<?php
session_start();
include("../../db_config.php");
include("../shared/recently_played.php");
header("Content-Type: application/json");
ensureRecentlyPlayedSchema($conn);

if(isset($_SESSION['user_id']) &&
   isset($_POST['song_id']))
{
    $session_user_id = mysqli_real_escape_string($conn, $_SESSION['user_id']);
    $song_id = (int) $_POST['song_id'];

    if($song_id <= 0)
    {
        echo json_encode([
            "success" => false,
            "message" => "Invalid song id."
        ]);
        exit();
    }

    $song_exists = mysqli_query($conn,
    "SELECT id FROM songs WHERE id='$song_id' LIMIT 1");

    if(!$song_exists || mysqli_num_rows($song_exists) === 0)
    {
        echo json_encode([
            "success" => false,
            "message" => "Song not found."
        ]);
        exit();
    }

    $user_result = mysqli_query($conn,
    "SELECT id, user_id FROM users
    WHERE user_id='$session_user_id'
    LIMIT 1");

    if(!$user_result || mysqli_num_rows($user_result) === 0)
    {
        echo json_encode([
            "success" => false,
            "message" => "User not found."
        ]);
        exit();
    }

    $user_row = mysqli_fetch_assoc($user_result);
    $canonical_user_id = mysqli_real_escape_string($conn, $user_row['user_id']);
    $legacy_numeric_user_id = (int) $user_row['id'];

    $candidate_user_ids = ["'" . $canonical_user_id . "'"];

    if($legacy_numeric_user_id > 0)
    {
        $candidate_user_ids[] = "'" . $legacy_numeric_user_id . "'";
    }

    $candidate_user_filter = implode(",", array_unique($candidate_user_ids));

    mysqli_query($conn,
    "UPDATE recently_played
    SET user_id='$canonical_user_id'
    WHERE user_id IN ($candidate_user_filter)");

    mysqli_query($conn,
    "DELETE FROM recently_played
    WHERE user_id='$canonical_user_id'
    AND song_id='$song_id'");

    $insert_recent = mysqli_query($conn,
    "INSERT INTO recently_played(user_id,song_id,played_at)
    VALUES('$canonical_user_id','$song_id',NOW())");

    if(!$insert_recent)
    {
        echo json_encode([
            "success" => false,
            "message" => mysqli_error($conn)
        ]);
        exit();
    }

    mysqli_query($conn,
    "DELETE FROM recently_played
    WHERE user_id='$canonical_user_id'
    AND id NOT IN
    (
        SELECT id
        FROM
        (
            SELECT id
            FROM recently_played
            WHERE user_id='$canonical_user_id'
            ORDER BY played_at DESC, id DESC
            LIMIT 6
        ) AS latest_recent
    )");

    echo json_encode([
        "success" => true,
        "song_id" => $song_id,
        "user_id" => $canonical_user_id
    ]);
    exit();
}

echo json_encode([
    "success" => false,
    "message" => "Missing session or song id."
]);
?>