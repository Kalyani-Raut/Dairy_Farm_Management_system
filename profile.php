<?php
session_start();
if(!isset($_SESSION["user_id"]))
{
header("Location: login.php");
exit();
}
include("db.php");
$user_id = $_SESSION["user_id"];
$message = "";
$success_message = "";
/* =========================
Update Profile
========================= */
if(isset($_POST["update_profile"]))
{
$name = trim($_POST["name"]);
$address = trim($_POST["address"]);
$phone_no = trim($_POST["phone_no"]);
$gender = $_POST["gender"];
$email_id = trim($_POST["email_id"]);
if($name == "" || $address == "" || $phone_no == "" ||
$gender == "" || $email_id == "")
{
$message = "Please fill all fields.";
}
else if(!preg_match("/^[0-9]{10}$/", $phone_no))
{
$message = "Mobile number must contain 10 digits.";
}
else if(!filter_var($email_id, FILTER_VALIDATE_EMAIL))
{
$message = "Please enter a valid email address.";
}
else
{
/* Check whether email belongs to another user */
$check_email_query = "
SELECT user_id
FROM users
WHERE email_id = $1
AND user_id != $2
";
$check_email_result = pg_query_params(
$conn,
$check_email_query,
array($email_id, $user_id)
);
if(pg_num_rows($check_email_result) > 0)
{
$message = "Email is already registered with another user.";
}
else
{
$update_query = "
UPDATE users
SET
name = $1,
address = $2,
phone_no = $3,
gender = $4,
email_id = $5
WHERE user_id = $6
";
$update_result = pg_query_params(
$conn,
$update_query,
array(
$name,
$address,
$phone_no,
$gender,
$email_id,
$user_id
)
);
if($update_result)
{
$_SESSION["name"] = $name;
$_SESSION["email"] = $email_id;
$success_message = "Profile updated successfully.";
}
else
{
$message = "Profile update failed.";
}
}
}
}
/* =========================
Change Password
========================= */
if(isset($_POST["change_password"]))
{
$current_password = $_POST["current_password"];
$new_password = $_POST["new_password"];
$confirm_password = $_POST["confirm_password"];
if($current_password == "" ||
$new_password == "" ||
$confirm_password == "")
{
$message = "Please fill all password fields.";
}
else if($new_password != $confirm_password)
{
$message = "New password and confirm password do not match.";
}
else if(strlen($new_password) < 6)
{
$message = "New password must contain at least 6 characters.";
}
else
{
/* Get current password */
$password_query = "
SELECT password
FROM users
WHERE user_id = $1
";
$password_result = pg_query_params(
$conn,
$password_query,
array($user_id)
);
if(pg_num_rows($password_result) == 0)
{
$message = "User information not found.";
}
else
{
$user_password = pg_fetch_assoc($password_result);
/* Verify current password */
if(!password_verify(
$current_password,
$user_password["password"]
))
{
$message = "Current password is incorrect.";
}
else
{
/* Hash new password */
$hashed_password = password_hash(
$new_password,
PASSWORD_DEFAULT
);
$update_password_query = "
UPDATE users
SET password = $1
WHERE user_id = $2
";
$update_password_result = pg_query_params(
$conn,
$update_password_query,
array(
$hashed_password,
$user_id
)
);
if($update_password_result)
{
$success_message = "Password changed successfully.";
}
else
{
$message = "Password change failed.";
}
}
}
}
}
/* =========================
Get User Information
========================= */
$profile_query = "
SELECT
user_id,
name,
address,
phone_no,
gender,
role,
email_id
FROM users
WHERE user_id = $1
";
$profile_result = pg_query_params(
$conn,
$profile_query,
array($user_id)
);
if(pg_num_rows($profile_result) == 0)
{
$message = "User information not found.";
}
else
{
$user = pg_fetch_assoc($profile_result);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profile - Onkareshwar Dairy Farm</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="profile.css">
</head>
<body>
<header><h1>Profile</h1></header>
<!-- Navigation -->
<nav class="navbar">
<a href="home.html" class="home-icon">
<i class="fa-solid fa-house"></i>
Home
</a>
</nav>
<div class="profile-container">
<div class="profile-icon"><i class="fa-solid fa-user"></i></div>
<h1>My Profile</h1>
<!-- Messages -->
<?php
if($message != "")
{
echo "<p class='message'>" . htmlspecialchars($message) . "</p>";
}
if($success_message != "")
{
echo "<p class='success-message'>" . htmlspecialchars($success_message) . "</p>";
}
?>
<!-- =========================
Profile Information
========================= -->
<div id="profileInformation" class="profile-information">
<div class="profile-row">
<label>Full Name</label>
<p><?php echo htmlspecialchars($user["name"]); ?></p>
</div>
<div class="profile-row">
<label>Address</label>
<p><?php echo htmlspecialchars($user["address"]); ?></p>
</div>
<div class="profile-row">
<label>Mobile Number</label>
<p><?php echo htmlspecialchars($user["phone_no"]); ?></p>
</div>
<div class="profile-row">
<label>Gender</label>
<p><?php echo htmlspecialchars($user["gender"]); ?></p>
</div>
<div class="profile-row">
<label>Role</label>
<p><?php echo htmlspecialchars($user["role"]); ?></p>
</div>
<div class="profile-row">
<label>Email ID</label>
<p><?php echo htmlspecialchars($user["email_id"]); ?></p>
</div>
</div>
<!-- =========================
Update Profile Form
========================= -->
<div id="updateProfileForm" class="profile-form">
<h2>Update Profile</h2>
<form method="POST" action="profile.php">
<label>Full Name</label>
<input type="text" name="name" value="<?php echo htmlspecialchars($user["name"]); ?>"
required
>
<label>Address</label>
<textarea name="address" required ><?php echo htmlspecialchars($user["address"]); ?></textarea>
<label>Mobile Number</label>
<input type="text" name="phone_no" value="<?php echo htmlspecialchars($user["phone_no"]); ?>"
maxlength="10"
required
>
<label>Gender</label>
<select name="gender" required>
<option value="Male" <?php if($user["gender"] == "Male") { echo "selected"; } ?>
>
Male
</option>
<option value="Female" <?php if($user["gender"] == "Female") { echo "selected"; } ?>
>
Female
</option>
<option value="Other" <?php if($user["gender"] == "Other") { echo "selected"; } ?>
>
Other
</option>
</select>
<label>Email ID</label>
<input type="email" name="email_id" value="<?php echo htmlspecialchars($user["email_id"]); ?>"
required
>
<div class="form-buttons">
<button type="submit" name="update_profile" class="save-button" >
<i class="fa-solid fa-save"></i>
Save Profile
</button>
<button type="button" class="cancel-button" onclick="hideUpdateProfile()" >
Cancel
</button>
</div>
</form>
</div>
<!-- =========================
Change Password Form
========================= -->
<div id="changePasswordForm" class="profile-form">
<h2>Change Password</h2>
<form method="POST" action="profile.php">
<label>Current Password</label>
<input type="password" name="current_password" required >
<label>New Password</label>
<input type="password" name="new_password" required >
<label>Confirm New Password</label>
<input type="password" name="confirm_password" required >
<div class="form-buttons">
<button type="submit" name="change_password" class="save-button" >
<i class="fa-solid fa-key"></i>
Change Password
</button>
<button type="button" class="cancel-button" onclick="hideChangePassword()" >
Cancel
</button>
</div>
</form>
</div>
<!-- =========================
Profile Buttons
========================= -->
<div class="profile-buttons">
<button type="button" class="profile-button" onclick="showUpdateProfile()" >
<i class="fa-solid fa-pen"></i>
Update Profile
</button>
<button type="button" class="profile-button" onclick="showChangePassword()" >
<i class="fa-solid fa-key"></i>
Change Password
</button>
<a href="logout.php" class="logout-button" >
<i class="fa-solid fa-right-from-bracket"></i>
Logout
</a>
</div>
</div>
<footer>
<p>&copy; rk.solutions.pvt.ltd</p>
</footer>
<script>
/* Show Update Profile */
function showUpdateProfile()
{
document.getElementById("profileInformation").style.display = "none";
document.getElementById("updateProfileForm").style.display = "block";
document.getElementById("changePasswordForm").style.display = "none";
}
/* Hide Update Profile */
function hideUpdateProfile()
{
document.getElementById("profileInformation").style.display = "block";
document.getElementById("updateProfileForm").style.display = "none";
}
/* Show Change Password */
function showChangePassword()
{
document.getElementById("profileInformation").style.display = "none";
document.getElementById("updateProfileForm").style.display = "none";
document.getElementById("changePasswordForm").style.display = "block";
}
/* Hide Change Password */
function hideChangePassword()
{
document.getElementById("profileInformation").style.display = "block";
document.getElementById("changePasswordForm").style.display = "none";
}
</script>
</body>
</html>
<?php
pg_close($conn);
?>
