<?php
include("../../db_config.php");
if(isset($_POST['song_title']))
{
    $song_title  = mysqli_real_escape_string($conn, $_POST['song_title']);
    $artist_name = mysqli_real_escape_string($conn, $_POST['artist_name']);
    $album_name  = mysqli_real_escape_string($conn, $_POST['album_name']);
    $genre       = mysqli_real_escape_string($conn, $_POST['genre']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $song_folder  = "../../assets/uploads/songs/";
    $image_folder = "../../assets/uploads/covers/";
    if(!file_exists($song_folder))
    {
        mkdir($song_folder,0777,true);
    }
    if(!file_exists($image_folder))
    {
        mkdir($image_folder,0777,true);
    }
    $song_file_name = time() . "_" . $_FILES['song_file']['name'];
    $song_tmp_name  = $_FILES['song_file']['tmp_name'];
    $song_path      = $song_folder . $song_file_name;
    move_uploaded_file($song_tmp_name, $song_path);
    $cover_image_name = time() . "_" . $_FILES['cover_image']['name'];
    $cover_tmp_name   = $_FILES['cover_image']['tmp_name'];
    $cover_path       = $image_folder . $cover_image_name;
    move_uploaded_file($cover_tmp_name, $cover_path);
    $insert_query = mysqli_query($conn, "
    INSERT INTO songs( song_title, artist_name, album_name, genre, description, song_file, cover_image, created_at)
    VALUES('$song_title','$artist_name','$album_name','$genre','$description','$song_file_name','$cover_image_name',NOW())
    ");    
    if($insert_query)
    {
        echo "
            <script>
                alert('Song uploaded successfully');
                window.location.href='admin_manage_songs.php';
            </script>
            ";
    }
    else
    {
        echo "
            <script>
                alert('Database insert failed');
                window.history.back();
            </script>
            ";
    }
}
?>