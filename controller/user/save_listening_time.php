<?php
session_start();
include("../../db_config.php");
include("../shared/listener_tracking.php");
header("Content-Type: application/json");

ensureListenerTrackingSchema($conn);

if(!isset($_SESSION['user_id']) ||
   !isset($_POST['song_id']) ||
   !isset($_POST['seconds_played']))
{
    echo json_encode([
        "success" => false,
        "message" => "Missing session or playback data."
    ]);
    exit();
}

$user = getCanonicalSessionUser($conn, $_SESSION['user_id']);
$song_id = (int) $_POST['song_id'];
$seconds_played = (int) round((float) $_POST['seconds_played']);

if(!$user)
{
    echo json_encode([
        "success" => false,
        "message" => "User not found."
    ]);
    exit();
}

if($song_id <= 0 || $seconds_played <= 0)
{
    echo json_encode([
        "success" => false,
        "message" => "Invalid playback data."
    ]);
    exit();
}

$songExists = mysqli_query($conn, "SELECT id FROM songs WHERE id='$song_id' LIMIT 1");

if(!$songExists || mysqli_num_rows($songExists) === 0)
{
    echo json_encode([
        "success" => false,
        "message" => "Song not found."
    ]);
    exit();
}

$canonicalUserId = mysqli_real_escape_string($conn, $user['user_id']);

mysqli_query($conn,
"INSERT INTO user_listening_history(user_id, song_id, seconds_played, played_at)
VALUES('$canonicalUserId', '$song_id', '$seconds_played', NOW())");

markUserListenerState($conn, $_SESSION['user_id'], true);

echo json_encode([
    "success" => true,
    "song_id" => $song_id,
    "seconds_played" => $seconds_played
]);
?>
