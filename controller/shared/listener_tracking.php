<?php
function ensureListenerTrackingSchema($conn)
{
    $activeListenersColumn = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'active_listeners'");
    if($activeListenersColumn && mysqli_num_rows($activeListenersColumn) === 0)
    {
        mysqli_query($conn, "ALTER TABLE users ADD COLUMN active_listeners TINYINT(1) NOT NULL DEFAULT 0");
    }
    $lastActivityColumn = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'last_activity_at'");
    if($lastActivityColumn && mysqli_num_rows($lastActivityColumn) === 0)
    {
        mysqli_query($conn, "ALTER TABLE users ADD COLUMN last_activity_at DATETIME NULL DEFAULT NULL");
    }
    mysqli_query($conn,
    "CREATE TABLE IF NOT EXISTS user_listening_history
    (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id VARCHAR(255) NOT NULL,
        song_id INT NOT NULL,
        seconds_played INT NOT NULL DEFAULT 0,
        played_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_played_at (user_id, played_at),
        INDEX idx_song_id (song_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
function getCanonicalSessionUser($conn, $sessionUserId)
{
    $escapedSessionUserId = mysqli_real_escape_string($conn, $sessionUserId);
    $userQuery = mysqli_query($conn,
    "SELECT id, user_id, username
    FROM users
    WHERE user_id='$escapedSessionUserId'
    LIMIT 1");
    if(!$userQuery || mysqli_num_rows($userQuery) === 0)
    {
        return null;
    }
    return mysqli_fetch_assoc($userQuery);
}
function markUserListenerState($conn, $sessionUserId, $isActive)
{
    $user = getCanonicalSessionUser($conn, $sessionUserId);
    if(!$user)
    {
        return null;
    }
    $canonicalUserId = mysqli_real_escape_string($conn, $user['user_id']);
    $activeValue = $isActive ? 1 : 0;
    $activityValue = $isActive ? "NOW()" : "NULL";
    $loggedInValue = $isActive ? 1 : 0;
    mysqli_query($conn,
    "UPDATE users
    SET active_listeners='$activeValue',
        is_logged_in='$loggedInValue',
        last_activity_at=$activityValue
    WHERE user_id='$canonicalUserId'");
    return $user;
}