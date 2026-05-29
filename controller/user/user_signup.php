<?php
session_start();
include("../../db_config.php");

$passwordPattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/';
$passwordHelpText = 'Password must be at least 8 characters and include uppercase, lowercase, number, and special character.';
$statusMessage = "";
$statusType = "error";
$redirectTarget = "";
$redirectDelay = 1.6;
if(isset($_POST['username']) && isset($_POST['password']))
{
    $user_id = "";
    $username =  $_POST['username'];
    $email =  $_POST['email'];
    $dob =  $_POST['dob'];
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
    if(!preg_match($passwordPattern, $password))
    {
        $statusMessage = $passwordHelpText;
        $redirectTarget = "user_signup.php";
    }
    else
    {
        $check_query = mysqli_query($conn,"SELECT * FROM users WHERE email='$email'");

        if(mysqli_num_rows($check_query) > 0)
        {
            $statusMessage = "Account already exists. Redirecting to login...";
            $redirectTarget = "user_login.php";
        }
        else
        {
            $temporary_user_id = "TMP" . time() . rand(1000,9999);
            $profile_image_name = "";
            if(isset($_FILES['profile_image']) && $_FILES['profile_image']['name'] != "")
            {
                $profile_image_name = time() . "_" . $_FILES['profile_image']['name'];
                $profile_tmp_name = $_FILES['profile_image']['tmp_name'];
                $profile_path = $profile_folder . $profile_image_name;
                move_uploaded_file($profile_tmp_name,$profile_path);
            }
            $insert_query = mysqli_query($conn,"
                INSERT INTO users(user_id,username,email,password,DOB,created_at)
                VALUES('$temporary_user_id','$username','$email','$password','$dob',NOW())
            ");
            if($insert_query)
            {
                $generated_user_id = (string) mysqli_insert_id($conn);
                if($generated_user_id !== "" && $generated_user_id !== "0")
                {
                    $escaped_generated_user_id =  $generated_user_id;
                    $escaped_temporary_user_id =  $temporary_user_id;
                    $sync_user_id_query = mysqli_query($conn,"
                        UPDATE users
                        SET user_id='$escaped_generated_user_id'
                        WHERE id=" . (int) $generated_user_id . " AND user_id='$escaped_temporary_user_id'
                    ");
                    if($sync_user_id_query)
                    {
                        $user_id = $generated_user_id;
                    }
                    else
                    {
                        $statusMessage = "Account created, but user ID could not be generated automatically.";
                        $redirectTarget = "user_signup.php";
                    }
                }
                else
                {
                    $statusMessage = "Account created, but user ID could not be generated automatically.";
                    $redirectTarget = "user_signup.php";
                }

                if($statusMessage !== "")
                {
                    mysqli_query($conn,"DELETE FROM users WHERE id=" . (int) $generated_user_id);
                }
                else
                {
                    include("../../mail.php");

                    $mail->addAddress($email, $username);
                    $mail->isHTML(true);
                    $mail->Subject = "Melodix Account Created Successfully";
                    $mail->Body = "
                    <h2>Welcome to<span style='color: #158e46;'> Melodix</span></h2>
                    <p>Your account created successfully.</p>
                    <p><b>Username:</b> $username</p>
                    <p><b>Email:</b> $email</p>
                    ";
                    $mail->send();

                    $mail->clearAddresses();
                    $mail->Subject = "New User Registration";
                    $mail->addAddress("no-reply@gmail.com");
                    $mail->Body = "
                    <h2>New User Registered</h2>
                    <p>A new user created account in Melodix.</p>
                    <table cellpadding='10' border='1' style='border-collapse:collapse;'>
                        <tr>
                            <td><b>User ID</b></td>
                            <td>$user_id</td>
                        </tr>
                        <tr>
                            <td><b>Username</b></td>
                            <td>$username</td>
                        </tr>
                        <tr>
                            <td><b>Email</b></td>
                            <td>$email</td>
                        </tr>
                        <tr>
                            <td><b>DOB</b></td>
                            <td>$dob</td>
                        </tr>
                    </table>
                    ";
                    $mail->send();

                    $statusMessage = "User added successfully. Redirecting to login...";
                    $statusType = "success";
                    $redirectTarget = "user_login.php";
                }
            }
            else
            {
                $statusMessage = "Unable to create account right now. Redirecting back to sign up...";
                $redirectTarget = "user_signup.php";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Melodix SignUp</title>
    <link rel="icon" type="image/x-icon" href="../logo.png"/>
    <?php if($statusMessage !== "" && $redirectTarget !== "") { ?>
        <meta http-equiv="refresh" content="<?php echo (int) $redirectDelay; ?>; url=<?php echo htmlspecialchars($redirectTarget); ?>">
    <?php } ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" rel="stylesheet">
    <link href="../../assets/css/app.css" rel="stylesheet">
    <style>
        body{
            font-family:'Montserrat',sans-serif;
            background:#0e0e0e;
            color:#ffffff;
            height:100%;
            display:flex;
            flex-wrap:nowrap;
            justify-content:center;
            align-items:center;
            position:relative;
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
            font-size:40px;
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
        .new{
            margin-top:50px;
            text-align:centre;
        }
        .new a{
            color:#1db954;
            text-decoration:none;
        }
        .status-message{
            margin-bottom:24px;
            padding:16px 18px;
            border-radius:18px;
            font-size:14px;
            text-align:center;
            border:1px solid rgba(255,255,255,0.08);
            background:rgba(255,255,255,0.04);
        }
        .status-message.success{
            color:#b9f6c8;
            border-color:rgba(83,224,118,0.28);
            background:rgba(83,224,118,0.12);
        }
        .status-message.error{
            color:#ffd1d1;
            border-color:rgba(255,107,107,0.28);
            background:rgba(255,107,107,0.1);
        }
    </style>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>
    <div class="glass-card">
        <div class="text-center mb-4">
            <div class=logo-icon>
                <i class="fa-solid fa-user"></i>
            </div>
            <h1 class="title mt-3">
                Melodix User
            </h1>
            <p class="subtitle">
                SignUp User
            </p>
        </div>
        <?php if(isset($statusMessage) && $statusMessage !== "") { ?>
            <div class="status-message <?php echo htmlspecialchars($statusType); ?>">
                <?php echo htmlspecialchars($statusMessage); ?>
            </div>
        <?php } ?>
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-4">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control rounded-4" placeholder="Enter Username"required>
            </div>
            <div class="mb-4">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control rounded-4" placeholder="Enter your Email"required>
            </div>
            <div class="mb-4">
                <label class="form-label">DOB</label>
                <input type="date" name="dob" class="form-control rounded-4" placeholder="Enter your Date of birth"required>
            </div>
            <div class="mb-4">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control rounded-4" placeholder="Enter Password" pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}" title="<?php echo htmlspecialchars($passwordHelpText); ?>" required>
                <div class="form-text text-secondary mt-2">
                    <?php echo htmlspecialchars($passwordHelpText); ?>
                </div>
                <!-- <div class="g-recaptcha" 
                    data-sitekey="6LcXA_csAAAAAKej3pfK7d6bRrduQfkYduY2LLCw">
                </div> -->
            </div>
            <button type="submit" class="login-btn" id="submitBtn">
                <span id="btnText">Sign up</span>
                <span id="btnLoader" style="display:none;">
                    <i class="fa fa-spinner fa-spin"></i>
                </span>
            </button>
        </form>
        <p class="new">Already Existing user
            <a href="user_login.php">login</a>
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
