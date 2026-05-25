<?php
function ensureRecentlyPlayedSchema($conn)
{
    mysqli_query($conn,
    "CREATE TABLE IF NOT EXISTS recently_played
    (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id VARCHAR(255) NOT NULL,
        song_id INT NOT NULL,
        played_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_recently_played_user_time (user_id, played_at),
        INDEX idx_recently_played_song (song_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}
?>