<?php
session_start();
include("../../db_config.php");
include("../shared/user_library.php");
include("../shared/user_playlists.php");
include("../shared/recently_played.php");
$timeout_duration = 3000;
if(isset($_SESSION['LAST_ACTIVITY']))
{
    if(time() - $_SESSION['LAST_ACTIVITY'] > $timeout_duration)
    {
        session_unset();
        session_destroy();
        header("Location: user_login.php");
        exit();
    }
}
$_SESSION['LAST_ACTIVITY'] = time();
if(!isset($_SESSION['user_id']))
{
    header("Location: user_login.php");
    exit();
}
ensureUserLibrarySchema($conn);
ensureUserPlaylistSchema($conn);
ensureRecentlyPlayedSchema($conn);
$user_id = $_SESSION['user_id'];
$user_query = mysqli_query($conn,"SELECT * FROM users WHERE user_id='$user_id'");
$user_data = mysqli_fetch_assoc($user_query);

if(!$user_data)
{
    header("Location: user_login.php");
    exit();
}

$user_name = $user_data['username'];
$escaped_canonical_user_id = getEscapedCanonicalPlaylistUserId($conn, $_SESSION['user_id']);
$recent_songs = [];
$trending_songs = [];
$total_songs_count = 0;
$total_songs_query = mysqli_query($conn, "SELECT COUNT(*) AS total_songs FROM songs");

if($total_songs_query)
{
    $total_songs_data = mysqli_fetch_assoc($total_songs_query);
    $total_songs_count = (int) $total_songs_data['total_songs'];
}
$candidate_user_ids = [];

if(isset($user_data['user_id']) && $user_data['user_id'] !== "")
{
    $candidate_user_ids[] = $user_data['user_id'];
}
if(isset($user_data['id']) && $user_data['id'] !== "")
{
    $candidate_user_ids[] = (string) ((int) $user_data['id']);
}
$candidate_user_ids = array_values(array_unique($candidate_user_ids));
normalizeLibraryOwnership($conn, "user_liked_songs", $user_data['user_id'], $candidate_user_ids);
normalizeLibraryOwnership($conn, "user_downloads", $user_data['user_id'], $candidate_user_ids);
$liked_song_ids = getUserSongStateMap($conn, "user_liked_songs", $candidate_user_ids);
$downloaded_song_ids = getUserSongStateMap($conn, "user_downloads", $candidate_user_ids);
$playlist_visibility_condition = getPlaylistVisibilityCondition($escaped_canonical_user_id);
?>
<!DOCTYPE html>
<html class="dark" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
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
<span class="text-3xl font-black text-[#53e076]">Melodix</span>
</div>
</a>
<div class="hidden md:flex items-center gap-6 relative">
    <h3>Welcome :
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
</div>
</nav>
<!-- SIDEBAR -->
<aside class="hidden lg:flex flex-col p-6 gap-3 h-screen w-64 fixed left-0 top-0 bg-[#181818]/60 backdrop-blur-md border-r border-white/10 z-40 shadow-xl">
    <div class="mb-5"></div>
        <nav class="flex flex-col gap-4">
            <a class="flex items-center gap-3 p-2 text-white font-bold hover:text-[#53e076]" href="index.php">
                <span class="material-symbols-outlined">home</span>
                <span>Home</span>
            </a>
            <a class="flex items-center gap-3 p-2 text-gray-400 hover:text-[#53e076]" href="search.php">
                <span class="material-symbols-outlined">search</span>
                <span>Search</span>
            </a>
            <a class="flex items-center gap-3 p-2 text-gray-400 hover:text-[#53e076]" href="playlists.php">
                <span class="material-symbols-outlined">playlist_add</span>
                <span>Playlist</span>
            </a>
            <a class="flex items-center gap-3 p-2 text-gray-400 hover:text-[#53e076]" href="library.php">
                <span class="material-symbols-outlined">library_music</span>
                <span>Library</span>
            </a>
        </nav>
    </div>
</aside>
<!-- MAIN -->
<main class="lg:ml-64 pt-16 pb-[120px] min-h-screen bg-[#131313]">
<!-- HERO -->
<section class="relative px-8 py-16 overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-br from-[#53e076]/10 via-[#131313] to-[#131313] -z-10"></div>
        <div class="max-w-4xl">
            <h2 class="text-7xl font-black leading-[0.9] mb-4">Music for
                <br>
                <span class="text-gradient">Everyone.</span>
            </h2>
            <p class="text-2xl text-gray-400 max-w-2xl mb-6">Millions of songs.</p>
        </div>
    <div class="absolute top-0 right-0 -translate-y-1/4 translate-x-1/4 w-[600px] h-[600px] bg-[#53e076]/20 rounded-full blur-[120px] -z-20"></div>
</section>

<!-- RECENTLY PLAYED -->

<section class="px-8 py-12">
    <div class="flex justify-between items-end mb-8">
        <div>
            <h3 class="text-3xl font-bold">Recently Played</h3>
            <p class="text-gray-400">Songs you listened recently.</p>
        </div>
    </div>
<div id="recently-played-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
<?php
$recent_query = mysqli_query($conn,
"SELECT songs.*, songs.id AS song_id, recent_songs.played_at, recent_songs.id AS recent_id
FROM songs INNER JOIN(
    SELECT recently_played.song_id, recently_played.played_at, recently_played.id
    FROM recently_played
    INNER JOIN
    (
        SELECT song_id, MAX(id) AS latest_id FROM recently_played WHERE user_id='$escaped_canonical_user_id' GROUP BY song_id
    ) AS latest_recent
    ON recently_played.id = latest_recent.latest_id
    WHERE recently_played.user_id='$escaped_canonical_user_id'
) AS recent_songs
ON recent_songs.song_id = songs.id
ORDER BY recent_songs.played_at DESC, recent_songs.id DESC
LIMIT 6");
if($recent_query && mysqli_num_rows($recent_query) > 0)
{
    while($recent = mysqli_fetch_assoc($recent_query))
    {
        $recent_songs[] = $recent;
?>
<div
class="group flex items-center gap-4 bg-[#1f1f1f] hover:bg-[#2a2a2a] p-4 rounded-xl transition-all duration-300"
data-song-id="<?php echo (int) $recent['song_id']; ?>">
<div class="relative">
<img
src="../../assets/uploads/covers/<?php echo $recent['cover_image']; ?>"
class="w-16 h-16 rounded-lg object-cover"
>
<button
type="button"
onclick='playSingleSong(
<?php echo (int) $recent["song_id"]; ?>,
<?php echo json_encode($recent["song_title"]); ?>,
<?php echo json_encode($recent["artist_name"]); ?>,
<?php echo json_encode($recent["song_file"]); ?>,
<?php echo json_encode($recent["cover_image"]); ?>,
<?php echo json_encode("recentlyPlayed"); ?>
)'
class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-all duration-300 rounded-lg flex items-center justify-center">
<span class="material-symbols-outlined text-white">
play_arrow
</span>
</button>
</div>
<div class="flex-1 overflow-hidden">
<h4 class="text-white font-semibold truncate">
<?php echo $recent['song_title']; ?>
</h4>
<p class="text-gray-400 text-sm truncate">
<?php echo $recent['artist_name']; ?>
</p>
</div>
<div class="flex items-center gap-2">
<button
type="button"
class="like-toggle-btn w-10 h-10 rounded-full border transition <?php echo isset($liked_song_ids[(int) $recent['song_id']]) ? 'border-[#53e076]/40 text-[#53e076]' : 'border-white/10 text-white hover:border-[#53e076] hover:text-[#53e076]'; ?>"
data-song-id="<?php echo (int) $recent['song_id']; ?>"
data-liked="<?php echo isset($liked_song_ids[(int) $recent['song_id']]) ? '1' : '0'; ?>">
<span class="material-symbols-outlined" style="<?php echo isset($liked_song_ids[(int) $recent['song_id']]) ? "font-variation-settings:'FILL' 1;" : ""; ?>">favorite</span>
</button>
<button
type="button"
class="download-toggle-btn w-10 h-10 rounded-full border transition <?php echo isset($downloaded_song_ids[(int) $recent['song_id']]) ? 'border-[#53e076]/40 text-[#53e076]' : 'border-white/10 text-white hover:border-[#53e076] hover:text-[#53e076]'; ?>"
data-song-id="<?php echo (int) $recent['song_id']; ?>"
data-downloaded="<?php echo isset($downloaded_song_ids[(int) $recent['song_id']]) ? '1' : '0'; ?>">
<span class="material-symbols-outlined"><?php echo isset($downloaded_song_ids[(int) $recent['song_id']]) ? 'download_done' : 'download'; ?></span>
</button>
</div>
</div>
<?php
    }
}
else
{
?>
<p id="recently-played-empty"class="text-gray-400 col-span-full">Play a song to see it here.</p>
<?php
}
?>
</div>
</section>
<!-- TRENDING SONGS -->
<section class="px-8 py-12">
<div class="flex justify-between items-end mb-8">
<div>
<h3 class="text-3xl font-bold">
Trending Songs
</h3>
<p class="text-gray-400">
Most played tracks right now.
</p>
</div>
<?php if($total_songs_count > 6) { ?>
<a class="text-[#53e076] font-bold hover:underline" href="all_songs.php">
SEE ALL
</a>
<?php } ?>
</div>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
<?php
$song_query = mysqli_query($conn,
"SELECT *, id AS song_id FROM songs ORDER BY id DESC LIMIT 6");
if(mysqli_num_rows($song_query) > 0)
{
    while($song = mysqli_fetch_assoc($song_query))
    {
        $trending_songs[] = $song;
?>
<div class="group flex items-center gap-4 bg-[#1f1f1f] hover:bg-[#2a2a2a] p-4 rounded-xl transition-all duration-300">
<div class="relative">
<img src="../../assets/uploads/covers/<?php echo $song['cover_image']; ?>"class="w-16 h-16 rounded-lg object-cover">
<button
onclick='playSingleSong(
<?php echo (int) $song["song_id"]; ?>,
<?php echo json_encode($song["song_title"]); ?>,
<?php echo json_encode($song["artist_name"]); ?>,
<?php echo json_encode($song["song_file"]); ?>,
<?php echo json_encode($song["cover_image"]); ?>,
<?php echo json_encode("trendingSongs"); ?>
)'
class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-all duration-300 rounded-lg flex items-center justify-center">
<span class="material-symbols-outlined text-white">
play_arrow
</span>
</button>
</div>
<div class="flex-1 overflow-hidden">
<h4 class="text-white font-semibold truncate">
<?php echo $song['song_title']; ?>
</h4>
<p class="text-gray-400 text-sm truncate">
<?php echo $song['artist_name']; ?>
</p>
</div>
<div class="flex items-center gap-2">
<button
type="button"
class="like-toggle-btn w-10 h-10 rounded-full border transition <?php echo isset($liked_song_ids[(int) $song['song_id']]) ? 'border-[#53e076]/40 text-[#53e076]' : 'border-white/10 text-white hover:border-[#53e076] hover:text-[#53e076]'; ?>"
data-song-id="<?php echo (int) $song['song_id']; ?>"
data-liked="<?php echo isset($liked_song_ids[(int) $song['song_id']]) ? '1' : '0'; ?>">
<span class="material-symbols-outlined" style="<?php echo isset($liked_song_ids[(int) $song['song_id']]) ? "font-variation-settings:'FILL' 1;" : ""; ?>">favorite</span>
</button>
<button
type="button"
class="download-toggle-btn w-10 h-10 rounded-full border transition <?php echo isset($downloaded_song_ids[(int) $song['song_id']]) ? 'border-[#53e076]/40 text-[#53e076]' : 'border-white/10 text-white hover:border-[#53e076] hover:text-[#53e076]'; ?>"
data-song-id="<?php echo (int) $song['song_id']; ?>"
data-downloaded="<?php echo isset($downloaded_song_ids[(int) $song['song_id']]) ? '1' : '0'; ?>">
<span class="material-symbols-outlined"><?php echo isset($downloaded_song_ids[(int) $song['song_id']]) ? 'download_done' : 'download'; ?></span>
</button>
</div>
<button
onclick='playSingleSong(
<?php echo (int) $song["song_id"]; ?>,
<?php echo json_encode($song["song_title"]); ?>,
<?php echo json_encode($song["artist_name"]); ?>,
<?php echo json_encode($song["song_file"]); ?>,
<?php echo json_encode($song["cover_image"]); ?>,
<?php echo json_encode("trendingSongs"); ?>
)'
class="w-10 h-10 bg-[#53e076] rounded-full flex items-center justify-center hover:scale-110 transition-all duration-300">
<span class="material-symbols-outlined text-black">
play_arrow
</span>
</button>
</div>
<?php
    }
}
?>
</div>
</section>
<!-- TRENDING PLAYLISTS -->
<section class="px-8 py-12">
<div class="flex justify-between items-end mb-8">
<div>
<h3 class="text-3xl font-bold">
Trending Playlists
</h3>
<p class="text-gray-400">
Hand-picked by our curators for your mood.
</p>
</div>
<a class="text-[#53e076] font-bold hover:underline" href="#">
SEE ALL
</a>
</div>
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-6">
<?php
$playlist_query = mysqli_query($conn,
"SELECT *
FROM playlists
WHERE $playlist_visibility_condition
ORDER BY id DESC");
if(mysqli_num_rows($playlist_query) > 0)
{
    while($playlists = mysqli_fetch_assoc($playlist_query))
    {
?>
<div class="playlist-card group" onclick="openPlaylist(null, <?php echo (int) $playlists['id']; ?>, false)">
    <div class="card-hover p-4 bg-[#1f1f1f] rounded-xl transition-all duration-300 hover:bg-[#2a2a2a] shadow-[0_8px_24px_rgba(0,0,0,0.2)] h-full">
        <div class="relative aspect-square overflow-hidden rounded-lg card-image-container mb-4">
            <img src="../../assets/uploads/playlists/<?php echo $playlists['cover_image']; ?>" alt="<?php echo $playlists['playlist_name']; ?>" class="w-full h-full object-cover transition-transform duration-500">
                <div class="play-overlay absolute bottom-2 right-2 opacity-0 translate-y-2 transition-all duration-300">
                    <button type="button" onclick="openPlaylist(event, <?php echo (int) $playlists['id']; ?>, true)" class="w-12 h-12 bg-[#53e076] rounded-full flex items-center justify-center shadow-lg hover:scale-110 transition-all duration-300"> 
                        <span class="material-symbols-outlined text-black">play_arrow</span>
                    </button>
                </div>
        </div>
        <h4 class="text-white font-semibold truncate">
            <?php echo $playlists['playlist_name']; ?>
        </h4>
        <p class="text-gray-400 text-sm truncate">
            <?php echo $playlists['description']; ?>
        </p>
    </div>
</div>
<?php
    }
}
?>
</div>
</section>
</main>
<!-- FOOTER PLAYER -->
<footer class="fixed bottom-0 left-0 w-full h-[90px] z-50 glass-panel border-t border-white/5 shadow-[0_-8px_24px_rgba(0,0,0,0.5)]">
<div class="flex items-center justify-between h-full px-6">
<!-- SONG INFO -->
<div class="flex items-center gap-4 w-1/3">
<div class="w-14 h-14 rounded overflow-hidden shadow-lg">
<img
id="player-cover"
class="w-full h-full object-cover"
src=""
>
</div>
<div class="hidden sm:block">
<h6
id="player-title"
class="text-white font-semibold">
No Song Selected
</h6>
<p
id="player-artist"
class="text-gray-400 text-sm">
Unknown Artist
</p>
</div>
</div>
<!-- PLAYER CONTROLS -->
<div class="flex flex-col items-center gap-2 w-1/3">
<div class="flex items-center gap-6">
<button
onclick="previousSong()"
class="material-symbols-outlined text-white text-3xl hover:text-[#53e076]">
skip_previous
</button>
<button
onclick="togglePlay()"
class="w-10 h-10 bg-white rounded-full flex items-center justify-center hover:scale-105"
>
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
<!-- VOLUME -->
<div class="flex items-center justify-end gap-3 w-1/3">
<span class="material-symbols-outlined text-gray-400">
volume_up

</span>
<input type="range" min="0" max="1" step="0.1" value="1" oninput="changeVolume(this.value)" class="w-24 accent-green-500">
</div>
</div>
</footer>
<!-- AUDIO -->

<audio id="audio-player"></audio>
<script>
const PLAYER_STATE_KEY = "melodixPlayerState";
const LISTENER_ACTIVITY_URL = "update_listener_activity.php";
const LISTENING_TIME_URL = "save_listening_time.php";
const TOGGLE_LIKE_URL = "toggle_like_song.php";
const TOGGLE_DOWNLOAD_URL = "toggle_download_song.php";
const songSections = {
    recentlyPlayed: <?php echo json_encode($recent_songs, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,
    trendingSongs: <?php echo json_encode($trending_songs, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>
};
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
function playSingleSong(songId, title, artist, file, cover, sectionKey)
{
    const fallbackSong = {
        song_id: songId,
        song_title: title,
        artist_name: artist,
        song_file: file,
        cover_image: cover
    };
    const nextQueue = resolveQueue(sectionKey, songId, fallbackSong);
    songs = nextQueue;
    currentSongIndex = songs.findIndex(function(song)
    {
        return Number(song.song_id || song.id) === Number(songId);
    });
    if(currentSongIndex < 0)
    {
        currentSongIndex = 0;
    }
    loadSong(currentSongIndex);
    playSong();
}
let songs = [];
let currentSectionKey = null;
let currentSongIndex = 0;
const audioPlayer =
document.getElementById("audio-player");
const playIcon =
document.getElementById("play-icon");
let lastSavedRecentSongId = null;
let listeningSecondsPending = 0;
let lastTrackedAudioTime = 0;
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
function getCurrentSong()
{
    return songs[currentSongIndex] || null;
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
    const currentSong = getCurrentSong();
    const secondsToSave = Math.floor(listeningSecondsPending);
    const songId = currentSong && (currentSong.song_id || currentSong.id);
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
function cloneSongs(songList)
{
    return (Array.isArray(songList) ? songList : []).map(function(song)
    {
        return Object.assign({}, song);
    });
}
function getSongId(song)
{
    return Number(song.song_id || song.id || 0);
}
function getAllSongs()
{
    const uniqueSongs = [];
    const seenSongIds = {};
    Object.keys(songSections).forEach(function(sectionKey)
    {
        (songSections[sectionKey] || []).forEach(function(song)
        {
            const songId = getSongId(song);
            if(!songId || seenSongIds[songId])
            {
                return;
            }
            seenSongIds[songId] = true;
            uniqueSongs.push(Object.assign({}, song));
        });
    });
    return uniqueSongs;
}
function shuffleSongs(songList)
{
    const shuffledSongs = cloneSongs(songList);
    for(let index = shuffledSongs.length - 1; index > 0; index--)
    {
        const randomIndex = Math.floor(Math.random() * (index + 1));
        const temporarySong = shuffledSongs[index];
        shuffledSongs[index] = shuffledSongs[randomIndex];
        shuffledSongs[randomIndex] = temporarySong;
    }
    return shuffledSongs;
}
function resolveQueue(sectionKey, selectedSongId, fallbackSong)
{
    const sectionSongs = cloneSongs(songSections[sectionKey]);
    if(sectionSongs.length > 0)
    {
        currentSectionKey = sectionKey;
        return sectionSongs;
    }
    const randomSongs = shuffleSongs(getAllSongs());
    currentSectionKey = null;
    if(selectedSongId)
    {
        const selectedIndex = randomSongs.findIndex(function(song)
        {
            return getSongId(song) === Number(selectedSongId);
        });
        if(selectedIndex > 0)
        {
            const selectedSong = randomSongs.splice(selectedIndex, 1)[0];
            randomSongs.unshift(selectedSong);
        }
    }
    if(randomSongs.length > 0)
    {
        return randomSongs;
    }
    return fallbackSong ? [fallbackSong] : [];
}
function getRandomIndex(excludedIndex)
{
    if(songs.length <= 1)
    {
        return 0;
    }
    let randomIndex = excludedIndex;
    while(randomIndex === excludedIndex)
    {
        randomIndex = Math.floor(Math.random() * songs.length);
    }
    return randomIndex;
}
function loadPlaylist(playlistId)
{
    fetch(
    "get_playlist_songs.php?playlist_id="
    + playlistId)
    .then(response => response.json())
    .then(data =>
    {
        songs = data;
        currentSongIndex = 0;
        if(songs.length > 0)
        {
            loadSong(currentSongIndex);
            playSong();
        }
    });
}
function openPlaylist(event, playlistId, autoplay)
{
    if(event)
    {
        event.preventDefault();
        event.stopPropagation();
    }
    let url = "open_playlist.php?id=" + playlistId;
    if(autoplay)
    {
        url += "&autoplay=1";
    }
    window.location.href = url;
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

    audioPlayer.src =
    "../../assets/uploads/songs/"
    + song.song_file;

    document.getElementById("player-title")
    .innerText =
    song.song_title;

    document.getElementById("player-artist")
    .innerText =
    song.artist_name;

    document.getElementById("player-cover")
    .src =
    "../../assets/uploads/covers/"
    + song.cover_image;

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
    if(songs.length == 0)
    {
        return;
    }

    if(currentSectionKey === null)
    {
        currentSongIndex = getRandomIndex(currentSongIndex);
    }
    else
    {
        currentSongIndex++;

        if(currentSongIndex >= songs.length)
        {
            currentSongIndex = 0;
        }
    }

    loadSong(currentSongIndex);

    playSong();
}

function previousSong()
{
    if(songs.length == 0)
    {
        return;
    }

    if(currentSectionKey === null)
    {
        currentSongIndex = getRandomIndex(currentSongIndex);
    }
    else
    {
        currentSongIndex--;

        if(currentSongIndex < 0)
        {
            currentSongIndex = songs.length - 1;
        }
    }

    loadSong(currentSongIndex);

    playSong();
}

function changeVolume(value)
{
    audioPlayer.volume = value;
    persistPlayerState();
}

audioPlayer.addEventListener(
"timeupdate",
function()
{
    captureListeningProgress();
    const progress =
    document.getElementById("progress");

    const currentTime =
    document.getElementById("current-time");

    const duration =
    document.getElementById("duration");

    let progressPercent =
    (audioPlayer.currentTime /
    audioPlayer.duration) * 100;

    progress.style.width =
    progressPercent + "%";

    currentTime.innerText =
    formatTime(audioPlayer.currentTime);

    duration.innerText =
    formatTime(audioPlayer.duration);
});

function formatTime(time)
{
    if(isNaN(time))
    {
        return "0:00";
    }

    let minutes =
    Math.floor(time / 60);

    let seconds =
    Math.floor(time % 60);

    if(seconds < 10)
    {
        seconds = "0" + seconds;
    }
    return minutes + ":" + seconds;
}
function seekSong(event)
{
    captureListeningProgress();
    const width =event.currentTarget.clientWidth;
    const clickX =event.offsetX;
    const duration =audioPlayer.duration;
    if(!duration)
    {
        return;
    }
    audioPlayer.currentTime =
    (clickX / width) * duration;
    persistPlayerState();
}
audioPlayer.addEventListener(
"ended",
function()
{
    persistListeningTime(false);
    nextSong();
});
function saveRecentlyPlayed(song)
{
    const songId = song && (song.song_id || song.id);
    if(!songId)
    {
        return;
    }
    if(Number(lastSavedRecentSongId) !== Number(songId))
    {
        const recentSong = Object.assign({}, song, {
            song_id: songId
        });

        updateRecentlyPlayedUI(recentSong);
        lastSavedRecentSongId = songId;
    }
    fetch("save_recently_played.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: "song_id=" + encodeURIComponent(songId)
    })
    .then(function(response)
    {
        if(!response.ok)
        {
            throw new Error("Unable to save recent song.");
        }
        return response.json();
    })
    .then(function(data)
    {
        if(!data.success)
        {
            throw new Error(data.message || "Unable to save recent song.");
        }
    })
    .catch(function(error)
    {
        console.error(error);
    });
}
function updateRecentlyPlayedUI(song)
{
    const grid = document.getElementById("recently-played-grid");
    const normalizedSongId = getSongId(song);
    if(!grid || !normalizedSongId)
    {
        return;
    }
    const emptyState = document.getElementById("recently-played-empty");
    if(emptyState)
    {
        emptyState.remove();
    }
    const existingCard = grid.querySelector('[data-song-id="' + normalizedSongId + '"]');
    if(existingCard)
    {
        existingCard.remove();
    }
    const card = document.createElement("div");
    card.className =
    "group flex items-center gap-4 bg-[#1f1f1f] hover:bg-[#2a2a2a] p-4 rounded-xl transition-all duration-300";
    card.setAttribute("data-song-id", normalizedSongId);
    card.innerHTML =
    '<div class="relative">' +
    '<img src="../../assets/uploads/covers/' + escapeHtml(song.cover_image) + '" class="w-16 h-16 rounded-lg object-cover">' +
    '<button class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition-all duration-300 rounded-lg flex items-center justify-center">' +
    '<span class="material-symbols-outlined text-white">play_arrow</span>' +
    '</button>' +
    '</div>' +
    '<div class="flex-1 overflow-hidden">' +
    '<h4 class="text-white font-semibold truncate">' + escapeHtml(song.song_title) + '</h4>' +
    '<p class="text-gray-400 text-sm truncate">' + escapeHtml(song.artist_name) + '</p>' +
    '</div>' +
    '<div class="flex items-center gap-2">' +
    '<button type="button" class="like-toggle-btn w-10 h-10 rounded-full border border-white/10 text-white transition hover:border-[#53e076] hover:text-[#53e076]" data-song-id="' + normalizedSongId + '" data-liked="0">' +
    '<span class="material-symbols-outlined">favorite</span>' +
    '</button>' +
    '<button type="button" class="download-toggle-btn w-10 h-10 rounded-full border border-white/10 text-white transition hover:border-[#53e076] hover:text-[#53e076]" data-song-id="' + normalizedSongId + '" data-downloaded="0">' +
    '<span class="material-symbols-outlined">download</span>' +
    '</button>' +
    '</div>';
    const playButton = card.querySelector("button");
    playButton.addEventListener("click", function()
    {
        playSingleSong(
        song.song_id,
        song.song_title,
        song.artist_name,
        song.song_file,
        song.cover_image,
        "recentlyPlayed"
        );
    });
    grid.prepend(card);
    wireSongActionButtons(card);
    songSections.recentlyPlayed = cloneSongs([song].concat(songSections.recentlyPlayed || [])).filter(function(item, index, list)
    {
        return list.findIndex(function(candidate)
        {
            return getSongId(candidate) === getSongId(item);
        }) === index;
    }).slice(0, 6);
    while(grid.children.length > 6)
    {
        grid.removeChild(grid.lastElementChild);
    }
}
function escapeHtml(value)
{
    return String(value).replace(/[&<>"']/g, function(character)
    {
        const entities = {
            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            '"': "&quot;",
            "'": "&#39;"
        };
        return entities[character];
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
        currentSectionKey: currentSectionKey,
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
        const volumeControl = document.querySelector('input[type="range"][oninput="changeVolume(this.value)"]');
        if(!Array.isArray(playerState.songs) || playerState.songs.length === 0)
        {
            return;
        }
        songs = playerState.songs;
        currentSectionKey = typeof playerState.currentSectionKey === "string"
            ? playerState.currentSectionKey
            : null;
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

function wireSongActionButtons(scope)
{
    (scope || document).querySelectorAll(".like-toggle-btn").forEach(function(button)
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

    (scope || document).querySelectorAll(".download-toggle-btn").forEach(function(button)
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
window.addEventListener("beforeunload", function()
{
    persistListeningTime(true);
    persistPlayerState();
    pingListenerActivity(true);
});

window.addEventListener("load", function()
{
    restorePlayerState();
    wireSongActionButtons(document);
    pingListenerActivity(false);
});

window.setInterval(function()
{
    pingListenerActivity(false);
}, 60000);
</script>
</body>
</html>
