<?php
session_start();
include("../../db_config.php");
$passwordPattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/';
$passwordHelpText = 'Password must be at least 8 characters and include uppercase, lowercase, number, and special character.';
$statusMessage = "";
$statusType = "error";
$redirectTarget = "";
$redirectDelay = 1;

if (isset($_POST['email']) && isset($_POST['password']) && isset($_POST['confirm_password'])) {
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $statusMessage = "New password and confirm password do not match.";
        $redirectTarget = "forgot_password.php";
    } elseif (!preg_match($passwordPattern, $password)) {
        $statusMessage = $passwordHelpText;
        $redirectTarget = "forgot_password.php";
    } else {
        $query = mysqli_query($conn, "SELECT * FROM users WHERE email='$email' LIMIT 1");

        if (mysqli_num_rows($query) > 0) {
            $row = mysqli_fetch_assoc($query);
            $user_id = mysqli_real_escape_string($conn, $row['user_id']);
            $update_password = mysqli_query($conn, "UPDATE users SET password='$password' WHERE user_id='$user_id'");

            if ($update_password) {
                include("../../mail.php");

                $mail->addAddress($email, $row['username']);
                $mail->isHTML(true);
                $mail->Subject = "Melodix Password Reset Successful";
                $mail->Body = "
                <h2>Password Reset Successful</h2>
                <p>Your Melodix password has been updated successfully.</p>
                <p><b>Username:</b> {$row['username']}</p>
                <p>If you did not make this change, please contact support immediately.</p>
                <p>Best regards,<br>Melodix Team</p>";
                $mail->send();
                $statusMessage = "Password updated successfully. Redirecting to login...";
                $statusType = "success";
                $redirectTarget = "user_login.php";
            } else {
                $statusMessage = "Unable to update password right now. Please try again.";
                $redirectTarget = "forgot_password.php";
            }
        } else {
            $statusMessage = "No account found for this email address.";
            $redirectTarget = "forgot_password.php";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Melodix Forgot Password</title>
    <link rel="icon" type="image/x-icon" href="../logo.png"/>
    <?php if ($statusMessage !== "" && $redirectTarget !== "") { ?>
        <meta http-equiv="refresh" content="<?php echo (int) $redirectDelay; ?>;url=<?php echo htmlspecialchars($redirectTarget); ?>">
    <?php } ?>
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

        .new {
            margin-top: 50px;
            text-align: center;
        }

        .new a {
            color: #1db954;
            text-decoration: none;
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
</head>

<body>

    <div class="bg-circle-one"></div>
    <div class="bg-circle-two"></div>

    <div class="glass-card">

        <div class="text-center mb-4">
            <div class="logo-icon">
                <i class="fa-solid fa-user"></i>
            </div>

            <h1 class="title mt-3">
                Melodix User
            </h1>

            <p class="subtitle">
                Reset your password
            </p>
        </div>

        <?php if ($statusMessage !== "") { ?>
            <div class="status-message <?php echo htmlspecialchars($statusType); ?>">
                <?php echo htmlspecialchars($statusMessage); ?>
            </div>
        <?php } ?>

        <form method="POST">

            <div class="mb-4">
                <label class="form-label">
                    Email
                </label>

                <input
                    type="email"
                    name="email"
                    class="form-control rounded-4"
                    placeholder="Enter your registered email"
                    required>
            </div>

            <div class="mb-4">
                <label class="form-label">
                    New Password
                </label>

                <input
                    type="password"
                    name="password"
                    class="form-control rounded-4"
                    placeholder="Enter new password"
                    pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}"
                    title="<?php echo htmlspecialchars($passwordHelpText); ?>"
                    required>
                <div class="form-text text-secondary mt-2">
                    <?php echo htmlspecialchars($passwordHelpText); ?>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">
                    Confirm Password
                </label>

                <input
                    type="password"
                    name="confirm_password"
                    class="form-control rounded-4"
                    placeholder="Confirm new password"
                    pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}"
                    title="<?php echo htmlspecialchars($passwordHelpText); ?>"
                    required>
            </div>

            <button type="submit" class="login-btn">
                Reset Password
            </button>

        </form>

        <p class="new">
            Remember your password?
            <a href="user_login.php">Login</a>
        </p>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
