<?php
session_start();
include("../../db_config.php");
if(isset($_SESSION['user_id']))
{
    header("Location: index.php");
    exit();
}
$passwordPattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/';
$statusMessage = "";
$statusType = "error";
if (isset($_POST['username']) && isset($_POST['password']))
{
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    // $recaptcha = $_POST['g-recaptcha-response'];
    // $secret_key = '6LcXA_csAAAAANBEG4Tv9xZQRW16J3qfwzYaZPNz';
    //  $url = 'https://www.google.com/recaptcha/api/siteverify?secret='
    //       . $secret_key . '&response=' . $recaptcha;
    //        $response = file_get_contents($url);
           
    // // Checking, if response is true or not
    // $response = json_decode($response);
    // if (!$response->success)    {
    //     $statusMessage = "reCAPTCHA verification failed. Please try again.";
    // }
     if (!preg_match($passwordPattern, $password))
    {
        $statusMessage = "Password format is invalid";
    }
    else
    {
        $query = mysqli_query($conn,"SELECT * FROM users WHERE username='$username' OR user_id='$username' OR email='$username'");
        if (mysqli_num_rows($query) > 0)
        {
            $row = mysqli_fetch_assoc($query);
            if ($password == $row['password'])
            {
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['username'] = $row['username'];
                $user_id = $row['user_id'];
                mysqli_query($conn,"UPDATE users SET last_login = NOW(), is_logged_in='1' WHERE user_id='$user_id'"
                );
                header("Location: index.php");
                exit();
            }
            else
            {
                $statusMessage = "Wrong password";
            }
        }
        else
        {
            $statusMessage = "User not found";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Melodix Login</title>
    <link rel="icon" type="image/x-icon" href="../logo.png"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/app.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
            background: #0e0e0e;
            color: #ffffff;
            height: 100vh;
            overflow: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }
        .bg-circle-one {
            position: absolute;
            width: 400px;
            height: 400px;
            background: rgba(83, 224, 118, 0.12);
            border-radius: 50%;
            filter: blur(120px);
            top: -150px;
            left: -150px;
        }
        .bg-circle-two {
            position: absolute;
            width: 350px;
            height: 350px;
            background: rgba(83, 224, 118, 0.08);
            border-radius: 50%;
            filter: blur(120px);
            bottom: -150px;
            right: -150px;
        }
        .glass-card {
            width: 100%;
            max-width: 450px;
            background: rgba(18, 18, 18, 0.75);
            backdrop-filter: blur(25px);
            border: 1px solid rgba(83, 224, 118, 0.1);
            border-radius: 25px;
            padding: 40px;
            position: relative;
            z-index: 10;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
        }
        .logo-icon {
            font-size: 35px;
            color: #53e076;
        }
        .title {
            color: #53e076;
            font-weight: 800;
        }
        .subtitle {
            color: #c7c6c6;
            font-size: 14px;
        }
        .form-control {
            background: #1b1b1b;
            border: 1px solid #333;
            color: #fff;
            height: 55px;
        }
        .form-control:focus {
            background: #1b1b1b;
            color: #fff;
            border-color: #53e076;
            box-shadow: none;
        }
        .form-label {
            color: #c7c6c6;
            font-size: 13px;
            font-weight: 600;
        }
        .forgot {
            margin-top: 10px;
            text-align: right;
        }
        .forgot a {
            color: #1db954;
            text-decoration: none;
        }
        .new {
            margin-top: 50px;
            text-align: center;
        }
        .new a {
            color: #1db954;
            text-decoration: none;
        }
        .g-recaptcha {
            margin-top: 25px;
            margin-bottom: 25px;
        }  
        .login-btn {
            width: 100%;
            height: 55px;
            border: none;
            border-radius: 50px;
            background: #1db954;
            color: #003914;
            font-weight: 700;
            transition: 0.3s;
        }
        .login-btn:hover {
            background: #53e076;
            transform: scale(1.02);
        }
        .status-message {
            margin-bottom: 24px;
            padding: 16px 18px;
            border-radius: 18px;
            font-size: 14px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(255, 255, 255, 0.04);
        }
        .status-message.success {
            color: #b9f6c8;
            border-color: rgba(83, 224, 118, 0.28);
            background: rgba(83, 224, 118, 0.12);
        }
        .status-message.error {
            color: #ffd1d1;
            border-color: rgba(255, 107, 107, 0.28);
            background: rgba(255, 107, 107, 0.1);
        }
    </style>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>
    <div class="bg-circle-one"></div>
    <div class="bg-circle-two"></div>
    <div class="glass-card">
        <div class="text-center mb-4">
            <div class="logo-icon">
                <i class="fa-solid fa-user"></i>
            </div>
            <h1 class="title mt-3">Melodix User</h1>
        </div>
        <?php if ($statusMessage !== "") { ?>
            <div class="status-message <?php echo htmlspecialchars($statusType); ?>">
                <?php echo htmlspecialchars($statusMessage); ?>
            </div>
        <?php } ?>
        <form method="POST">
            <div class="mb-4">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control rounded-4" placeholder="Enter Username or User ID" required>
            </div>
            <div class="mb-4">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control rounded-4" placeholder="Enter Password" pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}" required>
            </div>
            <p class="forgot">
                <a href="forgot_password.php">Forgot Password...?</a>
            </p>
            <!-- <div class="g-recaptcha" 
                data-sitekey="6LcXA_csAAAAAKej3pfK7d6bRrduQfkYduY2LLCw">
            </div> -->
            <button type="submit" class="login-btn" id="submitBtn">
                <span id="btnText">Login</span>
                <span id="btnLoader" style="display:none;">
                    <i class="fa fa-spinner fa-spin"></i>
                </span>
            </button>
        </form>
        <p class="new">
            New Account user
            <a href="user_signup.php">Sign Up</a>
        </p>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelector("form").addEventListener("submit", function()
        {
            document.getElementById("btnText").style.display = "none";
            document.getElementById("btnLoader").style.display = "inline";
            document.getElementById("submitBtn").disabled = true;
        });
    </script>
</body>
</html>