<?php
session_start();
include("../../db_config.php");
include("../shared/user_playlists.php");

header("Content-Type: application/json");

if(!isset($_SESSION['user_id']))
{
    echo json_encode([
        "success" => false,
        "message" => "Please sign in first."
    ]);
    exit();
}

ensureUserPlaylistSchema($conn);

$escaped_canonical_user_id = getEscapedCanonicalPlaylistUserId($conn, $_SESSION['user_id']);

if(!$escaped_canonical_user_id)
{
    echo json_encode([
        "success" => false,
        "message" => "User not found."
    ]);
    exit();
}

$song_id = isset($_POST['song_id']) ? (int) $_POST['song_id'] : 0;
$playlist_id = isset($_POST['playlist_id']) ? (int) $_POST['playlist_id'] : 0;

if($song_id <= 0 || $playlist_id <= 0)
{
    echo json_encode([
        "success" => false,
        "message" => "Invalid song or playlist."
    ]);
    exit();
}

$playlist_query = mysqli_query($conn,
"SELECT id
FROM playlists
WHERE id='$playlist_id'
AND " . getOwnedPlaylistCondition($escaped_canonical_user_id) . "
LIMIT 1");

if(!$playlist_query || mysqli_num_rows($playlist_query) === 0)
{
    echo json_encode([
        "success" => false,
        "message" => "You can only add songs to your own playlists."
    ]);
    exit();
}

$song_query = mysqli_query($conn,
"SELECT id
FROM songs
WHERE id='$song_id'
LIMIT 1");

if(!$song_query || mysqli_num_rows($song_query) === 0)
{
    echo json_encode([
        "success" => false,
        "message" => "Song not found."
    ]);
    exit();
}

$existing_query = mysqli_query($conn,
"SELECT id
FROM playlist_songs
WHERE playlist_id='$playlist_id'
AND song_id='$song_id'
LIMIT 1");

if($existing_query && mysqli_num_rows($existing_query) > 0)
{
    echo json_encode([
        "success" => true,
        "message" => "Song is already in this playlist."
    ]);
    exit();
}

$insert_query = mysqli_query($conn,
"INSERT INTO playlist_songs(
    playlist_id,
    song_id
)
VALUES(
    '$playlist_id',
    '$song_id'
)");

if(!$insert_query)
{
    echo json_encode([
        "success" => false,
        "message" => "Unable to add song right now."
    ]);
    exit();
}

echo json_encode([
    "success" => true,
    "message" => "Song added to playlist."
]);
?>
