<?php
session_start();
include("../../db_config.php");
if(!isset($_SESSION['admin_id']))
{
    header("Location: admin_login.php");
    exit();
}
$current_page = basename($_SERVER['PHP_SELF']);
$admin_name = $_SESSION['admin_username'];
$search = "";
if(isset($_GET['search']))
{
    $search = mysqli_real_escape_string($conn,$_GET['search']);
}
if($search != "")
{
    $playlist_query = mysqli_query($conn," SELECT * FROM playlists WHERE playlist_name LIKE '%$search%' ORDER BY id DESC");
}
else
{
    $playlist_query = mysqli_query($conn," SELECT * FROM playlists ORDER BY id DESC ");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Playlists</title>
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
        .logout-btn:hover{
            background:#1ed760;
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
            align-items:center;
            gap:20px;
            margin-bottom:35px;
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
            height:55px;
            padding:0 25px;
            background:#1db954;
            color:#000;
            border-radius:40px;
            display:flex;
            align-items:center;
            gap:10px;
            text-decoration:none;
            font-weight:700;
            white-space:nowrap;
        }
         .add-btn:hover{
            background:#1ed760;
        }
        .playlist-grid{
            display:grid;
            grid-template-columns:repeat(4,minmax(220px,1fr));
            gap:20px;
            width:100%;
        }
        .playlist-card{
            width:100%;
            background:#181818;
            border-radius:25px;
            overflow:hidden;
            transition:0.3s;
            border:1px solid rgba(255,255,255,0.05);
        }
        .playlist-card:hover{
            transform:translateY(-5px);
        }
        .playlist-image{
            width:100%;
            height:260px;
            object-fit:cover;
        }
        .playlist-content{
            padding:20px;
        }
        .playlist-title{
            font-size:22px;
            font-weight:700;
        }
        .playlist-meta{
            color:#888;
            margin-top:8px;
            font-size:14px;
        }
        .playlist-actions{
            display:flex;
            gap:12px;
            margin-top:20px;
        }
        .action-btn{
            flex:1;
            height:45px;
            border:none;
            border-radius:14px;
            display:flex;
            align-items:center;
            justify-content:center;
            text-decoration:none;
            font-weight:600;
            transition:0.3s;
        }
        .edit-btn{
            background:#2a2a2a;
            color:#fff;
        }
        .edit-btn:hover{
            background:#3a3a3a;
        }
        .delete-btn{
            background:#ff3b30;
            color:#fff;
        }
        .delete-btn:hover{
            background:#ff453a;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="logo">
            Melodix
        </div>
        <div class="sidebar-subtitle">
            Admin Dashboard
        </div>
        <ul class="sidebar-menu">
            <li>
                <a href="admin_dashboard.php" class="<?= ($current_page == 'admin_dashboard.php') ? 'active' : ''; ?>" >
                    <span class="material-symbols-outlined">dashboard</span>
                    Dashboard
                </a>
            </li>
            <li>
                <a href="admin_manage_songs.php" class="<?= ($current_page == 'admin_manage_songs.php') ? 'active' : ''; ?>" >
                    <span class="material-symbols-outlined">music_note</span>
                    Manage Songs
                </a>
            </li>
            <li>
                <a href="admin_manage_users.php" class="<?= ($current_page == 'admin_manage_users.php') ? 'active' : ''; ?>" >
                    <span class="material-symbols-outlined">group</span>
                    Manage Users
                </a>
            </li>
            <li>
                <a href="admin_manage_playlists.php" class="active">
                    <span class="material-symbols-outlined">queue_music</span>
                    Playlists
                </a>
            </li>
            <!-- <li>
                <a href="admin_manage_podcasts.php">
                    <span class="material-symbols-outlined">mic</span>
                    Podcasts
                </a>
            </li>
            <li>
                <a href="analytics.php">
                    <span class="material-symbols-outlined">analytics</span>
                    Analytics
                </a>
            </li> -->
        </ul>
        <div class="sidebar-bottom">
            <a href="logout.php" class="logout-btn">
                <span class="material-symbols-outlined">logout</span>
                Sign Out
            </a>
        </div>
    </div>
    <div class="topbar">
        <div class="topbar-title">
            Content Manager
        </div>
        <div class="admin-box">
            <div>
                <div class="admin-name">
                    <?php echo $admin_name; ?>
                </div>
                <div class="admin-role">
                    Super Admin
                </div>
            </div>
            <img src="https://i.pravatar.cc/100" alt="Admin">
        </div>
    </div>
    <div class="main-content">
        <h1 class="page-title">
            Manage Playlists
        </h1>
        <p class="page-subtitle">
            Curate and organize all playlists across the platform
        </p>
        <div class="action-bar">
            <form method="GET" class="search-box">
                <span class="material-symbols-outlined">
                    search
                </span>
                <input type="text" name="search" placeholder="Search playlists..." value="<?php echo $search; ?>" >
            </form>
            <a href="add_playlist.php" class="add-btn" >
                <span class="material-symbols-outlined">add</span>
                Add Playlist
            </a>
        </div>
        <div class="playlist-grid">
            <?php
            while($playlist = mysqli_fetch_assoc($playlist_query))
            {
            ?>
                <div class="playlist-card">
                    <a href="playlist_details.php?id=<?php echo $playlist['id']; ?>" style="text-decoration:none;color:white;">
                    <img src="../../assets/uploads/playlists/<?php echo $playlist['cover_image']; ?>" class="playlist-image">
                    <div class="playlist-content">
                        <div class="playlist-title">
                            <?php echo $playlist['playlist_name']; ?>
                        </div>
            </a>
                    <!-- <div class="playlist-meta">
                        <?php
                        if(isset($playlist['total_tracks']))
                        {
                            echo $playlist['total_tracks'];
                        }
                        else
                        {
                            echo 0;
                        }
                        ?>
                        Tracks
                    </div> -->
                    <div class="playlist-actions">
                            <a href="edit_playlist.php?id=<?php echo $playlist['id']; ?>" class="action-btn" style="background:#1db954;color:#000;" >
                                Edit
                            </a>
                            <a href="delete_playlist.php?id=<?php echo $playlist['id']; ?>" class="action-btn delete-btn" onclick="return confirm('Delete this playlist?')" >
                                Delete
                            </a>
                    </div>
                </div>
            </div>
            <?php
            }
            ?>
        </div>
    </div>
</body>
</html>