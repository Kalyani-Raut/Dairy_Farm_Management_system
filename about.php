<?php
include("db.php");
$farm_query = "
    SELECT farm_id, name, address, location, phone_no, email_id, establish_date, owner
    FROM farm
    LIMIT 1
";
$farm_result = pg_query($conn, $farm_query);
if(pg_num_rows($farm_result) > 0)
{
    $farm = pg_fetch_assoc($farm_result);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Onkareshwar Dairy Farm</title>
    <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="about.css">
</head>
<body>
<header>
    <h1>About Us</h1>
</header>
<nav class="navbar">
    <a href="home.html" class="home-icon" title="Home">
        <i class="fa-solid fa-house"></i> Home
    </a>
</nav>
<main>
    <!-- Farm Information Section -->
    <div class="farm-information">
        <h2>Farm Information</h2>
        <div class="farm-data">
            <p>
                <strong>Farm Name:</strong>
                <?php echo $farm["name"]; ?>
            </p>
            <p>
                <strong>Address:</strong>
                <?php echo $farm["address"]; ?>
            </p>
            <p>
                <strong>Location:</strong>
                <?php echo $farm["location"]; ?>
            </p>
            <p>
                <strong>Phone Number:</strong>
                <?php echo $farm["phone_no"]; ?>
            </p>
            <p>
                <strong>Email ID:</strong>
                <?php echo $farm["email_id"]; ?>
            </p>
            <p>
                <strong>Established Date:</strong>
                <?php echo $farm["establish_date"]; ?>
            </p>
            <p>
                <strong>Owner:</strong>
                <?php echo $farm["owner"]; ?>
            </p>
        </div>
    </div>
    <!-- Owner Section -->
    <div class="owner-section">
        <div class="owner-image">
            <img src="images/owner.jpg" alt="Owner">
        </div>
        <div class="owner-info">
            <h2>About the Owner</h2>
            <h3>Mr.Swapnil Raut</h3>
            <p>
                Onkareshwar Dairy Farm is managed by Mr. Swapnil Raut,
                who is dedicated to providing fresh and quality dairy
                products to customers.
            </p>
            <p>
                He focuses on proper animal care, milk quality,
                farm hygiene and customer satisfaction.
            </p>
        </div>
    </div>
    <!-- Employees Section -->
    <div class="employees-section">
        <h2>Our Employees</h2>
        <div class="employee-container">
            <div class="employee">
                <img src="images/emp1.jpg" alt="Employee">
                <h3>Ms.Riya Shah</h3>
            </div>
            <div class="employee">
                <img src="images/emp2.jpg" alt="Employee">
                <h3>Ms.Smita Rahane</h3>
            </div>
            <div class="employee">
                <img src="images/emp3.jpg" alt="Employee">
                <h3>Mr.shreyas Nehe</h3>
            </div>
            <div class="employee">
                <img src="images/emp4.jpg" alt="Employee">
                <h3>Mr.varun Agrawal</h3>
            </div>
        </div>
    </div>
    <!-- Contact Us Section -->
    <div class="contact-section">
        <h2>Contact Us</h2>
        <p>
            <i class="fa-solid fa-location-dot"></i>
            Onkareshwar Dairy Farm, Maharashtra
        </p>
        <p>
            <i class="fa-solid fa-phone"></i>
            +91 9876543210
        </p>
        <p>
            <i class="fa-solid fa-envelope"></i>
            onkareshwardairy@gmail.com
        </p>
    </div>
</main>
<footer>
    <p>&copy; rk.solutions.pvt.ltd</p>
</footer>
</body>
</html>
<?php
pg_close($conn);
?>