<?php
include("../../db_config.php");
if(isset($_POST['username']))
{
    $user_id = $_POST['userid'];
    $username = $_POST['username'];
    $email    = $_POST['email'];
    $password = $_POST['password'];
    $status = $_POST['status'];
    $DOB = $_POST['DOB'];
    $profile_image_name = "";
    if(isset($_FILES['profile_image']) && $_FILES['profile_image']['name'] != "")
    {
        $profile_image_name = time() . "_" . $_FILES['profile_image']['name'];
        $profile_tmp_name = $_FILES['profile_image']['tmp_name'];
        $profile_path = $profile_folder . $profile_image_name;
        move_uploaded_file($profile_tmp_name,$profile_path);
    }
    $insert_query = mysqli_query($conn,"
        INSERT INTO users(user_id,username,email,password,status,DOB,created_at)
        VALUES('$user_id','$username','$email','$password','$status','$DOB',NOW())
    ");
    if($insert_query)
    {
        echo "
            <script>
                alert('User added successfully');
                window.location.href='admin_manage_users.php';
            </script>
        ";
    }
    else
    {
        echo "
            <script>
                alert('Database insert failed');
                window.history.back();
            </script>
        ";
    }
}
?>