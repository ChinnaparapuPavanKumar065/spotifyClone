<?php
session_start();
include("../../db_config.php");
include("../shared/listener_tracking.php");

ensureListenerTrackingSchema($conn);

if(!isset($_SESSION['admin_id']))
{
    header("Location: admin_login.php");
    exit();
}
mysqli_query($conn,
"UPDATE users SET active_listeners = 0 WHERE active_listeners = 1 AND (last_activity_at IS NULL OR last_activity_at < NOW() - INTERVAL 5 MINUTE)");
$current_page = basename($_SERVER['PHP_SELF']);
$total_users_query = mysqli_query($conn," SELECT COUNT(*) AS total_users FROM users");
$total_users_data = mysqli_fetch_assoc($total_users_query);
$total_users = $total_users_data['total_users'];
$total_tracks_query = mysqli_query($conn,"SELECT COUNT(*) AS total_tracks FROM songs");
$total_tracks_data = mysqli_fetch_assoc($total_tracks_query);
$total_tracks = $total_tracks_data['total_tracks'];
$active_listeners_query = mysqli_query($conn,
"SELECT COUNT(*) AS active_listeners
FROM users
WHERE active_listeners = 1
AND last_activity_at IS NOT NULL
AND last_activity_at >= NOW() - INTERVAL 5 MINUTE");
$active_listeners_data = mysqli_fetch_assoc($active_listeners_query);
$active_listeners = $active_listeners_data['active_listeners'];
$total_listening_seconds = 0;
$total_listening_query = mysqli_query($conn,
"SELECT COALESCE(SUM(seconds_played), 0) AS total_seconds
FROM user_listening_history");
$total_listening_data = mysqli_fetch_assoc($total_listening_query);
$total_listening_seconds = (int) $total_listening_data['total_seconds'];
if($total_listening_seconds < 0)
{
    $total_listening_seconds = 0;
}
$total_hours_played = round($total_listening_seconds / 3600, 2);
$total_hours_played=$total_hours_played/60*100;
$recent_users_query = mysqli_query($conn,"SELECT username, user_id, last_login FROM users WHERE last_login IS NOT NULL ORDER BY last_login DESC LIMIT 30");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Melodix Admin Dashboard</title>
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
            display:flex;
            flex-direction:column;
            justify-content:space-between;
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
            margin-top:auto;
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
            font-size:15px;
            font-weight:700;
            transition:0.3s;
        }
        .logout-btn:hover{
            background:#1ed760;
            transform:translateY(-2px);
        }
        .logout-btn span{
            font-size:21px;
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
        .dashboard-title{
            font-size:42px;
            font-weight:800;
            margin-bottom:10px;
        }

        .dashboard-subtitle{
            color:#888;
            font-size:15px;
            margin-bottom:35px;
        }
        .dashboard-card{
            background:#181818;
            border-radius:25px;
            padding:25px;
            height:100%;
            border:1px solid rgba(255,255,255,0.05);
            transition:0.3s;
        }

        .dashboard-card:hover{
            transform:translateY(-5px);
        }

        .card-icon{
            width:60px;
            height:60px;
            border-radius:18px;
            background:rgba(83,224,118,0.1);

            display:flex;
            align-items:center;
            justify-content:center;

            color:#53e076;

            margin-bottom:20px;
        }

        .card-icon span{
            font-size:30px;
        }

        .card-title{
            color:#888;
            font-size:13px;
            margin-bottom:8px;
        }

        .card-value{
            font-size:34px;
            font-weight:800;
        }
        .activity-container{
            background:#181818;
            border-radius:25px;
            padding:25px;
            margin-top:35px;
            border:1px solid rgba(255,255,255,0.05);
        }

        .section-title{
            font-size:22px;
            font-weight:700;
            margin-bottom:25px;
        }

        .activity-card{
            display:flex;
            align-items:center;
            gap:15px;
            padding:18px;
            border-radius:18px;
            background:#202020;
            margin-bottom:15px;
        }

        .activity-icon{
            width:50px;
            height:50px;
            border-radius:15px;
            background:rgba(83,224,118,0.1);

            display:flex;
            align-items:center;
            justify-content:center;

            color:#53e076;
        }

        .activity-title{
            font-size:15px;
            font-weight:600;
            margin:0;
        }

        .activity-time{
            font-size:13px;
            color:#888;
            margin:0;
        }

    </style>

</head>

<body>

    <!-- SIDEBAR -->

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
            </ul>

        </div>

        <!-- Logout -->

        <div class="sidebar-bottom">

            <a href="logout.php" class="logout-btn">

                <span class="material-symbols-outlined">
                    logout
                </span>

                Sign Out

            </a>

        </div>

    </div>

    <!-- TOPBAR -->

    <div class="topbar">

        <div class="topbar-title">
            Dashboard Overview
        </div>

        <div class="admin-box">

            <div>

                <div class="admin-name">
                    Admin User
                </div>

                <div class="admin-role">
                    Super Admin
                </div>

            </div>

            <img src="https://i.pravatar.cc/100" alt="Admin">

        </div>

    </div>

    <!-- MAIN CONTENT -->

    <div class="main-content">

        <h1 class="dashboard-title">
            Dashboard Overview
        </h1>

        <p class="dashboard-subtitle">
            Real-time platform statistics and analytics
        </p>

        <!-- Cards -->

        <div class="row g-4">

            <!-- Total Users -->

            <div class="col-lg-3 col-md-6">

                <div class="dashboard-card">

                    <div class="card-icon">
                        <span class="material-symbols-outlined">
                            group
                        </span>
                    </div>

                    <div class="card-title">
                        TOTAL USERS
                    </div>

                    <div class="card-value">
                        <?php echo number_format($total_users); ?>
                    </div>

                </div>

            </div>

            <!-- Total Hours -->

            <div class="col-lg-3 col-md-6">

                <div class="dashboard-card">

                    <div class="card-icon">
                        <span class="material-symbols-outlined">
                            timer
                        </span>
                    </div>

                    <div class="card-title">
                        TOTAL HOURS PLAYED
                    </div>

                    <div class="card-value">
                        <p><?php echo number_format($total_hours_played, 2); ?> hrs</p>
                    </div>

                </div>

            </div>

            <!-- Active Listeners -->

            <div class="col-lg-3 col-md-6">

                <div class="dashboard-card">

                    <div class="card-icon">
                        <span class="material-symbols-outlined">
                            headphones
                        </span>
                    </div>

                    <div class="card-title">
                        ACTIVE LISTENERS
                    </div>

                    <div class="card-value">
                        <?php echo number_format($active_listeners); ?>
                    </div>

                </div>

            </div>

            <!-- Tracks -->

            <div class="col-lg-3 col-md-6">

                <div class="dashboard-card">

                    <div class="card-icon">
                        <span class="material-symbols-outlined">
                            music_note
                        </span>
                    </div>

                    <div class="card-title">
                        TOTAL TRACKS
                    </div>

                    <div class="card-value">
                        <?php echo number_format($total_tracks); ?>
                    </div>

                </div>

            </div>

        </div>

        <!-- Recent Activity -->

        <div class="activity-container">

            <h3 class="section-title">
                Recent Activity
            </h3>

            <?php

            while($recent_user = mysqli_fetch_assoc($recent_users_query))
            {

            ?>

            <div class="activity-card">

                <div class="activity-icon">

                    <span class="material-symbols-outlined">
                        login
                    </span>

                </div>

                <div>

                    <p class="activity-title">
                        <?php echo htmlspecialchars($recent_user['username']); ?> logged in
                    </p>

                    <p class="activity-time">
                        <?php echo htmlspecialchars($recent_user['last_login']); ?>
                    </p>

                </div>

            </div>

            <?php

            }

            ?>

        </div>

    </div>

    <!-- Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
