<?php
session_start();
include("../../db_config.php");
if(!isset($_SESSION['admin_id']))
{
    header("Location: admin_login.php");
    exit();
}
if(isset($_POST['create_playlist']))
{
    $playlist_name = mysqli_real_escape_string($conn,$_POST['playlist_name']);
    $description = mysqli_real_escape_string($conn,$_POST['description']);
    $status = (int) $_POST['status'];
    $playlist_image = $_FILES['playlist_image']['name'];
    $tmp_name = $_FILES['playlist_image']['tmp_name'];
    move_uploaded_file(
        $tmp_name,
        "../../assets/uploads/playlists/".$playlist_image
    );
   $insert_query = mysqli_query($conn,"

INSERT INTO playlists(
playlist_name,
description,
cover_image,
is_public,
created_at
)

VALUES(
'$playlist_name',
'$description',
'$playlist_image',
'$status',
NOW()
)

");
    echo "
    <script>
        alert('Playlist created successfully');
        window.location.href='admin_manage_playlists.php';
    </script>
    ";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Playlist</title>
    <link rel="icon" type="image/x-icon" href="../logo.png"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.mcss" relstylesheet" >
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;700;800;900&display=swap" rel="stylesheet" >
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
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
            display:flex;
            justify-content:center;
            align-items:center;
            min-height:100vh;
            padding:40px;
        }
        .form-container{
            width:100%;
            max-width:700px;
            background:#181818;
            border-radius:30px;
            padding:40px;
        }
        .back-btn{
            display:inline-flex;
            align-items:center;
            gap:8px;
            color:#53e076;
            text-decoration:none;
            margin-bottom:30px;
            font-size:14px;
            font-weight:600;
        }
        .back-btn:hover{
            color:#1ed760;
        }
        .page-title{
            font-size:50px;
            font-weight:800;
            margin-bottom:10px;
        }
        .page-subtitle{
            color:#888;
            margin-bottom:40px;
        }
        .form-group{
            margin-bottom:25px;
        }
        .form-label{
            display:block;
            margin-bottom:10px;
            font-weight:600;
        }
        .form-control{
            width:100%;
            height:60px;
            border:none;
            border-radius:18px;
            background:#242424;
            color:#fff;
            padding:0 20px;
            font-size:16px;
        }
        textarea.form-control{
            height:150px;
            padding-top:20px;
            resize:none;
        }
        .form-control:focus{
            outline:none;
            box-shadow:none;
            background:#2b2b2b;
            color:#fff;
        }
        .submit-btn{
            width:100%;
            height:65px;
            border:none;
            border-radius:20px;
            background:#1db954;
            color:#000;
            font-size:18px;
            font-weight:700;
            transition:0.3s;
        }
        .submit-btn:hover{
            background:#1ed760;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <a href="admin_manage_playlists.php" class="back-btn">
            <span class="material-symbols-outlined">
                arrow_back
            </span>
            Back to Manage Playlists
        </a>
        <h1 class="page-title">Add Playlist</h1>
        <p class="page-subtitle">Create a new playlist for your platform</p>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label class="form-label">Playlist Name</label>
                <input type="text" name="playlist_name" class="form-control" placeholder="Enter playlist name" required>
            </div>
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" placeholder="Enter playlist description"></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Playlist Cover Image</label>
                <input type="file" name="playlist_image" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Status</label>
                <select
    name="status"
    class="form-control"
>

    <option value="1">
        Public
    </option>

    <option value="0">
        Private
    </option>

</select>
            </div>
            <button type="submit" name="create_playlist" class="submit-btn">
                Create Playlist
            </button>
        </form>
    </div>
</body>
</html>