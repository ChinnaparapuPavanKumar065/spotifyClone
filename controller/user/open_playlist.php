<?php
session_start();
include("../../db_config.php");
include("../shared/user_library.php");
include("../shared/user_playlists.php");

if(!isset($_SESSION['user_id']))
{
    header("Location: user_login.php");
    exit();
}

ensureUserPlaylistSchema($conn);
ensureUserLibrarySchema($conn);

$escaped_canonical_user_id = getEscapedCanonicalPlaylistUserId($conn, $_SESSION['user_id']);

if(!$escaped_canonical_user_id)
{
    header("Location: user_login.php");
    exit();
}

$userQuery = mysqli_query($conn,
"SELECT id, user_id
FROM users
WHERE user_id='$escaped_canonical_user_id'
LIMIT 1");
$userRow = $userQuery ? mysqli_fetch_assoc($userQuery) : null;
$candidateUserIds = [];

if($userRow)
{
    $candidateUserIds[] = $userRow['user_id'];

    if(isset($userRow['id']) && $userRow['id'] !== "")
    {
        $candidateUserIds[] = (string) ((int) $userRow['id']);
    }
}

$candidateUserIds = array_values(array_unique($candidateUserIds));
normalizeLibraryOwnership($conn, "user_liked_songs", $escaped_canonical_user_id, $candidateUserIds);
normalizeLibraryOwnership($conn, "user_downloads", $escaped_canonical_user_id, $candidateUserIds);
$likedSongIds = getUserSongStateMap($conn, "user_liked_songs", $candidateUserIds);
$downloadedSongIds = getUserSongStateMap($conn, "user_downloads", $candidateUserIds);

if(!isset($_GET['id']))
{
    header("Location: index.php");
    exit();
}

$playlist_id = (int) $_GET['id'];

$playlist_query = mysqli_query($conn,
"SELECT * FROM playlists
WHERE id='$playlist_id'
AND " . getPlaylistAccessCondition($escaped_canonical_user_id) . "
LIMIT 1");

if(!$playlist_query || mysqli_num_rows($playlist_query) === 0)
{
    header("Location: index.php");
    exit();
}

$playlist = mysqli_fetch_assoc($playlist_query);
$is_owned_playlist =
    isset($playlist['owner_user_id']) &&
    $playlist['owner_user_id'] === $escaped_canonical_user_id;

if($is_owned_playlist && isset($_GET['add_song']))
{
    $song_id = (int) $_GET['add_song'];

    if($song_id > 0)
    {
        $check_song = mysqli_query($conn,
        "SELECT id
        FROM playlist_songs
        WHERE playlist_id='$playlist_id'
        AND song_id='$song_id'
        LIMIT 1");

        if($check_song && mysqli_num_rows($check_song) === 0)
        {
            mysqli_query($conn,
            "INSERT INTO playlist_songs(
                playlist_id,
                song_id
            )
            VALUES(
                '$playlist_id',
                '$song_id'
            )");
        }
    }

    header("Location: open_playlist.php?id=$playlist_id");
    exit();
}

if($is_owned_playlist && isset($_GET['remove_song']))
{
    $song_id = (int) $_GET['remove_song'];

    if($song_id > 0)
    {
        mysqli_query($conn,
        "DELETE FROM playlist_songs
        WHERE playlist_id='$playlist_id'
        AND song_id='$song_id'");
    }

    header("Location: open_playlist.php?id=$playlist_id");
    exit();
}

$songs_query = mysqli_query($conn,
"SELECT songs.*, songs.id AS song_id
FROM playlist_songs
INNER JOIN songs
ON playlist_songs.song_id = songs.id
WHERE playlist_songs.playlist_id='$playlist_id'
ORDER BY playlist_songs.id DESC");
$all_songs_query = null;

if($is_owned_playlist)
{
    $all_songs_query = mysqli_query($conn,
    "SELECT *, id AS song_id
    FROM songs
    ORDER BY id DESC");
}

$playlist_songs = [];
$available_songs = [];

if($songs_query)
{
    while($song = mysqli_fetch_assoc($songs_query))
    {
        $playlist_songs[] = $song;
    }
}

if($all_songs_query)
{
    while($song = mysqli_fetch_assoc($all_songs_query))
    {
        $available_songs[] = $song;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($playlist['playlist_name']); ?> - Melodix</title>
    <link rel="icon" type="image/x-icon" href="../logo.png"/>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;700;800;900&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
<style>
body{
    font-family:'Montserrat',sans-serif;
}
.glass-panel{
    background:rgba(18,18,18,0.72);
    backdrop-filter:blur(30px);
    -webkit-backdrop-filter:blur(30px);
}
.progress-container{
    width:100%;
    height:4px;
    background:#444;
    border-radius:999px;
    cursor:pointer;
    position:relative;
}
.progress{
    height:100%;
    width:0%;
    background:#53e076;
    border-radius:999px;
}
.song-row:hover .row-play-button{
    opacity:1;
    transform:translateY(0);
}
</style>
</head>
<body class="bg-[#131313] text-white overflow-x-hidden">
<div class="min-h-screen pb-[120px]">
    <section class="relative overflow-hidden border-b border-white/10">
        <div class="absolute inset-0 bg-gradient-to-b from-[#53e076]/20 via-[#1a1a1a] to-[#131313]"></div>
        <div class="relative px-6 py-10 md:px-10 lg:px-12">
            <a href="index.php" class="inline-flex items-center gap-2 text-sm font-semibold text-white/80 hover:text-[#53e076]">
                <span class="material-symbols-outlined">arrow_back</span>
                Back to Home
            </a>
            <div class="mt-8 flex flex-col gap-8 md:flex-row md:items-end">
                <img
                src="../../assets/uploads/playlists/<?php echo htmlspecialchars($playlist['cover_image']); ?>"
                alt="<?php echo htmlspecialchars($playlist['playlist_name']); ?>"
                class="h-64 w-64 rounded-3xl object-cover shadow-2xl shadow-black/40"
                >
                <div class="max-w-3xl">
                    <p class="text-sm font-bold uppercase tracking-[0.35em] text-[#53e076]">Playlist</p>
                    <h1 class="mt-4 text-5xl font-black leading-none md:text-7xl">
                        <?php echo htmlspecialchars($playlist['playlist_name']); ?>
                    </h1>
                    <p class="mt-5 max-w-2xl text-base text-white/70 md:text-lg">
                        <?php echo htmlspecialchars($playlist['description']); ?>
                    </p>
                    <div class="mt-6 flex flex-wrap items-center gap-4 text-sm text-white/60">
                        <span><?php echo count($playlist_songs); ?> songs</span>
                        <span class="h-1.5 w-1.5 rounded-full bg-white/30"></span>
                        <span>Made for your listening mood</span>
                    </div>
                    <div class="mt-8 flex flex-wrap items-center gap-4">
                        <button
                        type="button"
                        onclick="playPlaylist()"
                        class="inline-flex h-14 items-center gap-2 rounded-full bg-[#53e076] px-8 text-base font-bold text-black transition hover:scale-105 hover:bg-[#6af08a]">
                            <span class="material-symbols-outlined">play_arrow</span>
                            Play Playlist
                        </button>
                        <!-- <button
                        type="button"
                        onclick="shufflePlaylist()"
                        class="inline-flex h-14 items-center gap-2 rounded-full border border-white/15 px-8 text-base font-semibold text-white transition hover:border-[#53e076] hover:text-[#53e076]">
                            <span class="material-symbols-outlined">shuffle</span>
                            Shuffle
                        </button> -->
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="px-6 py-8 md:px-10 lg:px-12">
        <div class="rounded-[28px] border border-white/8 bg-[#171717] p-3 md:p-5">
            <div class="hidden grid-cols-[70px_minmax(0,1fr)_160px_120px] gap-4 border-b border-white/10 px-4 pb-4 text-xs font-bold uppercase tracking-[0.3em] text-white/40 md:grid">
                <div>#</div>
                <div>Title</div>
                <div>Album</div>
                <div>Action</div>
            </div>

            <?php if(count($playlist_songs) > 0) { ?>
                <div class="mt-2 space-y-2">
                    <?php foreach($playlist_songs as $index => $song) { ?>
                        <div class="song-row grid grid-cols-1 gap-4 rounded-2xl px-4 py-4 transition hover:bg-white/5 md:grid-cols-[70px_minmax(0,1fr)_160px_120px] md:items-center">
                            <div class="flex items-center gap-3 text-white/60">
                                <span class="text-sm font-semibold"><?php echo $index + 1; ?></span>
                                <button
                                type="button"
                                onclick='playSingleSong(
                                <?php echo (int) $song["song_id"]; ?>,
                                <?php echo json_encode($song["song_title"]); ?>,
                                <?php echo json_encode($song["artist_name"]); ?>,
                                <?php echo json_encode($song["song_file"]); ?>,
                                <?php echo json_encode($song["cover_image"]); ?>
                                )'
                                class="row-play-button inline-flex h-10 w-10 translate-y-1 items-center justify-center rounded-full bg-[#53e076] text-black opacity-100 transition hover:scale-105 md:opacity-0">
                                    <span class="material-symbols-outlined">play_arrow</span>
                                </button>
                            </div>
                            <div class="flex items-center gap-4 min-w-0">
                                <img
                                src="../../assets/uploads/covers/<?php echo htmlspecialchars($song['cover_image']); ?>"
                                alt="<?php echo htmlspecialchars($song['song_title']); ?>"
                                class="h-14 w-14 rounded-xl object-cover"
                                >
                                <div class="min-w-0">
                                    <h3 class="truncate text-base font-bold"><?php echo htmlspecialchars($song['song_title']); ?></h3>
                                    <p class="truncate text-sm text-white/60"><?php echo htmlspecialchars($song['artist_name']); ?></p>
                                </div>
                            </div>
                            <div class="text-sm text-white/55">
                                <?php echo htmlspecialchars($song['album_name'] ?: 'Single'); ?>
                            </div>
                            <div class="flex items-center gap-2 md:justify-end">
                                <button
                                type="button"
                                class="like-toggle-btn inline-flex h-11 w-11 items-center justify-center rounded-full border transition <?php echo isset($likedSongIds[(int) $song['song_id']]) ? 'border-[#53e076]/40 text-[#53e076]' : 'border-white/10 text-white hover:border-[#53e076] hover:text-[#53e076]'; ?>"
                                data-song-id="<?php echo (int) $song['song_id']; ?>"
                                data-liked="<?php echo isset($likedSongIds[(int) $song['song_id']]) ? '1' : '0'; ?>">
                                    <span class="material-symbols-outlined" style="<?php echo isset($likedSongIds[(int) $song['song_id']]) ? "font-variation-settings:'FILL' 1;" : ""; ?>">favorite</span>
                                </button>
                                <button
                                type="button"
                                class="download-toggle-btn inline-flex h-11 w-11 items-center justify-center rounded-full border transition <?php echo isset($downloadedSongIds[(int) $song['song_id']]) ? 'border-[#53e076]/40 text-[#53e076]' : 'border-white/10 text-white hover:border-[#53e076] hover:text-[#53e076]'; ?>"
                                data-song-id="<?php echo (int) $song['song_id']; ?>"
                                data-downloaded="<?php echo isset($downloadedSongIds[(int) $song['song_id']]) ? '1' : '0'; ?>">
                                    <span class="material-symbols-outlined"><?php echo isset($downloadedSongIds[(int) $song['song_id']]) ? 'download_done' : 'download'; ?></span>
                                </button>
                                <button
                                type="button"
                                onclick='playSingleSong(
                                <?php echo (int) $song["song_id"]; ?>,
                                <?php echo json_encode($song["song_title"]); ?>,
                                <?php echo json_encode($song["artist_name"]); ?>,
                                <?php echo json_encode($song["song_file"]); ?>,
                                <?php echo json_encode($song["cover_image"]); ?>
                                )'
                                class="inline-flex h-11 items-center gap-2 rounded-full border border-white/10 px-5 text-sm font-semibold text-white transition hover:border-[#53e076] hover:text-[#53e076]">
                                    <span class="material-symbols-outlined text-base">play_arrow</span>
                                    Play
                                </button>
                                <?php if($is_owned_playlist) { ?>
                                    <a
                                    href="open_playlist.php?id=<?php echo $playlist_id; ?>&remove_song=<?php echo (int) $song['song_id']; ?>"
                                    class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-red-500/30 text-red-300 transition hover:border-red-400 hover:text-red-200">
                                        <span class="material-symbols-outlined text-base">delete</span>
                                    </a>
                                <?php } ?>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            <?php } else { ?>
                <div class="px-4 py-10 text-center text-white/55">
                    No songs have been added to this playlist yet.
                </div>
            <?php } ?>
        </div>
    </section>

    <?php if($is_owned_playlist) { ?>
        <section class="px-6 pb-8 md:px-10 lg:px-12">
            <div class="rounded-[28px] border border-white/8 bg-[#171717] p-3 md:p-5">
                <div class="border-b border-white/10 px-4 pb-4">
                    <h2 class="text-2xl font-bold text-white">Add Songs</h2>
                    <p class="mt-2 text-sm text-white/55">Only your playlist can receive songs here.</p>
                </div>

                <?php if(count($available_songs) > 0) { ?>
                    <div class="mt-2 space-y-2">
                        <?php foreach($available_songs as $index => $song) { ?>
                            <div class="song-row grid grid-cols-1 gap-4 rounded-2xl px-4 py-4 transition hover:bg-white/5 md:grid-cols-[70px_minmax(0,1fr)_160px_120px] md:items-center">
                                <div class="flex items-center gap-3 text-white/60">
                                    <span class="text-sm font-semibold"><?php echo $index + 1; ?></span>
                                </div>
                                <div class="flex min-w-0 items-center gap-4">
                                    <img
                                    src="../../assets/uploads/covers/<?php echo htmlspecialchars($song['cover_image']); ?>"
                                    alt="<?php echo htmlspecialchars($song['song_title']); ?>"
                                    class="h-14 w-14 rounded-xl object-cover"
                                    >
                                    <div class="min-w-0">
                                        <h3 class="truncate text-base font-bold"><?php echo htmlspecialchars($song['song_title']); ?></h3>
                                        <p class="truncate text-sm text-white/60"><?php echo htmlspecialchars($song['artist_name']); ?></p>
                                    </div>
                                </div>
                                <div class="text-sm text-white/55">
                                    <?php echo htmlspecialchars($song['album_name'] ?: 'Single'); ?>
                                </div>
                                <div class="flex items-center md:justify-end">
                                    <a
                                    href="open_playlist.php?id=<?php echo $playlist_id; ?>&add_song=<?php echo (int) $song['song_id']; ?>"
                                    class="inline-flex h-11 items-center gap-2 rounded-full bg-[#53e076] px-5 text-sm font-semibold text-black transition hover:scale-105 hover:bg-[#6af08a]">
                                        <span class="material-symbols-outlined text-base">add</span>
                                        Add
                                    </a>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                <?php } else { ?>
                    <div class="px-4 py-10 text-center text-white/55">
                        No songs are available to add right now.
                    </div>
                <?php } ?>
            </div>
        </section>
    <?php } ?>
</div>

<footer class="glass-panel fixed bottom-0 left-0 z-50 h-[90px] w-full border-t border-white/5 shadow-[0_-8px_24px_rgba(0,0,0,0.5)]">
    <div class="flex h-full items-center justify-between px-6">
        <div class="flex w-1/3 items-center gap-4">
            <div class="h-14 w-14 overflow-hidden rounded shadow-lg">
                <img id="player-cover" class="h-full w-full object-cover" src="">
            </div>
            <div class="hidden sm:block">
                <h6 id="player-title" class="font-semibold text-white">No Song Selected</h6>
                <p id="player-artist" class="text-sm text-gray-400">Unknown Artist</p>
            </div>
        </div>

        <div class="flex w-1/3 flex-col items-center gap-2">
            <div class="flex items-center gap-6">
                <button onclick="previousSong()" class="material-symbols-outlined text-3xl text-white hover:text-[#53e076]">skip_previous</button>
                <button onclick="togglePlay()" class="flex h-10 w-10 items-center justify-center rounded-full bg-white transition hover:scale-105">
                    <span id="play-icon" class="material-symbols-outlined text-black">play_arrow</span>
                </button>
                <button onclick="nextSong()" class="material-symbols-outlined text-3xl text-white hover:text-[#53e076]">skip_next</button>
            </div>
            <div class="flex w-full max-w-md items-center gap-3">
                <span id="current-time" class="text-xs text-gray-400">0:00</span>
                <div class="progress-container flex-1" onclick="seekSong(event)">
                    <div class="progress" id="progress"></div>
                </div>
                <span id="duration" class="text-xs text-gray-400">0:00</span>
            </div>
        </div>

        <div class="flex w-1/3 items-center justify-end gap-3">
            <span class="material-symbols-outlined text-gray-400">volume_up</span>
            <input type="range" min="0" max="1" step="0.1" value="1" oninput="changeVolume(this.value)" class="w-24 accent-green-500">
        </div>
    </div>
</footer>

<audio id="audio-player"></audio>

<script>
const PLAYER_STATE_KEY = "melodixPlayerState";
const LISTENER_ACTIVITY_URL = "update_listener_activity.php";
const LISTENING_TIME_URL = "save_listening_time.php";
const TOGGLE_LIKE_URL = "toggle_like_song.php";
const TOGGLE_DOWNLOAD_URL = "toggle_download_song.php";
const playlistSongs = <?php echo json_encode($playlist_songs, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
let songs = [];
let currentSongIndex = 0;
let listeningSecondsPending = 0;
let lastTrackedAudioTime = 0;

const audioPlayer = document.getElementById("audio-player");
const playIcon = document.getElementById("play-icon");
const volumeControl = document.querySelector('input[type="range"][oninput="changeVolume(this.value)"]');

function sendPostRequest(url, body, useBeacon)
{
    if(useBeacon && navigator.sendBeacon)
    {
        const payload = new Blob([body], {
            type: "application/x-www-form-urlencoded"
        });

        navigator.sendBeacon(url, payload);
        return;
    }

    fetch(url, {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: body,
        keepalive: useBeacon
    }).catch(function(error)
    {
        console.error(error);
    });
}

function pingListenerActivity(useBeacon)
{
    sendPostRequest(LISTENER_ACTIVITY_URL, "ping=1", !!useBeacon);
}

function resetListeningTracker()
{
    listeningSecondsPending = 0;
    lastTrackedAudioTime = 0;
}

function captureListeningProgress()
{
    if(audioPlayer.paused)
    {
        lastTrackedAudioTime = audioPlayer.currentTime || 0;
        return;
    }

    const currentAudioTime = audioPlayer.currentTime || 0;
    const delta = currentAudioTime - lastTrackedAudioTime;

    if(delta > 0 && delta < 5)
    {
        listeningSecondsPending += delta;
    }

    lastTrackedAudioTime = currentAudioTime;
}

function persistListeningTime(useBeacon)
{
    captureListeningProgress();

    const currentSong = songs[currentSongIndex];
    const songId = currentSong && (currentSong.song_id || currentSong.id);
    const secondsToSave = Math.floor(listeningSecondsPending);

    if(!songId || secondsToSave <= 0)
    {
        return;
    }

    sendPostRequest(
        LISTENING_TIME_URL,
        "song_id=" + encodeURIComponent(songId) +
        "&seconds_played=" + encodeURIComponent(secondsToSave),
        !!useBeacon
    );

    listeningSecondsPending = 0;
}

function playSingleSong(songId, title, artist, file, cover)
{
    const targetIndex = playlistSongs.findIndex(function(song)
    {
        return Number(song.song_id || song.id) === Number(songId);
    });

    if(targetIndex >= 0)
    {
        songs = playlistSongs.slice();
        currentSongIndex = targetIndex;
    }
    else
    {
        songs = [{
            song_id: songId,
            song_title: title,
            artist_name: artist,
            song_file: file,
            cover_image: cover
        }];
        currentSongIndex = 0;
    }

    loadSong(currentSongIndex);
    playSong();
}

function playPlaylist()
{
    if(playlistSongs.length === 0)
    {
        return;
    }

    songs = playlistSongs.slice();
    currentSongIndex = 0;
    loadSong(currentSongIndex);
    playSong();
}

function shufflePlaylist()
{
    if(playlistSongs.length === 0)
    {
        return;
    }

    songs = playlistSongs.slice();
    currentSongIndex = Math.floor(Math.random() * songs.length);
    loadSong(currentSongIndex);
    playSong();
}

function loadSong(index, shouldSaveRecent)
{
    const song = songs[index];

    if(!song)
    {
        return;
    }

    persistListeningTime(false);
    resetListeningTracker();

    audioPlayer.src = "../../assets/uploads/songs/" + song.song_file;
    document.getElementById("player-title").innerText = song.song_title;
    document.getElementById("player-artist").innerText = song.artist_name;
    document.getElementById("player-cover").src = "../../assets/uploads/covers/" + song.cover_image;
    if(shouldSaveRecent !== false)
    {
        saveRecentlyPlayed(song);
    }
    persistPlayerState();
}

function playSong()
{
    audioPlayer.play();
    playIcon.innerText = "pause";
    persistPlayerState();
}

function pauseSong()
{
    persistListeningTime(false);
    audioPlayer.pause();
    playIcon.innerText = "play_arrow";
    persistPlayerState();
}

function togglePlay()
{
    if(audioPlayer.paused)
    {
        playSong();
    }
    else
    {
        pauseSong();
    }
}

function nextSong()
{
    if(songs.length === 0)
    {
        return;
    }

    currentSongIndex++;

    if(currentSongIndex >= songs.length)
    {
        currentSongIndex = 0;
    }

    loadSong(currentSongIndex);
    playSong();
}

function previousSong()
{
    if(songs.length === 0)
    {
        return;
    }

    currentSongIndex--;

    if(currentSongIndex < 0)
    {
        currentSongIndex = songs.length - 1;
    }

    loadSong(currentSongIndex);
    playSong();
}

function changeVolume(value)
{
    audioPlayer.volume = value;
    persistPlayerState();
}

function seekSong(event)
{
    captureListeningProgress();
    const width = event.currentTarget.clientWidth;
    const clickX = event.offsetX;
    const duration = audioPlayer.duration;

    if(!duration)
    {
        return;
    }

    audioPlayer.currentTime = (clickX / width) * duration;
    persistPlayerState();
}

function formatTime(time)
{
    if(isNaN(time))
    {
        return "0:00";
    }

    let minutes = Math.floor(time / 60);
    let seconds = Math.floor(time % 60);

    if(seconds < 10)
    {
        seconds = "0" + seconds;
    }

    return minutes + ":" + seconds;
}

function saveRecentlyPlayed(song)
{
    const songId = song && (song.song_id || song.id);

    if(!songId)
    {
        return;
    }

    fetch("save_recently_played.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: "song_id=" + encodeURIComponent(songId)
    }).catch(function(error)
    {
        console.error(error);
    });
}

function persistPlayerState()
{
    const currentSong = songs[currentSongIndex];

    if(!currentSong || !audioPlayer.src)
    {
        return;
    }

    localStorage.setItem(PLAYER_STATE_KEY, JSON.stringify({
        songs: songs,
        currentSongIndex: currentSongIndex,
        currentTime: audioPlayer.currentTime || 0,
        isPlaying: !audioPlayer.paused,
        volume: audioPlayer.volume
    }));
}

function restorePlayerState()
{
    const savedState = localStorage.getItem(PLAYER_STATE_KEY);

    if(!savedState)
    {
        return;
    }

    try
    {
        const playerState = JSON.parse(savedState);

        if(!Array.isArray(playerState.songs) || playerState.songs.length === 0)
        {
            return;
        }

        songs = playerState.songs.map(function(song)
        {
            return Object.assign({}, song);
        });

        currentSongIndex = Math.min(
            Math.max(Number(playerState.currentSongIndex) || 0, 0),
            songs.length - 1
        );

        if(volumeControl && typeof playerState.volume === "number")
        {
            audioPlayer.volume = playerState.volume;
            volumeControl.value = playerState.volume;
        }

        loadSong(currentSongIndex, false);

        audioPlayer.addEventListener("loadedmetadata", function handleRestore()
        {
            audioPlayer.currentTime = Number(playerState.currentTime) || 0;

            if(playerState.isPlaying)
            {
                playSong();
            }
            else
            {
                pauseSong();
            }

            audioPlayer.removeEventListener("loadedmetadata", handleRestore);
        });
    }
    catch(error)
    {
        console.error(error);
    }
}

function updateLikeButtons(songId, isLiked)
{
    document.querySelectorAll('.like-toggle-btn[data-song-id="' + songId + '"]').forEach(function(button)
    {
        const icon = button.querySelector(".material-symbols-outlined");
        button.dataset.liked = isLiked ? "1" : "0";

        if(isLiked)
        {
            button.classList.remove("border-white/10", "text-white");
            button.classList.add("border-[#53e076]/40", "text-[#53e076]");
            icon.style.fontVariationSettings = "'FILL' 1";
        }
        else
        {
            button.classList.remove("border-[#53e076]/40", "text-[#53e076]");
            button.classList.add("border-white/10", "text-white");
            icon.style.fontVariationSettings = "";
        }
    });
}

function updateDownloadButtons(songId, isDownloaded)
{
    document.querySelectorAll('.download-toggle-btn[data-song-id="' + songId + '"]').forEach(function(button)
    {
        const icon = button.querySelector(".material-symbols-outlined");
        button.dataset.downloaded = isDownloaded ? "1" : "0";

        if(isDownloaded)
        {
            button.classList.remove("border-white/10", "text-white");
            button.classList.add("border-[#53e076]/40", "text-[#53e076]");
            icon.innerText = "download_done";
        }
        else
        {
            button.classList.remove("border-[#53e076]/40", "text-[#53e076]");
            button.classList.add("border-white/10", "text-white");
            icon.innerText = "download";
        }
    });
}

function wireSongActionButtons()
{
    document.querySelectorAll(".like-toggle-btn").forEach(function(button)
    {
        if(button.dataset.boundLike === "1")
        {
            return;
        }

        button.dataset.boundLike = "1";
        button.addEventListener("click", function()
        {
            const songId = this.dataset.songId;

            fetch(TOGGLE_LIKE_URL, {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: "song_id=" + encodeURIComponent(songId)
            })
            .then(function(response)
            {
                return response.json();
            })
            .then(function(data)
            {
                if(!data.success)
                {
                    throw new Error(data.message || "Unable to update liked songs.");
                }

                updateLikeButtons(songId, !!data.liked);
            })
            .catch(function(error)
            {
                console.error(error);
            });
        });
    });

    document.querySelectorAll(".download-toggle-btn").forEach(function(button)
    {
        if(button.dataset.boundDownload === "1")
        {
            return;
        }

        button.dataset.boundDownload = "1";
        button.addEventListener("click", function()
        {
            const songId = this.dataset.songId;

            fetch(TOGGLE_DOWNLOAD_URL, {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: "song_id=" + encodeURIComponent(songId)
            })
            .then(function(response)
            {
                return response.json();
            })
            .then(function(data)
            {
                if(!data.success)
                {
                    throw new Error(data.message || "Unable to update downloads.");
                }

                updateDownloadButtons(songId, !!data.downloaded);
            })
            .catch(function(error)
            {
                console.error(error);
            });
        });
    });
}

audioPlayer.addEventListener("timeupdate", function()
{
    captureListeningProgress();
    const progress = document.getElementById("progress");
    const currentTime = document.getElementById("current-time");
    const duration = document.getElementById("duration");
    const progressPercent = (audioPlayer.currentTime / audioPlayer.duration) * 100;

    progress.style.width = (isNaN(progressPercent) ? 0 : progressPercent) + "%";
    currentTime.innerText = formatTime(audioPlayer.currentTime);
    duration.innerText = formatTime(audioPlayer.duration);
    persistPlayerState();
});

audioPlayer.addEventListener("ended", function()
{
    persistListeningTime(false);
    nextSong();
});

window.addEventListener("beforeunload", function()
{
    persistListeningTime(true);
    persistPlayerState();
    pingListenerActivity(true);
});

window.addEventListener("load", function()
{
    restorePlayerState();
    wireSongActionButtons();
    pingListenerActivity(false);
});

window.setInterval(function()
{
    pingListenerActivity(false);
}, 60000);
</script>
</body>
</html>
