<?php
include("../../db_config.php");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add User - Melodix Admin</title>
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
            /* padding:40px 20px; */
        }
        .form-card{
            width:80%;
            max-width:750px;
            background:#181818;
            border-radius:30px;
            padding:50px;
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
            margin-top:15px;
            margin-bottom:35px;
        }
        .form-label{
            font-size:14px;
            margin-bottom:10px;
            color:#ddd;
            font-weight:600;
            margin-bottom:15px;
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
            margin-top:25px;
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
            <a href="admin_manage_users.php" class="back-btn">
                <span class="material-symbols-outlined">
                    arrow_back
                </span>
                Back to users
            </a>
            <h1 class="page-title">Add New User</h1>
            <form action="insert_user.php" method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-lg-12">
                        <label class="form-label">User Id</label>
                        <input type="text" name="userid" class="form-control" placeholder="Enter UserId" required>
                    </div>
                    <div class="col-lg-12">
                        <label class="form-label">User Name</label>
                        <input type="text" name="username" class="form-control" placeholder="Enter UserName" required>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="Enter user Email" required>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label"> Password </label>
                        <input type="password" name="password" class="form-control" placeholder="Enter Password" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">
                                Select Status
                            </option>
                            <option>active</option>
                            <option>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-6 md-3">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" name="DOB" class="form-control" placeholder="Enter user DOB" required>
                    </div>
                </div>
                <button type="submit" class="submit-btn">
                    Register User
                </button>
            </form>
        </div>
    </div>
</body>
</html>