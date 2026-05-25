<?php
function ensureUserPlaylistSchema($conn)
{
    $ownerColumn = mysqli_query($conn, "SHOW COLUMNS FROM playlists LIKE 'owner_user_id'");
    if($ownerColumn && mysqli_num_rows($ownerColumn) === 0)
    {
        mysqli_query($conn, "ALTER TABLE playlists ADD COLUMN owner_user_id VARCHAR(255) NULL DEFAULT NULL AFTER is_public");
    }
}
function getEscapedCanonicalPlaylistUserId($conn, $sessionUserId)
{
    $escapedSessionUserId = mysqli_real_escape_string($conn, $sessionUserId);
    $userQuery = mysqli_query($conn,"SELECT user_id FROM users WHERE user_id='$escapedSessionUserId' LIMIT 1");
    if(!$userQuery || mysqli_num_rows($userQuery) === 0)
    {
        return null;
    }
    $user = mysqli_fetch_assoc($userQuery);
    return mysqli_real_escape_string($conn, $user['user_id']);
}
function getPlaylistVisibilityCondition($escapedCanonicalUserId)
{
    return "(playlists.owner_user_id IS NULL OR playlists.owner_user_id='' OR playlists.owner_user_id='$escapedCanonicalUserId')";
}
function getPlaylistAccessCondition($escapedCanonicalUserId)
{
    return "(owner_user_id IS NULL OR owner_user_id='' OR owner_user_id='$escapedCanonicalUserId')";
}
function getOwnedPlaylistCondition($escapedCanonicalUserId)
{
    return "owner_user_id='$escapedCanonicalUserId'";
}
?>