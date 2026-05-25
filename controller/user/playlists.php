<?php
session_start();
include("../../db_config.php");
include("../shared/user_playlists.php");
if(!isset($_SESSION['user_id']))
{
    header("Location: user_login.php");
    exit();
}
ensureUserPlaylistSchema($conn);
$search = "";
if(isset($_GET['search']))
{
    $search = trim($_GET['search']);
}
$escaped_search = mysqli_real_escape_string($conn, $search);
$playlists = [];
$songs = [];
$playlist_error = "";
$playlist_success = "";
$user_id = $_SESSION['user_id'];
$user_query = mysqli_query($conn,"SELECT * FROM users WHERE user_id='$user_id'");
$user_data = mysqli_fetch_assoc($user_query);
$user_name = $user_data['username'];
$escaped_canonical_user_id = getEscapedCanonicalPlaylistUserId($conn, $_SESSION['user_id']);

if(!$escaped_canonical_user_id)
{
    header("Location: user_login.php");
    exit();
}

if(isset($_POST['create_playlist']))
{
    $playlist_name = mysqli_real_escape_string($conn, trim($_POST['playlist_name']));
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));
    $cover_image_name = "sond.jpg";

    if($playlist_name === "")
    {
        $playlist_error = "Playlist name is required.";
    }
    else
    {
        if(isset($_FILES['playlist_image']) &&
           isset($_FILES['playlist_image']['tmp_name']) &&
           $_FILES['playlist_image']['tmp_name'] !== "")
        {
            $upload_directory = "../../assets/uploads/playlists/";

            if(!file_exists($upload_directory))
            {
                mkdir($upload_directory, 0777, true);
            }

            $cover_image_name = time() . "_" . basename($_FILES['playlist_image']['name']);
            move_uploaded_file(
                $_FILES['playlist_image']['tmp_name'],
                $upload_directory . $cover_image_name
            );
        }

        $insert_playlist = mysqli_query($conn,
        "INSERT INTO playlists(
            playlist_name,
            description,
            cover_image,
            is_public,
            owner_user_id,
            created_at
        )
        VALUES(
            '$playlist_name',
            '$description',
            '$cover_image_name',
            '0',
            '$escaped_canonical_user_id',
            NOW()
        )");

        if($insert_playlist)
        {
            $new_playlist_id = mysqli_insert_id($conn);
            header("Location: open_playlist.php?id=$new_playlist_id");
            exit();
        }

        $playlist_error = "Unable to create playlist right now.";
    }
}

$owned_playlist_condition = getOwnedPlaylistCondition($escaped_canonical_user_id);
if($search !== "")
{
    $playlists_query = mysqli_query($conn,
    "SELECT *
    FROM playlists
    WHERE $owned_playlist_condition
    AND (
        playlist_name LIKE '%$escaped_search%'
        OR description LIKE '%$escaped_search%'
    )
    ORDER BY id DESC");
}
else
{
    $playlists_query = mysqli_query($conn,
    "SELECT *
    FROM playlists
    WHERE $owned_playlist_condition
    ORDER BY id DESC
    LIMIT 12");
}

if($playlists_query)
{
    while($playlist = mysqli_fetch_assoc($playlists_query))
    {
        $playlists[] = $playlist;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Playlist - Melodix</title>
    <link rel="icon" type="image/x-icon" href="../logo.png"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;700;800;900&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
    <style>
        body{
            font-family:'Montserrat',sans-serif;
            background:#131313;
            color:#fff;
            overflow-x:hidden;
        }
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
        .sidebar-bottom{
            margin-top:270px; 
            color:"#158e46";   
        }
        .search-box{
            position:relative;
        }
        .search-box span{
            position:absolute;
            left:24px;
            top:50%;
            transform:translateY(-50%);
            color:#909090;
        }
        .search-box input{
            width:100%;
            height:62px;
            border:none;
            border-radius:18px;
            background:#1d1d1d;
            color:#fff;
            padding:0 22px 0 56px;
            font-size:16px;
        }
        .search-box input:focus{
            outline:2px solid rgba(83,224,118,0.55);
            outline-offset:2px;
        }
        .song-card:hover .play-overlay{
            opacity:1;
            transform:translateY(0);
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
<nav class="fixed top-0 right-0 w-full z-50 flex justify-between items-center h-16 px-8 bg-[#131313]/60 backdrop-blur-3xl">
    <a href="index.php">
        <div class="flex items-center gap-2">
            <span class="text-3xl font-black text-[#53e076]">Melodix</span>
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
</div>
</nav>

<aside class="hidden lg:flex flex-col p-6 gap-3 h-screen w-64 fixed left-0 top-0 bg-[#181818]/60 backdrop-blur-md border-r border-white/10 z-40 shadow-xl">
    <div class="mb-5"></div>
    <nav class="flex flex-col gap-4">
        <a class="flex items-center gap-3 p-2 text-gray-400 hover:text-[#53e076]" href="index.php">
            <span class="material-symbols-outlined">home</span>
            <span>Home</span>
        </a>
        <a class="flex items-center gap-3 p-2 text-gray-400 hover:text-[#53e076]" href="search.php">
            <span class="material-symbols-outlined">search</span>
            <span>Search</span>
        </a>
        <a class="flex items-center gap-3 p-2 text-white font-bold hover:text-[#53e076]" href="playlists.php">
            <span class="material-symbols-outlined">playlist_add</span>
            <span>Playlist</span>
        </a>
        <a class="flex items-center gap-3 p-2 text-gray-400 hover:text-[#53e076]" href="library.php">
            <span class="material-symbols-outlined">library_music</span>
            <span>Library</span>
        </a>
    </nav>

</aside>
<main class="lg:ml-64 pt-16 pb-[120px] min-h-screen bg-[#131313]">
    <section class="relative px-8 py-14 overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-[#53e076]/10 via-[#131313] to-[#131313] -z-10"></div>
        <div class="absolute top-0 right-0 -translate-y-1/4 translate-x-1/4 w-[560px] h-[560px] bg-[#53e076]/15 rounded-full blur-[120px] -z-20"></div>
        <div class="max-w-5xl">
            <h1 class="mt-4 text-5xl md:text-6xl font-black leading-[0.95]">
                Create and open
                <span class="text-gradient">your private playlists.</span>
            </h1>
            <form id="search-form" method="GET" class="search-box mt-12 max-w-5xl">
                <span class="material-symbols-outlined">search</span>
                <input id="search-input" type="text" name="search" placeholder="Search your playlists and public playlists" value="<?php echo htmlspecialchars($search); ?>"
                autocomplete="on"  
                list="search-history-list" 
>
                <datalist id="search-history-list"></datalist>
            </form>
        </div>
    </section>

    <section class="px-8 py-6">
        <div class="mb-12 rounded-[28px] border border-white/10 bg-[#171717] p-6 md:p-8">
            <div class="flex flex-col gap-8 lg:flex-row lg:items-start lg:justify-between">
                <div class="max-w-xl">
                    <p class="text-sm font-bold uppercase tracking-[0.3em] text-[#53e076]">Private Access</p>
                    <h3 class="mt-3 text-3xl font-bold">Create a playlist   </h3>
                </div>
                <form method="POST" enctype="multipart/form-data" class="w-full max-w-xl space-y-4">
                    <?php if($playlist_error !== "") { ?>
                        <div class="rounded-2xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-200">
                            <?php echo htmlspecialchars($playlist_error); ?>
                        </div>
                    <?php } ?>
                    <div class="grid gap-4 md:grid-cols-2">
                        <input
                        type="text"
                        name="playlist_name"
                        placeholder="Playlist name"
                        required
                        class="h-14 rounded-2xl border border-white/10 bg-[#1f1f1f] px-4 text-white placeholder:text-gray-500 focus:border-[#53e076] focus:outline-none">
                        <input
                        type="file"
                        name="playlist_image"
                        accept="image/*"
                        class="h-14 rounded-2xl border border-white/10 bg-[#1f1f1f] px-4 py-4 text-sm text-gray-400 focus:border-[#53e076] focus:outline-none">
                    </div>
                    <textarea
                    name="description"
                    rows="4"
                    placeholder="Description"
                    class="w-full rounded-2xl border border-white/10 bg-[#1f1f1f] px-4 py-4 text-white placeholder:text-gray-500 focus:border-[#53e076] focus:outline-none"></textarea>
                    <button
                    type="submit"
                    name="create_playlist"
                    class="inline-flex h-14 items-center gap-2 rounded-full bg-[#53e076] px-8 text-base font-bold text-black transition hover:scale-105 hover:bg-[#6af08a]">
                        <span class="material-symbols-outlined">playlist_add</span>
                        Create Playlist
                    </button>
                </form>
            </div>
        </div>

        <div class="mb-12">
            <div class="flex justify-between items-end mb-6">
                <div>
                    <h3 class="text-2xl font-bold">Your playlists and public playlists</h3>
                </div>
            </div>

            <?php if(count($playlists) > 0) { ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-6">
                    <?php foreach($playlists as $playlist) { ?>
                        <a
                        href="open_playlist.php?id=<?php echo (int) $playlist['id']; ?>"
                        class="group block rounded-[24px] bg-[#1b1b1b] p-5 transition-all duration-300 hover:bg-[#242424] shadow-[0_12px_30px_rgba(0,0,0,0.25)]"
                        >
                            <div class="relative overflow-hidden rounded-2xl">
                                <img
                                src="../../assets/uploads/playlists/<?php echo htmlspecialchars($playlist['cover_image']); ?>"
                                alt="<?php echo htmlspecialchars($playlist['playlist_name']); ?>"
                                class="w-full aspect-square object-cover"
                                >
                                <div
                                class="absolute bottom-4 right-4 flex h-12 w-12 items-center justify-center rounded-full bg-[#53e076] shadow-lg transition-all duration-300 hover:scale-110">
                                    <span class="material-symbols-outlined text-black">play_arrow</span>
                                </div>
                            </div>
                            <div class="mt-4 min-w-0 flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <div class="mb-2">
                                        <span class="rounded-full px-3 py-1 text-[11px] font-bold uppercase tracking-[0.2em] <?php echo (!isset($playlist['owner_user_id']) || $playlist['owner_user_id'] === null || $playlist['owner_user_id'] === '') ? 'bg-white/10 text-gray-300' : 'bg-[#53e076]/15 text-[#53e076]'; ?>">
                                            <?php echo (!isset($playlist['owner_user_id']) || $playlist['owner_user_id'] === null || $playlist['owner_user_id'] === '') ? 'Public' : 'Only you'; ?>
                                        </span>
                                    </div>
                                    <h4 class="text-lg font-bold truncate"><?php echo htmlspecialchars($playlist['playlist_name']); ?></h4>
                                    <p class="text-sm text-gray-400 mt-2 line-clamp-2">
                                        <?php echo htmlspecialchars($playlist['description'] ?: 'Open this playlist to listen.'); ?>
                                    </p>
                                </div>
                            </div>
                        </a>
                    <?php } ?>
                </div>
            <?php } else { ?>
                <div class="rounded-[28px] border border-white/8 bg-[#171717] px-6 py-10 text-center text-gray-400">
                    No playlists matched your search.
                </div>
            <?php } ?>
        </div>
    </section>
</main>

<footer class="fixed bottom-0 left-0 w-full h-[90px] z-50 glass-panel bg-[#181818]/95 border-t border-white/10 shadow-[0_-8px_24px_rgba(0,0,0,0.5)] backdrop-blur-3xl">
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
const SEARCH_HISTORY_KEY = "melodixSearchHistory";
const LISTENER_ACTIVITY_URL = "update_listener_activity.php";
const LISTENING_TIME_URL = "save_listening_time.php";
let songs = <?php echo json_encode($songs, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?> || [];
let currentSongIndex = 0;
let listeningSecondsPending = 0;
let lastTrackedAudioTime = 0;

const audioPlayer = document.getElementById("audio-player");
const playIcon = document.getElementById("play-icon");
const volumeControl = document.getElementById("volume-control");
const searchForm = document.getElementById("search-form");
const searchInput = document.getElementById("search-input");
const searchHistoryList = document.getElementById("search-history-list");

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

function playSingleSong(songId, title, artist, file, cover)
{
    const fallbackSong = {
        song_id: songId,
        song_title: title,
        artist_name: artist,
        song_file: file,
        cover_image: cover
    };
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
        const playerState = localStorage.getItem(PLAYER_STATE_KEY);
        let restoredSongs = [];

        if(playerState)
        {
            try
            {
                const parsedState = JSON.parse(playerState);

                if(Array.isArray(parsedState.songs))
                {
                    restoredSongs = parsedState.songs.filter(function(song)
                    {
                        return Number(song.song_id || song.id || 0) > 0;
                    }).map(function(song)
                    {
                        return Object.assign({}, song);
                    });
                }
            }
            catch(error)
            {
                console.error(error);
            }
        }

        if(restoredSongs.length === 0)
        {
            restoredSongs = [fallbackSong];
        }
        else if(restoredSongs.findIndex(function(song)
        {
            return Number(song.song_id || song.id) === Number(songId);
        }) < 0)
        {
            restoredSongs.unshift(fallbackSong);
        }

        songs = restoredSongs;
        currentSongIndex = songs.findIndex(function(song)
        {
            return Number(song.song_id || song.id) === Number(songId);
        });

        if(currentSongIndex < 0)
        {
            currentSongIndex = 0;
        }
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

function getSearchHistory()
{
    try
    {
        const savedHistory = JSON.parse(localStorage.getItem(SEARCH_HISTORY_KEY));

        if(!Array.isArray(savedHistory))
        {
            return [];
        }

        return savedHistory.filter(function(item)
        {
            return typeof item === "string" && item.trim() !== "";
        });
    }
    catch(error)
    {
        console.error(error);
        return [];
    }
}

function renderSearchHistory()
{
    const historyItems = getSearchHistory();

    searchHistoryList.innerHTML = "";

    if(historyItems.length === 0)
    {
        return;
    }

    historyItems.forEach(function(item)
    {
        const option = document.createElement("option");
        option.value = item;
        searchHistoryList.appendChild(option);
    });
}

function saveSearchHistory()
{
    const searchValue = searchInput.value.trim();

    if(searchValue === "")
    {
        return;
    }

    const historyItems = getSearchHistory().filter(function(item)
    {
        return item.toLowerCase() !== searchValue.toLowerCase();
    });

    historyItems.unshift(searchValue);

    localStorage.setItem(
        SEARCH_HISTORY_KEY,
        JSON.stringify(historyItems.slice(0, 8))
    );
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

searchForm.addEventListener("submit", function()
{
    saveSearchHistory();
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
    renderSearchHistory();
    pingListenerActivity(false);
});

window.setInterval(function()
{
    pingListenerActivity(false);
}, 60000);
</script>
</body>
</html>
