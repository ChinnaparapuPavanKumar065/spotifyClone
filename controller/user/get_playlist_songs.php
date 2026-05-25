<?php
session_start();
include("../../db_config.php");
include("../shared/user_playlists.php");

header("Content-Type: application/json");

if(!isset($_SESSION['user_id']))
{
    echo json_encode([]);
    exit();
}

ensureUserPlaylistSchema($conn);

$escaped_canonical_user_id = getEscapedCanonicalPlaylistUserId($conn, $_SESSION['user_id']);

if(!$escaped_canonical_user_id)
{
    echo json_encode([]);
    exit();
}

if(isset($_GET['playlist_id']))
{
    $playlist_id = (int) $_GET['playlist_id'];

    $songs = [];
    $playlistQuery = mysqli_query($conn,
    "SELECT id
    FROM playlists
    WHERE id='$playlist_id'
    AND " . getPlaylistAccessCondition($escaped_canonical_user_id) . "
    LIMIT 1");

    if(!$playlistQuery || mysqli_num_rows($playlistQuery) === 0)
    {
        echo json_encode([]);
        exit();
    }

    $query = mysqli_query($conn,
    "SELECT songs.*, songs.id AS song_id
    FROM playlist_songs
    INNER JOIN songs
    ON playlist_songs.song_id = songs.id
    WHERE playlist_songs.playlist_id='$playlist_id'
    ORDER BY playlist_songs.id DESC");

    while($row = mysqli_fetch_assoc($query))
    {
        $songs[] = $row;
    }

    echo json_encode($songs);
}
?>
