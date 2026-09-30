<?php

session_start();

$message = "";

if($_SERVER["REQUEST_METHOD"] == "POST")
{
    $email = $_POST["email"];
    $password = $_POST["password"];
    $uniquecode = $_POST["uniquecode"];

    $email = trim($email);
    $uniquecode = trim($uniquecode);

    if($email == "" || $password == "" || $uniquecode == "")
    {
        $message = "Please fill all fields.";
    }
    else if(!filter_var($email, FILTER_VALIDATE_EMAIL))
    {
        $message = "Please enter a valid email address.";
    }
    else
    {
        include("db.php");

        $login_query = "
            SELECT user_id, name, role, email_id, password, unique_code
            FROM users
            WHERE email_id = $1
        ";

        $login_result = pg_query_params(
            $conn,
            $login_query,
            array($email)
        );

        if(pg_num_rows($login_result) == 0)
        {
            $message = "Invalid email, password or unique code.";
        }
        else
        {
            $user = pg_fetch_assoc($login_result);

            if(password_verify($password, $user["password"]) &&
               $uniquecode == $user["unique_code"])
            {
                $_SESSION["user_id"] = $user["user_id"];
                $_SESSION["name"] = $user["name"];
                $_SESSION["role"] = $user["role"];
                $_SESSION["email"] = $user["email_id"];

                header("Location: profile.php");

            }
            else
            {
                $message = "Invalid email, password or unique code.";
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
    <title>Login - Onkareshwar Dairy Farm</title>
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="login.css">
</head>
<body>
<header>
    <h1>Login</h1>
</header>
<nav class="navbar">
    <a href="home.html" class="home-icon" title="Home">
        <i class="fa-solid fa-house"></i> Home
    </a>
</nav>
<div class="login-container">
    <h1>Onkareshwar Dairy Farm</h1>
    <h2>Login</h2>
    <?php
    if($message != "")
    {
        echo "<p class='message'>" . $message . "</p>";
    }
    ?>
    <form method="post" action="login.php">
        <label for="user_email">Email ID</label>
        <input
            type="email"
            id="user_email"
            name="email"
            placeholder="Enter Email"
            required>
        <label for="password">Password</label>
        <input
            type="password"
            id="password"
            name="password"
            placeholder="Enter Password"
            required>
        <label for="uniquecode">Unique Code</label>
        <input
            type="password"
            id="uniquecode"
            name="uniquecode"
            placeholder="Enter Unique Code"
            required>
        <div class="button-group">
            <input
                type="submit"
                value="Login"
                class="login-button">
            <input
                type="reset"
                value="Reset"
                class="reset-button">
        </div>
    </form>
</div>
<footer>
    <p>
        &copy; rk.solutions.pvt.ltd
    </p>
</footer>
</body>
</html>