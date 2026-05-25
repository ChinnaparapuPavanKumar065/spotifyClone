<?php
session_start();
include("../../db_config.php");
include("../shared/user_library.php");
include("../shared/user_playlists.php");
$current_page = basename(__FILE__);
if(!isset($_SESSION['user_id']))
{
    header("Location: user_login.php");
    exit();
}
$user_name = $_SESSION['username'];
ensureUserLibrarySchema($conn);
ensureUserPlaylistSchema($conn);
$user = getCanonicalLibraryUser($conn, $_SESSION['user_id']);
if(!$user)
{
    header("Location: user_login.php");
    exit();
}
$canonicalUserId = $user['user_id'];
$escapedCanonicalUserId = mysqli_real_escape_string($conn, $canonicalUserId);
$libraryPlaylistCondition = "playlists.owner_user_id='$escapedCanonicalUserId'";
$candidateUserIds = getLibraryCandidateUserIds($user);
normalizeLibraryOwnership($conn, "user_liked_songs", $canonicalUserId, $candidateUserIds);
normalizeLibraryOwnership($conn, "user_downloads", $canonicalUserId, $candidateUserIds);
$likedSongs = [];
$downloadedSongs = [];
$playlists = [];
$likedSongsQuery = mysqli_query($conn,
"SELECT songs.*, songs.id AS song_id, user_liked_songs.created_at AS liked_at
FROM user_liked_songs
INNER JOIN songs
ON user_liked_songs.song_id = songs.id
WHERE user_liked_songs.user_id='$escapedCanonicalUserId'
ORDER BY user_liked_songs.created_at DESC, user_liked_songs.id DESC");
if($likedSongsQuery)
{
    while($row = mysqli_fetch_assoc($likedSongsQuery))
    {
        $likedSongs[] = $row;
    }
}
$downloadedSongsQuery = mysqli_query($conn,
"SELECT songs.*, songs.id AS song_id, user_downloads.created_at AS downloaded_at
FROM user_downloads
INNER JOIN songs
ON user_downloads.song_id = songs.id
WHERE user_downloads.user_id='$escapedCanonicalUserId'
ORDER BY user_downloads.created_at DESC, user_downloads.id DESC");

if($downloadedSongsQuery)
{
    while($row = mysqli_fetch_assoc($downloadedSongsQuery))
    {
        $downloadedSongs[] = $row;
    }
}

$playlistsQuery = mysqli_query($conn,
"SELECT *
FROM playlists
WHERE $libraryPlaylistCondition
ORDER BY id DESC
LIMIT 6");

if($playlistsQuery)
{
    while($row = mysqli_fetch_assoc($playlistsQuery))
    {
        $playlists[] = $row;
    }
}

$likedSongIds = [];
$downloadedSongIds = [];

foreach($likedSongs as $song)
{
    $likedSongIds[(int) $song['song_id']] = true;
}

foreach($downloadedSongs as $song)
{
    $downloadedSongIds[(int) $song['song_id']] = true;
}

$libraryQueue = [];
$seenSongIds = [];

foreach(array_merge($likedSongs, $downloadedSongs) as $song)
{
    $songId = (int) $song['song_id'];

    if($songId <= 0 || isset($seenSongIds[$songId]))
    {
        continue;
    }

    $seenSongIds[$songId] = true;
    $libraryQueue[] = $song;
}
?>
<!DOCTYPE html>
<html class="dark" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0"
name="viewport"/>
<title>Melodix - Music for everyone.</title>
    <link rel="icon" type="image/x-icon" href="../logo.png"/>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;700;800;900&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
<style>
.glass-panel{
background:rgba(18,18,18,0.6);
backdrop-filter:blur(30px);
-webkit-backdrop-filter:blur(30px);
}
.text-gradient{
background:linear-gradient(135deg,#53e076 0%,#1db954 100%);
-webkit-background-clip:text;
-webkit-text-fill-color:transparent;
}
.card-hover:hover .play-overlay{
opacity:1;
transform:translateY(0);
}
.card-image-container:hover img{
transform:scale(1.05);
}
.sidebar-bottom{
margin-top:270px; 
color:"#158e46";   
}
.playlist-card{
cursor:pointer;
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
</style>
</head>
<body class="bg-[#131313] text-white font-[Montserrat] overflow-x-hidden">
<!-- NAVBAR -->
<nav class="fixed top-0 right-0 w-full z-50 flex justify-between items-center h-16 px-8 bg-[#131313]/60 backdrop-blur-3xl">
    <a href="index.php">
<div class="flex items-center gap-2">
<span class="text-3xl font-black text-[#53e076]">
Melodix
</span>
</div>
</a>
<div class="hidden md:flex items-center gap-6 relative">
    <h3>
        Welcome :
        <strong class="text-[#53e076]">
            <?php echo $user_name; ?>
        </strong>
    </h3>
    <?php $first_letter = strtoupper(substr($user_name, 0, 1));?>
    <div class="relative">
        <button onclick="toggleProfileDropdown()" class="w-10 h-10 rounded-full bg-[#53e076] text-black font-bold flex items-center justify-center border border-gray-700 hover:scale-105 transition-all duration-300">
            <?php echo $first_letter; ?>
        </button>
        <div id="profileDropdown" class="hidden absolute right-0 mt-3 w-52 overflow-hidden z-50">
            <div class="p-4 border-b border-white/10">
                <p class="text-[#53e076] font-semibold text-center">
                    <?php echo $user_name; ?>
                </p>
            </div>
            <a href="profile.php" class="flex items-center gap-3 px-4 py-3 text-gray-300 hover:bg-[#2a2a2a] hover:text-[#53e076] transition">
                <span class="material-symbols-outlined">person</span>
                Profile
            </a>

            <a href="playlists.php" class="flex items-center gap-3 px-4 py-3 text-gray-300 hover:bg-[#2a2a2a] hover:text-[#53e076] transition">
                <span class="material-symbols-outlined">playlist_add</span>
                Playlists
            </a>
            
            <a href="library.php" class="flex items-center gap-3 px-4 py-3 text-gray-300 hover:bg-[#2a2a2a] hover:text-[#53e076] transition">
                <span class="material-symbols-outlined">library_music</span>
                Library
            </a>
            <a href="logout.php" class="flex items-center gap-3 px-4 py-3 text-red-400 hover:bg-[#2a2a2a] transition">
                <span class="material-symbols-outlined">logout</span>
                Logout
            </a>
        </div>
    </div>
</div></nav>
<!-- SIDEBAR -->
<aside class="hidden lg:flex flex-col p-6 gap-3 h-screen w-64 fixed left-0 top-0 bg-[#181818]/60 backdrop-blur-md border-r border-white/10 z-40 shadow-xl">
<div class="mb-5"></div>
<nav class="flex flex-col gap-4">
    <a class="flex items-center gap-3 p-2 text-gray-400 hover:text-[#53e076]" href="index.php">
<span class="material-symbols-outlined">
home
</span>
<span>
Home
</span>
</a>
<a class="flex items-center gap-3 p-2 text-gray-400 hover:text-[#53e076]" href="search.php">
<span class="material-symbols-outlined">
search
</span>
<span>
Search
</span>
</a>
<a class="flex items-center gap-3 p-2 text-gray-400 hover:text-[#53e076]" href="playlists.php">
<span class="material-symbols-outlined">
playlist_add    
</span>
<span>
Playlist
</span>
</a>
<a class="flex items-center gap-3 p-2 text-white font-bold hover:text-[#53e076]" href="library.php">
<span class="material-symbols-outlined">
library_music
</span>
<span>
Library
</span>
</a>
</nav>


</div>
</aside>
<main class="lg:ml-64 pt-16 pb-[120px] min-h-screen bg-[#131313]">
    <section class="relative px-8 py-14 overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-[#53e076]/10 via-[#131313] to-[#131313] -z-10"></div>
        <div class="absolute top-0 right-0 -translate-y-1/4 translate-x-1/4 w-[560px] h-[560px] bg-[#53e076]/15 rounded-full blur-[120px] -z-20"></div>
        <div class="max-w-5xl">
            <p class="text-sm font-bold uppercase tracking-[0.35em] text-[#53e076]">Library</p>
            <h1 class="mt-4 text-5xl md:text-6xl font-black leading-[0.95]">
                One place for your
                <span class="text-[#53e076]">liked songs, downloads.</span>
            </h1>
        </div>
    </section>

    <section class="px-8 pb-6">
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <a href="open_collection.php?type=liked" class="library-stat-card block rounded-[28px] p-6 bg-gradient-to-br from-[#4f1dff] via-[#7340ff] to-[#c7f3db] shadow-[0_18px_50px_rgba(79,29,255,0.25)] hover:scale-[1.01] transition">
                <div class="flex items-center justify-between">
                    <span class="material-symbols-outlined text-4xl text-white" style="font-variation-settings:'FILL' 1;">favorite</span>
                    <button
                    type="button"
                    onclick="event.preventDefault(); event.stopPropagation(); playCollection('liked')"
                    class="h-12 w-12 rounded-full bg-black/25 text-white backdrop-blur flex items-center justify-center hover:scale-105 transition">
                        <span class="material-symbols-outlined">play_arrow</span>
                    </button>
                </div>
                <h2 class="mt-12 text-3xl font-black">Liked Songs</h2>
                <p class="mt-2 text-white/80"><?php echo count($likedSongs); ?> songs saved to your personal liked playlist.</p>
            </a>

            <a href="open_collection.php?type=downloads" class="library-stat-card block rounded-[28px] p-6 bg-gradient-to-br from-[#0f766e] via-[#115e59] to-[#1f2937] shadow-[0_18px_50px_rgba(15,118,110,0.2)] hover:scale-[1.01] transition">
                <div class="flex items-center justify-between">
                    <span class="material-symbols-outlined text-4xl text-white" style="font-variation-settings:'FILL' 1;">download_done</span>
                    <button
                    type="button"
                    onclick="event.preventDefault(); event.stopPropagation(); playCollection('downloads')"
                    class="h-12 w-12 rounded-full bg-black/25 text-white backdrop-blur flex items-center justify-center hover:scale-105 transition">
                        <span class="material-symbols-outlined">play_arrow</span>
                    </button>
                </div>
                <h2 class="mt-12 text-3xl font-black">Downloads</h2>
                <p class="mt-2 text-white/80"><?php echo count($downloadedSongs); ?> songs ready in your downloads section.</p>
            </a>

            <a href="playlists.php" class="library-stat-card rounded-[28px] p-6 bg-[#1a1a1a] border border-white/10 hover:border-[#53e076]/50 transition block">
                <div class="flex items-center justify-between">
                    <span class="material-symbols-outlined text-4xl text-[#53e076]">playlist_add</span>
                    <span class="material-symbols-outlined text-gray-500">arrow_forward</span>
                </div>
                <h2 class="mt-12 text-3xl font-black">Create Playlist</h2>
                <p class="mt-2 text-gray-400">Open the playlist section to build and manage your playlist collection.</p>
            </a>
        </div>
    </section>
</main>

<footer class="fixed bottom-0 left-0 w-full h-[90px] z-50 glass-panel border-t border-white/5 shadow-[0_-8px_24px_rgba(0,0,0,0.5)]">
    <div class="flex items-center justify-between h-full px-6">
        <div class="flex items-center gap-4 w-1/3">
            <div class="w-14 h-14 rounded overflow-hidden shadow-lg">
                <img id="player-cover" class="w-full h-full object-cover" src="" alt="Now playing cover">
            </div>
            <div class="hidden sm:block">
                <h6 id="player-title" class="text-white font-semibold">No Song Selected</h6>
                <p id="player-artist" class="text-gray-400 text-sm">Unknown Artist</p>
            </div>
        </div>
        <div class="flex flex-col items-center gap-2 w-1/3">
            <div class="flex items-center gap-6">
                <button onclick="previousSong()" class="material-symbols-outlined text-white text-3xl hover:text-[#53e076]">skip_previous</button>
                <button onclick="togglePlay()" class="w-10 h-10 bg-white rounded-full flex items-center justify-center hover:scale-105">
                    <span id="play-icon" class="material-symbols-outlined text-black">play_arrow</span>
                </button>
                <button onclick="nextSong()" class="material-symbols-outlined text-white text-3xl hover:text-[#53e076]">skip_next</button>
            </div>
            <div class="w-full max-w-md flex items-center gap-3">
                <span id="current-time" class="text-xs text-gray-400">0:00</span>
                <div class="progress-container flex-1" onclick="seekSong(event)">
                    <div class="progress" id="progress"></div>
                </div>
                <span id="duration" class="text-xs text-gray-400">0:00</span>
            </div>
        </div>
        <div class="flex items-center justify-end gap-3 w-1/3">
            <span class="material-symbols-outlined text-gray-400">volume_up</span>
            <input
            id="volume-control"
            type="range"
            min="0"
            max="1"
            step="0.1"
            value="1"
            oninput="changeVolume(this.value)"
            class="w-24 accent-green-500">
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
const likedSongsQueue = <?php echo json_encode($likedSongs, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
const downloadedSongsQueue = <?php echo json_encode($downloadedSongs, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
const libraryQueue = <?php echo json_encode($libraryQueue, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

let songs = libraryQueue.length > 0 ? libraryQueue.slice() : [];
let currentSongIndex = 0;
let listeningSecondsPending = 0;
let lastTrackedAudioTime = 0;

const audioPlayer = document.getElementById("audio-player");
const playIcon = document.getElementById("play-icon");
const volumeControl = document.getElementById("volume-control");
const librarySearchInput = document.getElementById("library-search");

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
function toggleProfileDropdown()
{
    const dropdown =
    document.getElementById("profileDropdown");

    dropdown.classList.toggle("hidden");
}

window.addEventListener("click", function(event)
{
    const dropdown =
    document.getElementById("profileDropdown");

    const button =
    event.target.closest("button");

    if(!event.target.closest("#profileDropdown") &&
       !button?.onclick)
    {
        dropdown.classList.add("hidden");
    }
});

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

function playCollection(collectionKey)
{
    if(collectionKey === "liked")
    {
        songs = likedSongsQueue.slice();
    }
    else if(collectionKey === "downloads")
    {
        songs = downloadedSongsQueue.slice();
    }
    else
    {
        songs = libraryQueue.slice();
    }

    if(songs.length === 0)
    {
        return;
    }

    currentSongIndex = 0;
    loadSong(currentSongIndex);
    playSong();
}

function playSingleSong(songId, title, artist, file, cover)
{
    const targetIndex = songs.findIndex(function(song)
    {
        return Number(song.song_id || song.id) === Number(songId);
    });

    if(targetIndex >= 0)
    {
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

        songs = playerState.songs;
        currentSongIndex = Math.min(
            Math.max(Number(playerState.currentSongIndex) || 0, 0),
            songs.length - 1
        );

        if(typeof playerState.volume === "number")
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
            button.classList.add("downloaded-song", "border-[#53e076]/40", "text-[#53e076]");
            button.classList.remove("border-white/10", "text-white");
            icon.innerText = "download_done";
        }
        else
        {
            button.classList.remove("downloaded-song", "border-[#53e076]/40", "text-[#53e076]");
            button.classList.add("border-white/10", "text-white");
            icon.innerText = "download";
        }
    });
}

function attachLibraryActions()
{
    document.querySelectorAll(".like-toggle-btn").forEach(function(button)
    {
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

                if(!data.liked)
                {
                    window.location.reload();
                }
            })
            .catch(function(error)
            {
                console.error(error);
            });
        });
    });

    document.querySelectorAll(".download-toggle-btn").forEach(function(button)
    {
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

                if(!data.downloaded)
                {
                    window.location.reload();
                }
            })
            .catch(function(error)
            {
                console.error(error);
            });
        });
    });
}

function filterLibrarySongs()
{
    const searchValue = librarySearchInput.value.trim().toLowerCase();

    document.querySelectorAll(".library-song-card").forEach(function(card)
    {
        const title = card.dataset.songTitle || "";
        const artist = card.dataset.artistName || "";
        const matches = searchValue === "" || title.indexOf(searchValue) !== -1 || artist.indexOf(searchValue) !== -1;

        card.style.display = matches ? "" : "none";
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
    attachLibraryActions();
    pingListenerActivity(false);
});

librarySearchInput.addEventListener("input", filterLibrarySongs);

window.setInterval(function()
{
    pingListenerActivity(false);
}, 60000);
</script>
</body>
</html>
