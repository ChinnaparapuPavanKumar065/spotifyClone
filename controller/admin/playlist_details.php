<?php
session_start();
include("../../db_config.php");
if(!isset($_SESSION['admin_id']))
{
    header("Location: admin_login.php");
    exit();
}
if(!isset($_GET['id']))
{
    header("Location: admin_manage_playlists.php");
    exit();
}
$playlist_id = $_GET['id'];
$playlist_query = mysqli_query($conn," SELECT * FROM playlists WHERE id='$playlist_id' ");
$playlist = mysqli_fetch_assoc($playlist_query);
if(isset($_GET['add_song']))
{
    $song_id = $_GET['add_song'];
    $check_song = mysqli_query($conn,"
    SELECT *
    FROM playlist_songs
    WHERE playlist_id='$playlist_id'
    AND song_id='$song_id'
    ");
    if(mysqli_num_rows($check_song) == 0)
    {
        mysqli_query($conn,"
        INSERT INTO playlist_songs(
        playlist_id,
        song_id
        )
        VALUES(
        '$playlist_id',
        '$song_id'
        )
        ");
    }
    header("Location: playlist_details.php?id=$playlist_id");
}
if(isset($_GET['remove_song']))
{
    $song_id = $_GET['remove_song'];
    mysqli_query($conn,"
    DELETE FROM playlist_songs
    WHERE playlist_id='$playlist_id'
    AND song_id='$song_id'
    ");
    header("Location: playlist_details.php?id=$playlist_id");
}
$songs_query = mysqli_query($conn,"
SELECT *
FROM songs
ORDER BY id DESC
");
$playlist_songs = mysqli_query($conn,"
SELECT songs.*
FROM playlist_songs
JOIN songs
ON playlist_songs.song_id = songs.id
WHERE playlist_songs.playlist_id='$playlist_id'
ORDER BY playlist_songs.id DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/x-icon" href="../logo.png"/>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Playlist Details</title>
    <link rel="icon" type="image/x-icon" href="../logo.png"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <style>
        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
        }
        body{
            background:#0f0f0f;
            color:#fff;
            font-family:'Montserrat',sans-serif;
            padding:40px;
        }
        .playlist-header{
            display:flex;
            align-items:center;
            gap:30px;
            margin-bottom:50px;
        }
        .playlist-image{
            width:220px;
            height:220px;
            border-radius:30px;
            object-fit:cover;
        }
        .playlist-title{
            font-size:60px;
            font-weight:900;
        }
        .playlist-description{
            color:#aaa;
            margin-top:10px;
            font-size:18px;
        }
        .back-btn{
        display:inline-flex;
        align-items:center;
        gap:8px;
        color:#53e076;
        text-decoration:none;
        margin-bottom:30px;
        font-size:14px;
        font-weight:600;
        }
        .back-btn:hover{
        color:#1ed760;
        }
        .section-title{
            font-size:32px;
            font-weight:800;
            margin-bottom:25px;
        }
        .song-table{
            width:100%;
            border-collapse:collapse;
            margin-bottom:60px;
        }
        .song-table th{
            background:#1d1d1d;
            padding:18px;
            text-align:left;
        }
        .song-table td{
            background:#181818;
            padding:18px;
            border-bottom:1px solid #2a2a2a;
        }
        .song-cover{
            width:65px;
            height:65px;
            border-radius:14px;
            object-fit:cover;
        }
        .action-btn{
            width:45px;
            height:45px;
            border-radius:50%;
            display:flex;
            align-items:center;
            justify-content:center;
            text-decoration:none;
            margin:auto;
        }
        .add-btn{
            background:#1db954;
            color:#000;
        }
        .remove-btn{
            background:#ff3b30;
            color:#fff;
        }
        .action-btn:hover{
            opacity:0.9;
        }
    </style>
</head>
<body>
<a href="admin_manage_playlists.php" class="back-btn">
    <span class="material-symbols-outlined">
        arrow_back
    </span>
Back to Manage Playlists
</a>
<div class="playlist-header">
    <img
    src="../../assets/uploads/playlists/<?php echo $playlist['cover_image']; ?>"
    class="playlist-image"
    >
    <div>
        <div class="playlist-title">
            <?php echo $playlist['playlist_name']; ?>
        </div>
        <div class="playlist-description">
            <?php echo $playlist['description']; ?>
        </div>
    </div>
</div>
<div class="section-title">
    Playlist Songs
</div>
<table class="song-table">
<tr>
    <th>
        Cover
    </th>
    <th>
        Track
    </th>
    <th>
        Artist
    </th>
    <th>
    </th>
</tr>
<?php
while($playlist_song = mysqli_fetch_assoc($playlist_songs))
{
?>
<tr>
    <td>
        <img
        src="../../assets/uploads/covers/<?php echo $playlist_song['cover_image']; ?>"
        class="song-cover"
        >
    </td>
    <td>
        <?php echo $playlist_song['song_title']; ?>
    </td>
    <td>
        <?php echo $playlist_song['artist_name']; ?>
    </td>
    <td>
        <a
        href="playlist_details.php?id=<?php echo $playlist_id; ?>&remove_song=<?php echo $playlist_song['id']; ?>"
        class="action-btn remove-btn"
        >
            <span class="material-symbols-outlined">
                delete
            </span>
        </a>
    </td>
</tr>
<?php
}
?>
</table>
<div class="section-title">
    Add Songs
</div>
<table class="song-table">
<tr>
    <th>
        Cover
    </th>
    <th>
        Track
    </th>
    <th>
        Artist
    </th>
    <th>
    </th>
</tr>
<?php
while($song = mysqli_fetch_assoc($songs_query))
{
?>
<tr>
    <td>
        <img
        src="../../assets/uploads/covers/<?php echo $song['cover_image']; ?>"
        class="song-cover"
        >
    </td>
    <td>
        <?php echo $song['song_title']; ?>
    </td>
    <td>
        <?php echo $song['artist_name']; ?>
    </td>
    <td>
        <a
        href="playlist_details.php?id=<?php echo $playlist_id; ?>&add_song=<?php echo $song['id']; ?>"
        class="action-btn add-btn"
        >
            <span class="material-symbols-outlined">
                add
            </span>
        </a>
    </td>
</tr>
<?php
}
?>
</table>
</body>
</html>