<?php
session_start();
include("../../db_config.php");
if(!isset($_SESSION['admin_id']))
{
    header("Location: admin_login.php");
    exit();
}
if(!isset($_GET['id']))
{
    header("Location: admin_manage_playlists.php");
    exit();
}
$id = $_GET['id'];
$playlist_query = mysqli_query($conn," SELECT * FROM playlists WHERE id='$id' ");
$playlist = mysqli_fetch_assoc($playlist_query);
if($playlist)
{
    $image_path = "../../assets/uploads/playlists/" . $playlist['cover_image'];
    if(file_exists($image_path))
    {
        unlink($image_path);
    }
    $delete_query = mysqli_query($conn," DELETE FROM playlists WHERE id='$id' ");
    if($delete_query)
    {
        echo "
            <script>
                alert('Playlist deleted successfully');
                window.location.href='admin_manage_playlists.php';
            </script>
        ";
    }
    else
    {
        echo "
            <script>
                alert('Delete failed');
                window.history.back();
            </script>
        ";
    }
}
else
{
    echo "
        <script>
            alert('Playlist not found');
            window.location.href='admin_manage_playlists.php';
        </script>
    ";
}
?>