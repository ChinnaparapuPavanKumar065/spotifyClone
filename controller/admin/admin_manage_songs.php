<?php
session_start();
include("../../db_config.php");
if(!isset($_SESSION['admin_id']))
{
    header("Location: admin_login.php");
    exit();
}
$admin_id = $_SESSION['admin_id'];
$admin_query = mysqli_query($conn,"SELECT * FROM admin WHERE id='$admin_id'");
$admin_data = mysqli_fetch_assoc($admin_query);
if(!$admin_data)
{
    die("Admin data not found");
}
$admin_name = $admin_data['username'];
$current_page = basename($_SERVER['PHP_SELF']);
if(isset($_POST['update_song']))
{
    $song_id     = $_POST['song_id'];
    $song_title  = $_POST['song_title'];
    $artist_name = $_POST['artist_name'];
    $album_name  = $_POST['album_name'];
    $genre       = $_POST['genre'];
    $description = $_POST['description'];
    mysqli_query($conn,"UPDATE songs SET song_title='$song_title', artist_name='$artist_name', album_name='$album_name', genre='$genre', description='$description' WHERE id='$song_id'");
    echo "
    <script>

        alert('Song updated successfully');

        window.location.href='admin_manage_songs.php';

    </script>

    ";

}
$search = "";
if(isset($_GET['search']))
{
    $search = mysqli_real_escape_string($conn,$_GET['search']);
}
if($search != "")
{
    $songs_query = mysqli_query($conn," SELECT * FROM songs WHERE song_title LIKE '%$search%' ORDER BY id DESC");
}
else
{
    $songs_query = mysqli_query($conn,"
    SELECT *
    FROM songs
    ORDER BY id DESC
    ");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Songs - Melodix Admin</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script> 
    <link rel="icon" type="image/x-icon" href="../logo.png"/>
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;700;800;900&display=swap" rel="stylesheet">
    <!-- Material Icons -->
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
            overflow-x:hidden;
        }
        .sidebar{
            width:280px;
            height:100vh;
            background:#161616;
            position:fixed;
            left:0;
            top:0;
            padding:30px 20px;
            border-right:1px solid rgba(255,255,255,0.05);
        }
        .logo{
            color:#53e076;
            font-size:28px;
            font-weight:800;
        }
        .sidebar-subtitle{
            color:#888;
            font-size:13px;
            margin-top:5px;
            margin-bottom:40px;
        }
        .sidebar-menu{
            list-style:none;
            padding:0;
            margin:0;
        }
        .sidebar-menu li{
            margin-bottom:10px;
        }
        .sidebar-menu a{
            display:flex;
            align-items:center;
            gap:15px;
            text-decoration:none;
            color:#b3b3b3;
            padding:13px 15px;
            border-radius:14px;
            transition:0.3s;
            font-size:15px;
            font-weight:500;
        }
        .sidebar-menu a span{
            font-size:21px;
        }
        .sidebar-menu a:hover{
            background:#282828;
            color:#fff;
        }
        .sidebar-menu a.active{
            background:#1db954;
            color:#000;
            font-weight:700;
        }
        .sidebar-bottom{
            position:absolute;
            bottom:30px;
            left:20px;
            width:calc(100% - 40px);
        }
        .logout-btn{
            display:flex;
            align-items:center;
            gap:15px;
            width:100%;
            background:#1db954;
            color:#000;
            text-decoration:none;
            padding:13px 15px;
            border-radius:14px;
            transition:0.3s;
            font-size:15px;
            font-weight:700;
        }
        .logout-btn span{
            font-size:21px;
        }
        .logout-btn:hover{
            background:#1ed760;
        }
        .admin-profile-btn{
            width:45px;
            height:45px;
            border-radius:50%;
            background:#53e076;
            color:#000;
            border:none;
            font-size:22px;
            font-weight:800;
            display:flex;
            align-items:center;
            justify-content:center;
            cursor:pointer;
            transition:0.3s;
        }
        .admin-profile-btn:hover{
            transform:scale(1.08);
        }

        #profileDropdown{
            background:#1f1f1f;
            border:1px solid rgba(255,255,255,0.08);
            border-radius:16px;
            width:220px;
            overflow:hidden;
            box-shadow:0 10px 30px rgba(0,0,0,0.4);
            position:absolute;
            right:0;
            top:60px;
            z-index:999;
            display:none;
        }

        #profileDropdown a{
            display:flex;
            align-items:center;
            gap:12px;
            padding:14px 18px;
            text-decoration:none;
            color:#d1d5db;
            transition:0.3s;
        }

        #profileDropdown a:hover{
            background:#2a2a2a;
            color:#53e076;
        }
        .topbar{
            position:fixed;
            left:280px;
            top:0;
            width:calc(100% - 280px);
            height:80px;
            background:#161616;
            display:flex;
            justify-content:space-between;
            align-items:center;
            padding:0 35px;
            border-bottom:1px solid rgba(255,255,255,0.05);
            z-index:100;
        }
        .topbar-title{
            font-size:20px;
            font-weight:700;
            color:#53e076;
        }
        .admin-box{
            display:flex;
            align-items:center;
            gap:15px;
        }
        .admin-box img{
            width:45px;
            height:45px;
            border-radius:50%;
            object-fit:cover;
        }
        .admin-name{
            font-size:14px;
            font-weight:700;
        }
        .admin-role{
            font-size:12px;
            color:#53e076;
        }
        .main-content{
            margin-left:280px;
            padding:120px 40px 40px;
        }
        .page-title{
            font-size:42px;
            font-weight:800;
            margin-bottom:10px;
        }
        .page-subtitle{
            color:#888;
            margin-bottom:30px;
        }
        .action-bar{
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:30px;
        }
        .search-box{
            flex:1;
            position:relative;
            margin-right:20px;
            display:flex;
            align-items:center;
        }
        .search-box span{
            position:absolute;
            left:18px;
            top:50%;
            transform:translateY(-50%);
            color:#777;
        }
        .search-box input{
            width:100%;
            flex:1;
            height:55px;
            background:#1d1d1d;
            border:none;
            border-radius:16px;
            padding-left:55px;
            color:#fff;
        }
        .search-box input:focus{
            outline:none;
        }
        .add-btn{
            display:flex;
            align-items:center;
            gap:8px;
            background:#1db954;
            text-decoration:none;
            padding:14px 26px;
            border-radius:40px;
            color:#000;
            font-size:14px;
            font-weight:700;
        }
        .add-btn:hover{
            background:#1ed760;
        }
        .table-container{
            background:#181818;
            border-radius:25px;
            overflow:hidden;
        }
        table{
            width:100%;
        }
        thead{
            background:#202020;
        }
        thead th{
            padding:20px;
            color:#888;
            font-size:12px;
        }
        tbody td{
            padding:20px;
        }
        tbody tr{
            border-top:1px solid rgba(255,255,255,0.05);
        }
        .song-info{
            display:flex;
            align-items:center;
            gap:15px;
        }
        .song-info img{
            width:60px;
            height:60px;
            border-radius:14px;
            object-fit:cover;
        }
        .song-title{
            font-size:15px;
            font-weight:700;
        }
        .genre-badge{
            background:#2a2a2a;
            padding:6px 12px;
            border-radius:20px;
            font-size:12px;
        }
        .action-buttons{
            display:flex;
            align-items:center;
            justify-content:center;
            gap:10px;
        }
        .icon-btn{
            width:40px;
            height:40px;
            display:flex;
            align-items:center;
            justify-content:center;
            border:none;
            border-radius:12px;
            background:#2a2a2a;
            color:#fff;
            text-decoration:none;
            transition:0.3s;
        }
        .icon-btn:hover{
            background:#1db954;
            color:#000;
        }
        .form-control{
            background:#202020 !important;
            border:none;
            color:#fff !important;
        }
        .form-control:focus{
            box-shadow:none;
            border:1px solid #1db954;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div>
            <div class="logo">
                Melodix
            </div>
            <div class="sidebar-subtitle">
                Admin Dashboard
            </div>
            <ul class="sidebar-menu">
                <li>
                    <a href="admin_dashboard.php"
                    class="<?php echo ($current_page == 'admin_dashboard.php') ? 'active' : ''; ?>">
                        <span class="material-symbols-outlined">
                            dashboard
                        </span>
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="admin_manage_songs.php"
                    class="<?php echo ($current_page == 'admin_manage_songs.php') ? 'active' : ''; ?>">
                        <span class="material-symbols-outlined">
                            music_note
                        </span>
                        Manage Songs
                    </a>
                </li>
                <li>
                    <a href="admin_manage_users.php"
                    class="<?php echo ($current_page == 'admin_manage_users.php') ? 'active' : ''; ?>">
                        <span class="material-symbols-outlined">
                            group
                        </span>
                        Manage Users
                    </a>
                </li>
                <li>
                    <a href="admin_manage_playlists.php"
                    class="<?php echo ($current_page == 'admin_manage_playlists.php') ? 'active' : ''; ?>">
                        <span class="material-symbols-outlined">
                            queue_music
                        </span>
                        Playlists
                    </a>
                </li>
                <!-- <li>
                    <a href="manage_podcasts.php"
                    class="<?php echo ($current_page == 'manage_podcasts.php') ? 'active' : ''; ?>">
                        <span class="material-symbols-outlined">
                            mic
                        </span>
                        Podcasts
                    </a>
                </li>
                <li>
                    <a href="analytics.php"
                    class="<?php echo ($current_page == 'analytics.php') ? 'active' : ''; ?>">
                        <span class="material-symbols-outlined">
                            analytics
                        </span>
                        Analytics
                    </a>
                </li> -->
            </ul>
        </div>
        <div class="sidebar-bottom">
            <a href="logout.php" class="logout-btn">
                <span class="material-symbols-outlined">
                    logout
                </span>
                Sign Out
            </a>
        </div>
    </div>
    <div class="topbar">
        <div class="topbar-title">
            Content Manager
        </div>
        <div class="admin-box">
           <div class="admin-box">
                <h3>Welcome :
                    <strong class="text-[#1db954]">
                        <?php echo $admin_name; ?>
                    </strong>
                </h3>
                <?php $first_letter = strtoupper(substr($admin_name, 0, 1));?>
                <div class="relative">
                    <button onclick="toggleProfileDropdown()" class="admin-profile-btn">
                        <?php echo $first_letter; ?>
                    </button>
                    <!-- <div id="profileDropdown" class="hidden absolute right-0 mt-3 w-52 overflow-hidden z-50">
                        <div class="p-4 border-b border-white/10">
                            <p class="text-[#53e076] font-semibold text-center">
                                <?php echo $admin_name; ?>
                            </p>
                        </div>
                        <a href="profile.php" class="flex items-center gap-3 px-4 py-3 text-gray-300 hover:bg-[#2a2a2a] hover:text-[#53e076] transition">
                            <span class="material-symbols-outlined">person</span>
                            Profile
                        </a>
                        <a href="playlists.php" class="flex items-center gap-3 px-4 py-3 text-gray-300 hover:bg-[#2a2a2a] hover:text-[#53e076] transition">
                            <span class="material-symbols-outlined">playlist_add</span>
                            Playlists
                        </a>
                        
                        <a href="library.php" class="flex items-center gap-3 px-4 py-3 text-gray-300 hover:bg-[#2a2a2a] hover:text-[#53e076] transition">
                            <span class="material-symbols-outlined">library_music</span>
                            Library
                        </a>
                        <a href="logout.php" class="flex items-center gap-3 px-4 py-3 text-red-400 hover:bg-[#2a2a2a] transition">
                            <span class="material-symbols-outlined">logout</span>
                            Logout
                        </a>
                    </div> -->
                </div>
            </div>
        </div>
    </div>
    <div class="main-content">
        <h1 class="page-title">
            Manage Songs
        </h1>
        <p class="page-subtitle">
            Central catalog for all songs across the platform
        </p>
        <div class="action-bar">
        <form method="GET" class="search-box">

            <span class="material-symbols-outlined">
                search
            </span>

            <input
                type="text"
                name="search"
                placeholder="Search songs by track name..."
                value="<?php echo $search; ?>"
            >
        </form>
        <a href="add_song.php" class="add-btn">
            <span class="material-symbols-outlined">
                add
            </span>
            Add Song
        </a>
    </div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th style="width:35%;">TRACK NAME</th>
                    <th>ARTIST</th>
                    <th>ALBUM</th>
                    <th>GENRE</th>
                    <th style="text-align:center;">ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    while($song = mysqli_fetch_assoc($songs_query))
                    {
                ?>
                <tr>
                    <td>
                        <div class="song-info">
                            <img src="../../assets/uploads/covers/<?php echo $song['cover_image']; ?>">
                            <div>
                                <div class="song-title">
                                    <?php echo $song['song_title']; ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <?php echo $song['artist_name']; ?>
                    </td>
                    <td>
                        <?php echo $song['album_name']; ?>
                    </td>
                    <td>
                        <span class="genre-badge">
                            <?php echo $song['genre']; ?>
                        </span>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button class="icon-btn" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $song['id']; ?>">
                                <span class="material-symbols-outlined">edit</span>
                            </button>
                            <a href="delete_song.php?id=<?php echo $song['id']; ?>" class="icon-btn" onclick="return confirm('Delete this song?')">
                                <span class="material-symbols-outlined">delete</span>
                            </a>
                        </div>
                    </td>
                </tr>
                <div class="modal fade" id="editModal<?php echo $song['id']; ?>" tabindex="-1">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div
                            class="modal-content" style="background:#181818; color:white border-radius:25px;">
                                <div class="modal-header border-0">
                                    <h5 class="modal-title">Edit Song</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" ></button>
                                </div>
                                <form method="POST">
                                    <div class="modal-body">
                                        <input type="hidden" name="song_id" value="<?php echo $song['id']; ?>" >
                                        <div class="mb-3">
                                            <label class="form-label"> Song Title </label>
                                            <input type="text" name="song_title" class="form-control" value="<?php echo $song['song_title']; ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label"> Artist Name </label>
                                            <input type="text" name="artist_name" class="form-control" value="<?php echo $song['artist_name']; ?>" >
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label"> Album Name </label>
                                            <input type="text" name="album_name" class="form-control" value="<?php echo $song['album_name']; ?>" >
                                        </div>
                                            <div class="mb-3">
                                            <label class="form-label"> Genre </label>
                                            <input type="text" name="genre" class="form-control" value="<?php echo $song['genre']; ?>" >
                                        </div>
                                            <!-- <div class="mb-3">
                                                <label class="form-label"> Description </label>
                                                <textarea name="description" class="form-control" rows="4" ><?php echo $song['description']; ?></textarea>
                                            </div> -->
                                        </div>
                                        <div class="modal-footer border-0">
                                            <button type="submit" name="update_song" class="btn btn-success" > Update Song </button>
                                        </div>
                                </form>
                                    </div>
                </div>
    </div>
                <?php
                    }
                ?>
        </tbody>
    </table>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleProfileDropdown()
        {
            const dropdown =
            document.getElementById("profileDropdown");
            if(dropdown.style.display === "block")
            {
                dropdown.style.display = "none";
            }
            else
            {
                dropdown.style.display = "block";
            }
        }
        window.addEventListener("click", function(event)
        {
            if(!event.target.closest(".admin-box"))
            {
                document.getElementById("profileDropdown").style.display = "none";
            }
        });
    </script>
</body>
</html>