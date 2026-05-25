<?php
session_start();
include("../../db_config.php");
$admin_id = $_SESSION['admin_id'] ?? null;

if($admin_id !== null)
{
    mysqli_query($conn,"
    UPDATE admin
    SET is_logged_in='0'
    WHERE id='$admin_id'
    ");
}

session_destroy();

header("Location: admin_login.php");

exit();

?>
