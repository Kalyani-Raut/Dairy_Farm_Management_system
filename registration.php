<?php
$message = "";
if($_SERVER["REQUEST_METHOD"] == "POST")
{
$username = $_POST["username"];
$useraddress = $_POST["useraddress"];
$MobileNo = $_POST["MobileNo"];
$gender = $_POST["gender"];
$role = $_POST["role"];
$email = $_POST["email"];
$password = $_POST["password"];
$uniquecode = $_POST["uniquecode"];
$username = trim($username);
$useraddress = trim($useraddress);
$MobileNo = trim($MobileNo);
$email = trim($email);
$uniquecode = trim($uniquecode);
if($username == "" || $useraddress == "" || $MobileNo == "" ||
$gender == "" || $role == "" || $email == "" ||
$password == "" || $uniquecode == "")
{
$message = "Please fill all fields.";
}
else if(!preg_match("/^[0-9]{10}$/", $MobileNo))
{
$message = "Mobile number must contain 10 digits.";
}
else if(!filter_var($email, FILTER_VALIDATE_EMAIL))
{
$message = "Please enter a valid email address.";
}
else
{
include("db.php");
/* Check whether email already exists */
$check_query = "
SELECT user_id
FROM users
WHERE email_id = $1
";
$check_result = pg_query_params(
$conn,
$check_query,
array($email)
);
if(pg_num_rows($check_result) > 0)
{
$message = "Email already registered.";
}
else
{
/* Get farm ID */
$farm_query = "
SELECT farm_id
FROM farm
LIMIT 1
";
$farm_result = pg_query($conn, $farm_query);
if(pg_num_rows($farm_result) == 0)
{
$message = "Farm information not found.";
}
else
{
$farm = pg_fetch_assoc($farm_result);
$farm_id = $farm["farm_id"];
/* Hash password */
$hashed_password = password_hash(
$password,
PASSWORD_DEFAULT
);
/* Insert user */
$insert_query = "
INSERT INTO users
(name, email_id, password, unique_code, phone_no, address, gender, role, farm_id)
VALUES
($1, $2, $3, $4, $5, $6, $7, $8, $9)
";
$insert_result = pg_query_params(
$conn,
$insert_query,
array($username, $email, $hashed_password, $uniquecode, $MobileNo, $useraddress, $gender, $role, $farm_id));
}
if($insert_result)
{
/* Get newly created user ID */
$user_query = "
SELECT user_id
FROM users
WHERE email_id = $1
";
$user_result = pg_query_params(
$conn,
$user_query,
array($email)
);
$user = pg_fetch_assoc($user_result);
$user_id = $user["user_id"];
/* Create Customer Record */
if($role == "customer")
{
$customer_query = "
INSERT INTO customer(user_id)
VALUES($1)
";
$customer_result = pg_query_params(
$conn,
$customer_query,
array($user_id)
);
if($customer_result)
{
$message = "Registration successful.";
header("Location: login.php");
}
else
{
$message = "User registered but customer record was not created.";
}
}
else if($role == "worker")
{
$worker_query = "
INSERT INTO workers
(description, user_id)
VALUES
($1, $2)
";
$worker_result = pg_query_params(
$conn,
$worker_query,
array(
"Worker",
$user_id
)
);
if($worker_result)
{
$message = "Registration successful.";
header("Location: login.php");
}
else
{
$message = "User registered but worker record was not created.";
}
}
else
{
$message = "Registration successful.";
header("Location: login.php");
}
}
}
pg_close($conn);
}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registration - Onkareshwar Dairy Farm</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="registration.css">
</head>
<body>
<header><h1>Registration</h1></header>
<!-- Navigation Bar -->
<nav class="navbar">
<a href="home.html" class="home-icon" title="Home"><i class="fa-solid fa-house"></i> Home</a>
</nav>
<div class="registration-container">
<h1>Onkareshwar Dairy Farm</h1>
<h2>Registration</h2>
<?php
if($message != "")
{
echo "<p class='message'>" . $message . "</p>";
}
?>
<form method="post" action="registration.php">
<label for="user_name">Full Name</label>
<input type="text" id="user_name" name="username" placeholder="Enter Name" required >
<label for="user_address">Address</label>
<input type="text" id="user_address" name="useraddress" placeholder="Enter Address" required >
<label for="user_phone_no">Mobile Number</label>
<input type="tel" id="user_phone_no" name="MobileNo" placeholder="Enter Mobile Number" pattern="[0-9]{10}" required >
<label>Gender</label>
<div class="radio-group">
<input type="radio" id="user_gender_male" name="gender" value="male" required >
<label for="user_gender_male">Male</label>
<input type="radio" id="user_gender_female" name="gender" value="female" >
<label for="user_gender_female">Female</label>
<input type="radio" id="user_gender_other" name="gender" value="other" >
<label for="user_gender_other">Other</label>
</div>
<label>Role</label>
<div class="radio-group">
<input type="radio" id="user_role_admin" name="role" value="admin" required >
<label for="user_role_admin">Admin</label>
<input type="radio" id="user_role_worker" name="role" value="worker" >
<label for="user_role_worker">Worker</label>
<input type="radio" id="user_role_customer" name="role" value="customer" >
<label for="user_role_customer">Customer</label>
</div>
<label for="user_email">Email ID</label>
<input type="email" id="user_email" name="email" placeholder="Enter Email" required >
<label for="password">Password</label>
<input type="password" id="password" name="password" placeholder="Enter Password" required >
<label for="uniquecode">Unique Code</label>
<input type="password" id="uniquecode" name="uniquecode" placeholder="Enter Unique Code" required >
<div class="button-group">
<input type="submit" value="Register" class="register-button" >
<input type="reset" value="Reset" class="reset-button" >
</div>
</form>
</div>
<footer>
<p>&copy; rk.solutions.pvt.ltd</p>
</footer>
</body>
</html>
