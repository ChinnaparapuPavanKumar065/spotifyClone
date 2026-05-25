<?php
session_start();
include("../../db_config.php");
include("../shared/user_library.php");

header("Content-Type: application/json");

if(!isset($_SESSION['user_id']) || !isset($_POST['song_id']))
{
    echo json_encode([
        "success" => false,
        "message" => "Missing session or song id."
    ]);
    exit();
}

ensureUserLibrarySchema($conn);

$song_id = (int) $_POST['song_id'];

if($song_id <= 0)
{
    echo json_encode([
        "success" => false,
        "message" => "Invalid song id."
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

$user = getCanonicalLibraryUser($conn, $_SESSION['user_id']);

if(!$user)
{
    echo json_encode([
        "success" => false,
        "message" => "User not found."
    ]);
    exit();
}

$canonicalUserId = $user['user_id'];
$candidateUserIds = getLibraryCandidateUserIds($user);

normalizeLibraryOwnership($conn, "user_downloads", $canonicalUserId, $candidateUserIds);

$escapedCanonicalUserId = mysqli_real_escape_string($conn, $canonicalUserId);
$existingDownload = mysqli_query($conn,
"SELECT id
FROM user_downloads
WHERE user_id='$escapedCanonicalUserId'
AND song_id='$song_id'
LIMIT 1");

$isDownloaded = false;

if($existingDownload && mysqli_num_rows($existingDownload) > 0)
{
    mysqli_query($conn,
    "DELETE FROM user_downloads
    WHERE user_id='$escapedCanonicalUserId'
    AND song_id='$song_id'");
}
else
{
    mysqli_query($conn,
    "INSERT INTO user_downloads(user_id, song_id, created_at)
    VALUES('$escapedCanonicalUserId', '$song_id', NOW())");
    $isDownloaded = true;
}

echo json_encode([
    "success" => true,
    "downloaded" => $isDownloaded,
    "song_id" => $song_id
]);
exit();
?>