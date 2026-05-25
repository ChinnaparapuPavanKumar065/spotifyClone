<?php
include("../../db_config.php");
if(isset($_GET['id']))
{
    $id = $_GET['id'];
    $song_query = mysqli_query($conn," SELECT * FROM songs WHERE id='$id'");
    $song = mysqli_fetch_assoc($song_query);
    $song_path = "../../assets/uploads/songs/" . $song['song_file'];

    if(file_exists($song_path))
    {
        unlink($song_path);
    }
    $cover_path = "../../assets/uploads/covers/" . $song['cover_image'];
    if(file_exists($cover_path))
    {
        unlink($cover_path);
    }
    mysqli_query($conn,"DELETE FROM songs WHERE id='$id'");
    echo "
    <script>
        alert('Song deleted successfully');
        window.location.href='admin_manage_songs.php';
    </script>
    ";
}
else
{
    header('Location: admin_manage_songs.php');
}
?>