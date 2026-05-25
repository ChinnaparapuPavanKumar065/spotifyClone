<?php
function ensureUserLibrarySchema($conn)
{
    mysqli_query($conn,
    "CREATE TABLE IF NOT EXISTS user_liked_songs
    (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id VARCHAR(255) NOT NULL,
        song_id INT NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_song (user_id, song_id),
        INDEX idx_user_created (user_id, created_at),
        INDEX idx_song_id (song_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    mysqli_query($conn,
    "CREATE TABLE IF NOT EXISTS user_downloads
    (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id VARCHAR(255) NOT NULL,
        song_id INT NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_song (user_id, song_id),
        INDEX idx_user_created (user_id, created_at),
        INDEX idx_song_id (song_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
function getCanonicalLibraryUser($conn, $sessionUserId)
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
function getLibraryCandidateUserIds($user)
{
    $candidateUserIds = [];
    if(isset($user['user_id']) && $user['user_id'] !== "")
    {
        $candidateUserIds[] = (string) $user['user_id'];
    }
    if(isset($user['id']) && (int) $user['id'] > 0)
    {
        $candidateUserIds[] = (string) ((int) $user['id']);
    }
    return array_values(array_unique($candidateUserIds));
}
function buildEscapedUserIdList($conn, $userIds)
{
    $escapedUserIds = [];
    foreach($userIds as $userId)
    {
        $escapedUserIds[] = "'" . mysqli_real_escape_string($conn, $userId) . "'";
    }
    return implode(",", $escapedUserIds);
}
function normalizeLibraryOwnership($conn, $tableName, $canonicalUserId, $candidateUserIds)
{
    if($canonicalUserId === "" || count($candidateUserIds) === 0)
    {
        return;
    }
    $escapedCanonicalUserId = mysqli_real_escape_string($conn, $canonicalUserId);
    $candidateUserFilter = buildEscapedUserIdList($conn, $candidateUserIds);
    mysqli_query($conn,"UPDATE $tableName SET user_id='$escapedCanonicalUserId' WHERE user_id IN ($candidateUserFilter)");
}
function getUserSongStateMap($conn, $tableName, $candidateUserIds)
{
    if(count($candidateUserIds) === 0)
    {
        return [];
    }
    $candidateUserFilter = buildEscapedUserIdList($conn, $candidateUserIds);
    $stateMap = [];
    $query = mysqli_query($conn,
    "SELECT song_id FROM $tableName WHERE user_id IN ($candidateUserFilter)");
    if(!$query)
    {
        return [];
    }
    while($row = mysqli_fetch_assoc($query))
    {
        $stateMap[(int) $row['song_id']] = true;
    }
    return $stateMap;
}
?>