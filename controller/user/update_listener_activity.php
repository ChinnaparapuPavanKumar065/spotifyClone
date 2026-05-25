<?php
session_start();
include("../../db_config.php");
include("../shared/listener_tracking.php");
header("Content-Type: application/json");

ensureListenerTrackingSchema($conn);

if(!isset($_SESSION['user_id']))
{
    echo json_encode([
        "success" => false,
        "message" => "Missing session."
    ]);
    exit();
}

$user = markUserListenerState($conn, $_SESSION['user_id'], true);

if(!$user)
{
    echo json_encode([
        "success" => false,
        "message" => "User not found."
    ]);
    exit();
}

echo json_encode([
    "success" => true,
    "user_id" => $user['user_id']
]);
?>