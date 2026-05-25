<?php
include("../../db_config.php");
if(isset($_GET['user_id']))
{
    $user_id=$_GET['user_id'];
    $delete_query=mysqli_query( $conn, "DELETE FROM users WHERE user_id='$user_id'");
    if($delete_query)
    {
        echo "
        <script>
            alert('User deleted successfully');
            window.location.href='admin_manage_users.php';
        </script>
        ";
    }
    else
    {
        echo "
        <script>
            alert('Failed to delete user');
            window.location.href='admin_manage_users.php';
        </script>
        ";
    }
}
else
{
    header('Location: admin_manage_users.php');
    exit();
}
?>