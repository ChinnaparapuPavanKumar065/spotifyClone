<?php
include("../../db_config.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Song - Melodix Admin</title>
    <link rel="icon" type="image/x-icon" href="../logo.png"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <style>
        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
        }
        body{
            font-family:'Montserrat',sans-serif;
            background:#0f0f0f;
            color:#fff;
        }
        .page-container{
            min-height:100vh;
            display:flex;
            align-items:center;
            justify-content:center;
            padding:40px 20px;
        }
        .form-card{
            width:100%;
            max-width:750px;
            background:#181818;
            border-radius:30px;
            padding:40px;
            border:1px solid rgba(255,255,255,0.05);
        }
        .page-title{
            font-size:38px;
            font-weight:800;
            margin-bottom:10px;
            color:#53e076;
        }
        .page-subtitle{
            color:#888;
            font-size:15px;
            margin-bottom:35px;
        }
        .form-label{
            font-size:14px;
            margin-bottom:10px;
            color:#ddd;
            font-weight:600;
        }
        .form-control,
        .form-select{
            height:55px;
            background:#202020 !important;
            border:none;
            border-radius:15px;
            color:#fff !important;
            font-size:14px;
        }
        .form-control:focus,
        .form-select:focus{
            box-shadow:none;
            border:1px solid #1db954;
        }
        textarea.form-control{
            height:120px;
            resize:none;
            padding-top:15px;
        }
        .upload-box{
            background:#202020;
            border:2px dashed rgba(255,255,255,0.1);
            border-radius:20px;
            padding:30px;
            text-align:center;
        }
        .upload-box span{
            font-size:50px;
            color:#53e076;
            margin-bottom:10px;
        }
        .upload-box p{
            color:#888;
            margin-top:10px;
            font-size:14px;
        }
        .submit-btn{
            width:100%;
            height:55px;
            background:#1db954;
            border:none;
            border-radius:16px;
            color:#000;
            font-size:16px;
            font-weight:700;
            transition:0.3s;
        }
        .submit-btn:hover{
            background:#1ed760;
            transform:translateY(-2px);
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
    </style>
</head>
<body>
    <div class="page-container">
        <div class="form-card">
            <a href="admin_manage_songs.php" class="back-btn">
                <span class="material-symbols-outlined">
                    arrow_back
                </span>
                Back to Manage Songs
            </a>
            <h1 class="page-title">
                Add New Song
            </h1>
            <p class="page-subtitle">
                Upload songs to the Melodix music platform
            </p>
            <form action="insert_song.php" method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label class="form-label">
                            Song Title
                        </label>
                        <input
                            type="text"
                            name="song_title"
                            class="form-control"
                            placeholder="Enter song title"
                            required
                        >
                    </div>
                    <div class="col-md-6 mb-4">
                        <label class="form-label">
                            Artist Name
                        </label>
                        <input
                            type="text"
                            name="artist_name"
                            class="form-control"
                            placeholder="Enter artist name"
                            required
                        >
                    </div>
                    <div class="col-md-6 mb-4">
                        <label class="form-label">
                            Album Name
                        </label>
                        <input
                            type="text"
                            name="album_name"
                            class="form-control"
                            placeholder="Enter album name"
                        >
                    </div>
                    <div class="col-md-6 mb-4">
                        <label class="form-label">
                            Genre
                        </label>
                        <select name="genre" class="form-select">
                            <option value="">
                                Select Genre
                            </option>
                            <option>Pop</option>
                            <option>Hip Hop</option>
                            <option>Rock</option>
                            <option>Jazz</option>
                            <option>EDM</option>
                            <option>Classical</option>
                            <option>Lo-Fi</option>
                        </select>
                    </div>
                    <div class="col-12 mb-4">
                        <label class="form-label">
                            Song Description
                        </label>
                        <textarea
                            name="description"
                            class="form-control"
                            placeholder="Write something about this song..."
                        ></textarea>
                    </div>
                    <div class="col-md-6 mb-4">
                        <label class="form-label">
                            Upload Song File
                        </label>
                        <div class="upload-box">
                            <span class="material-symbols-outlined">
                                music_note
                            </span>
                            <input
                                type="file"
                                name="song_file"
                                class="form-control mt-3"
                                accept=".mp3,.wav"
                                required
                            >
                            <p>
                                Supported formats: MP3, WAV
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <label class="form-label">
                            Upload Cover Image
                        </label>
                        <div class="upload-box">
                            <span class="material-symbols-outlined">
                                image
                            </span>
                            <input
                                type="file"
                                name="cover_image"
                                class="form-control mt-3"
                                accept=".jpg,.jpeg,.png"
                                required
                            >
                            <p>
                                Supported formats: JPG, PNG
                            </p>
                        </div>
                    </div>
                </div>
                <button type="submit" class="submit-btn">
                    Upload Song
                </button>
            </form>
        </div>
    </div>
</body>
</html>