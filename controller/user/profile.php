<?php
session_start();
include("../../db_config.php");

if(!isset($_SESSION['user_id']))
{
    header("Location: user_login.php");
    exit();
}

$passwordPattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/';
$passwordHelpText = 'Password must be at least 8 characters and include uppercase, lowercase, number, and special character.';
$statusMessage = "";
$statusType = "error";
$showEditForm = false;
$user_id = mysqli_real_escape_string($conn, $_SESSION['user_id']);

$user_query = mysqli_query($conn, "SELECT * FROM users WHERE user_id='$user_id' LIMIT 1");
$user_data = $user_query ? mysqli_fetch_assoc($user_query) : null;

if(!$user_data)
{
    session_unset();
    session_destroy();
    header("Location: user_login.php");
    exit();
}

if(isset($_POST['edit_profile']))
{
    $showEditForm = true;
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $dob = mysqli_real_escape_string($conn, trim($_POST['dob']));
    $current_password = isset($_POST['current_password']) ? $_POST['current_password'] : "";
    $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : "";
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : "";

    if($username === "" || $email === "" || $dob === "")
    {
        $statusMessage = "Name, email, and date of birth are required.";
    }
    elseif(!filter_var($email, FILTER_VALIDATE_EMAIL))
    {
        $statusMessage = "Please enter a valid email address.";
    }
    else
    {
        $current_user_id = mysqli_real_escape_string($conn, $user_data['user_id']);
        $email_check_query = mysqli_query(
            $conn,
            "SELECT user_id FROM users WHERE email='$email' AND user_id!='".$current_user_id."' LIMIT 1"
        );
        $username_check_query = mysqli_query(
            $conn,
            "SELECT user_id FROM users WHERE username='$username' AND user_id!='".$current_user_id."' LIMIT 1"
        );

        if($email_check_query && mysqli_num_rows($email_check_query) > 0)
        {
            $statusMessage = "This email is already being used by another account.";
        }
        elseif($username_check_query && mysqli_num_rows($username_check_query) > 0)
        {
            $statusMessage = "This username is already being used by another account.";
        }
        else
        {
            $password_sql = "";

            if($new_password !== "" || $confirm_password !== "" || $current_password !== "")
            {
                if($current_password === "")
                {
                    $statusMessage = "Enter your current password to change it.";
                }
                elseif($current_password !== $user_data['password'])
                {
                    $statusMessage = "Current password is incorrect.";
                }
                elseif($new_password !== $confirm_password)
                {
                    $statusMessage = "New password and confirm password do not match.";
                }
                elseif(!preg_match($passwordPattern, $new_password))
                {
                    $statusMessage = $passwordHelpText;
                }
                else
                {
                    $escaped_new_password = mysqli_real_escape_string($conn, $new_password);
                    $password_sql = ", password='$escaped_new_password'";
                }
            }

            if($statusMessage === "")
            {
                $update_query = mysqli_query(
                    $conn,
                    "UPDATE users
                    SET username='$username',
                        email='$email',
                        DOB='$dob'
                        $password_sql
                    WHERE user_id='$current_user_id'"
                );

                if($update_query)
                {
                    $_SESSION['username'] = trim($_POST['username']);
                    $statusMessage = "Profile updated successfully.";
                    $statusType = "success";
                    $showEditForm = false;

                    $user_query = mysqli_query($conn, "SELECT * FROM users WHERE user_id='$current_user_id' LIMIT 1");
                    $user_data = $user_query ? mysqli_fetch_assoc($user_query) : $user_data;
                }
                else
                {
                    $statusMessage = "Unable to update your profile right now.";
                }
            }
        }
    }
}

$user_name = isset($user_data['username']) ? $user_data['username'] : "";
$user_email = isset($user_data['email']) && $user_data['email'] !== "" ? $user_data['email'] : "Not Available";
$user_dob = isset($user_data['DOB']) ? $user_data['DOB'] : "";
$first_letter = strtoupper(substr($user_name, 0, 1));
?>
<!DOCTYPE html>
<html class="dark" lang="en">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Melodix - Profile</title>
<link rel="icon" type="image/x-icon" href="../logo.png"/>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;700;800;900&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<style>
body{
    font-family:'Montserrat',sans-serif;
    background:#131313;
    color:#fff;
}
.glass-panel{
    background:rgba(18,18,18,0.6);
    backdrop-filter:blur(30px);
    -webkit-backdrop-filter:blur(30px);
}
.text-gradient{
    background:linear-gradient(135deg,#53e076 0%,#1db954 100%);
    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
}
.profile-hero{
    background:linear-gradient(180deg,#1db954 0%,#1b1b1b 45%,#131313 100%);
}
.profile-avatar{
    box-shadow:0 20px 60px rgba(0,0,0,0.45);
}
.profile-card{
    background:rgba(255,255,255,0.04);
    border:1px solid rgba(255,255,255,0.08);
}
.progress-container{
width:100%;
height:4px;
background:#444;
border-radius:999px;
cursor:pointer;
position:relative;
}
.progress{
height:100%;
width:0%;
background:#53e076;
border-radius:999px;
}
</style>
</head>
<body class="overflow-x-hidden">
<nav class="fixed top-0 right-0 w-full z-50 flex justify-between items-center h-16 px-8 bg-[#131313]/60 backdrop-blur-3xl">
    <a href="index.php">
        <div class="flex items-center gap-2">
            <span class="text-3xl font-black text-[#53e076]">Melodix</span>
        </div>
    </a>
    <div class="hidden md:flex items-center gap-6 relative">
        <h3>Welcome : <strong class="text-[#53e076]"><?php echo htmlspecialchars($user_name); ?></strong></h3>
        <div class="relative">
            <button onclick="toggleProfileDropdown()" class="w-10 h-10 rounded-full bg-[#53e076] text-black font-bold flex items-center justify-center border border-gray-700 hover:scale-105 transition-all duration-300">
                <?php echo htmlspecialchars($first_letter); ?>
            </button>
            <div id="profileDropdown" class="hidden absolute right-0 mt-3 w-52 overflow-hidden z-50 rounded-2xl border border-white/10 bg-[#1b1b1b] shadow-2xl">
                <div class="p-4 border-b border-white/10">
                    <p class="text-[#53e076] font-semibold text-center"><?php echo htmlspecialchars($user_name); ?></p>
                </div>
                <a href="profile.php" class="flex items-center gap-3 px-4 py-3 text-gray-300 hover:bg-[#2a2a2a] hover:text-[#53e076] transition">
                    <span class="material-symbols-outlined">person</span>
                    Profile
                </a>
                <a href="library.php" class="flex items-center gap-3 px-4 py-3 text-gray-300 hover:bg-[#2a2a2a] hover:text-[#53e076] transition">
                    <span class="material-symbols-outlined">library_music</span>
                    Library
                </a>
                <a href="logout.php" class="flex items-center gap-3 px-4 py-3 text-red-400 hover:bg-[#2a2a2a] transition">
                    <span class="material-symbols-outlined">logout</span>
                    Logout
                </a>
            </div>
        </div>
    </div>
</nav>

<aside class="hidden lg:flex flex-col p-6 gap-3 h-screen w-64 fixed left-0 top-15 bg-[#181818]/60 backdrop-blur-md border-r border-white/10 z-40 shadow-xl">
    <div class="mb-5"></div>
        <nav class="flex flex-col gap-4">
            <a class="flex items-center gap-3 p-2 text-gray-400 hover:text-[#53e076]" href="index.php">
                <span class="material-symbols-outlined">home</span>
                <span>Home</span>
            </a>
            <a class="flex items-center gap-3 p-2 text-gray-400 hover:text-[#53e076]" href="search.php">
                <span class="material-symbols-outlined">search</span>
                <span>Search</span>
            </a>
            <a class="flex items-center gap-3 p-2 text-gray-400 hover:text-[#53e076]" href="playlists.php">
                <span class="material-symbols-outlined">playlist_add</span>
                <span>Playlist</span>
            </a>
            <a class="flex items-center gap-3 p-2 text-gray-400 hover:text-[#53e076]" href="library.php">
                <span class="material-symbols-outlined">library_music</span>
                <span>Library</span>
            </a>
        </nav>
    </div>
</aside>

<main class="lg:ml-64 pt-16 pb-16 min-h-screen">
    <section class="profile-hero px-8 py-14">
        <div class="max-w-6xl mx-auto">
            <section class="relative px-8 py-16 overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-[#53e076]/10 via-[#131313] to-[#131313] -z-10">
        </div>
        <div class="max-w-4xl">
            <h2 class="text-7xl font-black leading-[0.9] mb-4">Music for
                <span class="text-gradient">everyone.</span>
            </h2>
        </div>
        <div class="absolute top-0 right-0 -translate-y-1/4 translate-x-1/4 w-[600px] h-[600px] bg-[#53e076]/20 rounded-full blur-[120px] -z-20">
        </div>
    </section>
            <div class="flex flex-col md:flex-row items-start md:items-end gap-8">
                <div class="profile-avatar w-36 h-36 md:w-52 md:h-52 rounded-full bg-[#53e076] text-black flex items-center justify-center text-6xl md:text-8xl font-black">
                    <?php echo htmlspecialchars($first_letter); ?>
                </div>
                <div class="flex-1">
                    <p class="uppercase tracking-[0.25em] text-sm text-white/70 mb-3">Profile</p>
                    <h1 class="text-5xl md:text-7xl font-black leading-none mb-4"><?php echo htmlspecialchars($user_name); ?></h1>
                    <p class="text-lg text-white/75 mb-2"><?php echo htmlspecialchars($user_email); ?></p>
                    <p class="text-sm text-white/60 mb-6">
                        DOB:
                        <?php echo $user_dob !== "" ? htmlspecialchars($user_dob) : "Not Available"; ?>
                    </p>
                    <div class="flex flex-wrap gap-3">
                        <button type="button" onclick="toggleEditProfile()" class="px-6 py-3 rounded-full bg-[#53e076] text-black font-bold hover:scale-105 transition">
                            Edit Profile
                        </button>
                        <a href="playlists.php" class="px-6 py-3 rounded-full border border-white/15 text-white font-semibold hover:border-[#53e076] hover:text-[#53e076] transition">
                            Open Playlists
                        </a>
                        <a href="library.php" class="px-6 py-3 rounded-full border border-white/15 text-white font-semibold hover:border-[#53e076] hover:text-[#53e076] transition">
                            Open Library
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="px-8 py-10">
        <div class="max-w-6xl mx-auto">
            <?php if($statusMessage !== "") { ?>
                <div class="mb-6 rounded-3xl px-5 py-4 <?php echo $statusType === 'success' ? 'border border-[#53e076]/30 bg-[#53e076]/10 text-[#c7ffd5]' : 'border border-red-500/30 bg-red-500/10 text-red-200'; ?>">
                    <?php echo htmlspecialchars($statusMessage); ?>
                </div>
            <?php } ?>

            <div id="editProfilePanel" class="<?php echo $showEditForm ? '' : 'hidden '; ?>mt-8 rounded-[32px] bg-[#181818] border border-white/10 p-6 md:p-8">
                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4 mb-8">
                    <div>
                        <p class="text-sm uppercase tracking-[0.25em] text-[#53e076]">Account Settings</p>
                        <h2 class="text-3xl font-black mt-3">Edit profile details</h2>
                        <p class="text-gray-400 mt-2">You can update your name, email, date of birth, and password here.</p>
                    </div>
                    <button type="button" onclick="toggleEditProfile()" class="self-start px-5 py-3 rounded-full border border-white/10 text-white hover:border-[#53e076] hover:text-[#53e076] transition">
                        Close
                    </button>
                </div>

                <form method="POST" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-sm text-gray-400 mb-2">Name</label>
                            <input
                            type="text"
                            name="username"
                            value="<?php echo htmlspecialchars($user_name); ?>"
                            required
                            class="w-full h-14 rounded-2xl border border-white/10 bg-[#202020] px-4 text-white placeholder:text-gray-500 focus:border-[#53e076] focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-400 mb-2">Email</label>
                            <input
                            type="email"
                            name="email"
                            value="<?php echo htmlspecialchars($user_email === 'Not Available' ? '' : $user_email); ?>"
                            required
                            class="w-full h-14 rounded-2xl border border-white/10 bg-[#202020] px-4 text-white placeholder:text-gray-500 focus:border-[#53e076] focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-400 mb-2">DOB</label>
                            <input
                            type="date"
                            name="dob"
                            value="<?php echo htmlspecialchars($user_dob); ?>"
                            required
                            class="w-full h-14 rounded-2xl border border-white/10 bg-[#202020] px-4 text-white placeholder:text-gray-500 focus:border-[#53e076] focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-400 mb-2">Current Password</label>
                            <input
                            type="password"
                            name="current_password"
                            placeholder="Required only if changing password"
                            class="w-full h-14 rounded-2xl border border-white/10 bg-[#202020] px-4 text-white placeholder:text-gray-500 focus:border-[#53e076] focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-400 mb-2">New Password</label>
                            <input
                            type="password"
                            name="new_password"
                            pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}"
                            title="<?php echo htmlspecialchars($passwordHelpText); ?>"
                            placeholder="Leave blank to keep current password"
                            class="w-full h-14 rounded-2xl border border-white/10 bg-[#202020] px-4 text-white placeholder:text-gray-500 focus:border-[#53e076] focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-400 mb-2">Confirm Password</label>
                            <input
                            type="password"
                            name="confirm_password"
                            pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}"
                            title="<?php echo htmlspecialchars($passwordHelpText); ?>"
                            placeholder="Repeat new password"
                            class="w-full h-14 rounded-2xl border border-white/10 bg-[#202020] px-4 text-white placeholder:text-gray-500 focus:border-[#53e076] focus:outline-none">
                        </div>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-[#121212] px-4 py-4 text-sm text-gray-400">
                        <?php echo htmlspecialchars($passwordHelpText); ?>
                    </div>

                    <button
                    type="submit"
                    name="edit_profile"
                    class="inline-flex h-14 items-center gap-2 rounded-full bg-[#53e076] px-8 text-base font-bold text-black transition hover:scale-105 hover:bg-[#6af08a]">
                        <span class="material-symbols-outlined">save</span>
                        Save Changes
                    </button>
                </form>
            </div>
        </div>
    </section>
</main>

<script>
function toggleProfileDropdown()
{
    const dropdown = document.getElementById("profileDropdown");
    dropdown.classList.toggle("hidden");
}

function toggleEditProfile()
{
    const panel = document.getElementById("editProfilePanel");
    panel.classList.toggle("hidden");
}

window.addEventListener("click", function(event)
{
    const dropdown = document.getElementById("profileDropdown");
    const profileButton = event.target.closest("button");

    if(!event.target.closest("#profileDropdown") && !profileButton?.onclick)
    {
        dropdown.classList.add("hidden");
    }
});
</script>
</body>
</html>