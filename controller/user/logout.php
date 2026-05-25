<?php
session_start();
include("../../db_config.php");
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    mysqli_query($conn, "UPDATE users SET is_logged_in='0' WHERE user_id='$user_id'");
}
session_unset();
session_destroy();
header("Location: user_login.php");
exit();
?>