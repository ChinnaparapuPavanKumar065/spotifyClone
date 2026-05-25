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
if(isset($_POST['update_playlist']))
{ 
    $playlist_name = mysqli_real_escape_string( $conn, $_POST['playlist_name'] );
    $description = mysqli_real_escape_string($conn,$_POST['description']);
    $status = (int) $_POST['status'];
    $cover_image = $playlist['cover_image'];
    if($_FILES['playlist_image']['name'] != "")
    {
        $playlist_folder = "../../assets/uploads/playlists/";
        $cover_image = time() . "_" . $_FILES['playlist_image']['name'];
        $tmp_name = $_FILES['playlist_image']['tmp_name'];
        move_uploaded_file(
            $tmp_name,
            $playlist_folder . $cover_image
        );
    }
    $update_query = mysqli_query($conn,"UPDATE playlists SET playlist_name = '$playlist_name', description = '$description', cover_image = '$cover_image', is_public = '$status' WHERE id='$id' ");
    if($update_query)
    {
        echo "
            <script>
                alert('Playlist updated successfully');
                window.location.href='admin_manage_playlists.php';
            </script>
        ";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" >
    <title>Edit Playlist</title>
    <link rel="icon" type="image/x-icon" href="../logo.png"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" >
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;700;800;900&display=swap" rel="stylesheet" >
    <style>
        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
        }
        body{
            background:#0f0f0f;
            color:#fff;
            font-family:'Montserrat',sans-serif;
            min-height:100vh;
            display:flex;
            justify-content:center;
            align-items:center;
            padding:40px;
        }
        .form-container{
            width:100%;
            max-width:750px;
            background:#181818;
            border-radius:30px;
            padding:45px;
        }
        .page-title{
            font-size:55px;
            font-weight:800;
            margin-bottom:10px;
        }
        .page-subtitle{
            color:#888;
            margin-bottom:40px;
            font-size:17px;
        }
        .form-group{
            margin-bottom:25px;
        }
        .form-label{
            display:block;
            margin-bottom:12px;
            font-weight:700;
            font-size:15px;
        }
        .form-control{
            width:100%;
            height:65px;
            background:#242424;
            border:none;
            border-radius:18px;
            padding:0 20px;
            color:#fff;
            font-size:16px;
        }
        textarea.form-control{
            height:160px;
            resize:none;
            padding-top:20px;
        }
        .form-control:focus{
            outline:none;
            background:#2d2d2d;
            color:#fff;
            box-shadow:none;
        }
        .submit-btn{
            width:100%;
            height:68px;
            background:#1db954;
            color:#000;
            border:none;
            border-radius:22px;
            font-size:18px;
            font-weight:800;
            transition:0.3s;
        }
        .submit-btn:hover{
            background:#1ed760;
        }
        .preview-image{
            width:140px;
            height:140px;
            border-radius:20px;
            object-fit:cover;
            margin-bottom:20px;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h1 class="page-title">Edit Playlist</h1>
        <p class="page-subtitle">Update playlist details</p>
        <form method="POST" enctype="multipart/form-data" >
            <img src="../../assets/uploads/playlists/<?php echo $playlist['cover_image']; ?>" class="preview-image" >
            <div class="form-group">
                <label class="form-label">Playlist Name</label>
                <input type="text" name="playlist_name" class="form-control" value="<?php echo $playlist['playlist_name']; ?>" required > 
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" ><?php echo $playlist['description']; ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Change Cover Image</label>
                <input type="file" name="playlist_image" class="form-control" >
            </div>
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <option value="1" <?php if($playlist['is_public'] == 1){ echo "selected"; } ?>>
                        Public
                    </option>
                    <option value="0"<?php if($playlist['is_public'] == 0){ echo "selected"; } ?>>
                        Private
                    </option>
                </select>
            </div>
            <button type="submit" name="update_playlist" class="submit-btn">Update Playlist</button>
        </form>
    </div>
</body>
</html>