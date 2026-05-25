<?php
session_start();
include("../../db_config.php");
if(isset($_POST['username']) && isset($_POST['password']))
{
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password = $_POST['password'];
    $sql = "SELECT * FROM admin WHERE username='$username'";
    $result = mysqli_query($conn, $sql);
    if(mysqli_num_rows($result) > 0)
    {
        $row = mysqli_fetch_assoc($result);
        if($password == $row['password'])
        {
            mysqli_query($conn, "UPDATE admin SET is_logged_in='1' WHERE id='" . $row['id'] . "'");
            $_SESSION['admin_id'] = $row['id'];
            $_SESSION['admin_username'] = $row['username'];
            header("Location: admin_dashboard.php");
            exit();
        }
        else
        {
            $error = "Wrong Password";
        }
    }
    else
    {
        $error = "Admin Not Found";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Melodix Admin Login</title>
    <link rel="icon" type="image/x-icon" href="../logo.png"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <link href="../../assets/css/app.css" rel="stylesheet">
    <style>
        body{
            font-family:'Montserrat',sans-serif;
            background:#0e0e0e;
            color:#ffffff;
            height:100vh;
            overflow:hidden;
            display:flex;
            justify-content:center;
            align-items:center;
            position:relative;
        }
        .bg-circle-one{
            position:absolute;
            width:400px;
            height:400px;
            background:rgba(83,224,118,0.12);
            border-radius:50%;
            filter:blur(120px);
            top:-150px;
            left:-150px;
        }
        .bg-circle-two{
            position:absolute;
            width:350px;
            height:350px;
            background:rgba(83,224,118,0.08);
            border-radius:50%;
            filter:blur(120px);
            bottom:-150px;
            right:-150px;
        }
        .glass-card{
            width:100%;
            max-width:450px;
            background:rgba(18,18,18,0.75);
            backdrop-filter:blur(25px);
            border:1px solid rgba(83,224,118,0.1);
            border-radius:25px;
            padding:40px;
            position:relative;
            z-index:10;
            box-shadow:0 10px 40px rgba(0,0,0,0.5);
        }
        .logo-icon{
            font-size:60px;
            color:#53e076;
        }
        .title{
            color:#53e076;
            font-weight:800;
        }
        .subtitle{
            color:#c7c6c6;
            font-size:14px;
        }
        .form-control{
            background:#1b1b1b;
            border:1px solid #333;
            color:#fff;
            height:55px;
        }
        .form-control:focus{
            background:#1b1b1b;
            color:#fff;
            border-color:#53e076;
            box-shadow:none;
        }
        .form-label{
            color:#c7c6c6;
            font-size:13px;
            font-weight:600;
        }
        .login-btn{
            width:100%;
            height:55px;
            border:none;
            border-radius:50px;
            background:#1db954;
            color:#003914;
            font-weight:700;
            transition:0.3s;
        }
        .login-btn:hover{
            background:#53e076;
            transform:scale(1.02);
        }
        .alert{
            border-radius:15px;
        }
    </style>
</head>
<body>
    <div class="bg-circle-one"></div>
    <div class="bg-circle-two"></div>
    <div class="glass-card">
        <div class="text-center mb-4">
            <span class="material-symbols-outlined logo-icon">
                admin_panel_settings
            </span>
            <h1 class="title mt-3">
                Melodix Admin
            </h1>
            <p class="subtitle">
                Login for admin
            </p>
        </div>
        <?php
        if(isset($error))
        {
            ?>
            <div class="alert alert-danger text-center">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php
        }
        ?>
        <form method="POST">
            <div class="mb-4">
                <label class="form-label">
                    Admin Username
                </label>
                <input 
                    type="text"
                    name="username"
                    class="form-control rounded-4"
                    placeholder="Enter Username"
                    required
                >
            </div>
            <div class="mb-4">
                <label class="form-label">
                    Password
                </label>
                <input 
                    type="password"
                    name="password"
                    class="form-control rounded-4"
                    placeholder="Enter Password"
                    required
                >
            </div>
            <button type="submit" class="login-btn">
                Login
            </button>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
