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
    header("Location: admin_manage_users.php");
    exit();
}
$id = $_GET['id'];
$user_query = mysqli_query($conn," SELECT * FROM users WHERE id='$id' ");
$user = mysqli_fetch_assoc($user_query);
if(isset($_POST['update_user']))
{
    $username = mysqli_real_escape_string( $conn, $_POST['username']);
    $email = mysqli_real_escape_string( $conn, $_POST['email']);
    $status = mysqli_real_escape_string( $conn, $_POST['status']);
    $update_query = mysqli_query($conn,"
UPDATE users
SET
username = '$username',
email = '$email',
status = '$status'
WHERE id='$id'
");
    {
        echo "
        <script>
            alert('User updated successfully');
            window.location.href='admin_manage_users.php';
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
    <title> Edit User </title> 
    <link rel="icon" type="image/x-icon" href="../logo.png"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" > 
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
            font-size:38px;
            font-weight:800;
            margin-bottom:10px;
            color:#53e076;
        }
        .page-subtitle{
            color:#888;
            margin-bottom:40px;
            font-size:17px;
            color:#53e076;
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
            border-radius:50%;
            object-fit:cover;
            margin-bottom:25px;
        }
    </style>
</head> 
<body> 
    <div class="form-container"> 
        <a href="admin_manage_users.php" class="back-btn">
                <span class="material-symbols-outlined">
                    arrow_back
                </span>
                Back to users
            </a>
        <h1 class="page-title"> Edit User </h1> 
        <p class="page-subtitle"> Update user details </p>
        <form method="POST" enctype="multipart/form-data" >
            <img src="<?php
                if(!empty($user['profile_image']))
                {
                    echo '../../assets/uploads/users/'.$user['profile_image'];
                }
                else
                {
                    echo 'https://i.pravatar.cc/200?u='.$user['id'];
                }
                ?>" class="preview-image">
            <div class="form-group">
                <label class="form-label"> Username </label>
                <input type="text" name="username" class="form-control" value="<?php echo $user['username']; ?>" required >
            </div>
            <div class="form-group">
                <label class="form-label"> Email Address
                </label>
                <input type="email" name="email" class="form-control" value="<?php echo $user['email']; ?>" required >
            </div>
            <div class="form-group">
                <label class="form-label"> Status </label>
                <select name="status" class="form-control" >
                    <option value="Active" <?php if($user['status'] == 'Active'){ echo "selected"; } ?>>
                        Active
                    </option>
                    <option value="Suspended" <?php if($user['status'] == 'Suspended'){ echo "selected"; } ?>>
                        Suspended
                    </option>
                </select>
            </div>
            <button type="submit" name="update_user" class="submit-btn" >
                Update User
            </button>
        </form>
    </div>
</body>
</html>