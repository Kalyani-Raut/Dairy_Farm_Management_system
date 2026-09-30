<?php

session_start();

include("db.php");


/*
    Check Login
*/

if(!isset($_SESSION["user_id"]))
{
    $message = "Please login first to access the dashboard.";
}
else
{
    $user_id = $_SESSION["user_id"];
    $role = $_SESSION["role"];

    if($role != "admin" && $role != "worker")
    {
        $message = "You don't have permission to access the dashboard.";
    }
}


/*
    If Customer or Not Logged In
*/

if(!isset($_SESSION["user_id"]) || 
   ($_SESSION["role"] != "admin" && $_SESSION["role"] != "worker"))
{

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        Dashboard - Onkareshwar Dairy Farm
    </title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <link rel="stylesheet"
          href="dashboard.css">

</head>

<body>

<header>

    <h1>
        Onkareshwar Dairy Farm
    </h1>

</header>


<nav class="navbar">

    <a href="home.html">

        <i class="fa-solid fa-house"></i>

        Home

    </a>

</nav>


<div class="permission-container">

    <div class="permission-icon">

        <i class="fa-solid fa-lock"></i>

    </div>

    <h2>

        Access Denied

    </h2>

    <p>

        <?php

        echo htmlspecialchars($message);

        ?>

    </p>


    <a href="login.php"
       class="login-button">

        Login

    </a>

</div>


<footer>

    <p>

        &copy; rk.solutions.pvt.ltd

    </p>

</footer>

</body>

</html>

<?php

    exit();

}


/*
    Logged-in Admin / Worker
*/

$message = "";

$success_message = "";


/*
    Get Messages
*/

if(isset($_SESSION["dashboard_success"]))
{
    $success_message =
        $_SESSION["dashboard_success"];

    unset($_SESSION["dashboard_success"]);
}

if(isset($_SESSION["dashboard_message"]))
{
    $message =
        $_SESSION["dashboard_message"];

    unset($_SESSION["dashboard_message"]);
}


/*
========================================================
ADMIN / WORKER OPERATIONS
========================================================
*/


/*
    Add Product
*/

if(isset($_POST["add_product"]))
{

    $name = trim($_POST["name"]);
    $image = trim($_POST["image"]);
    $price = $_POST["price"];
    $quantity = trim($_POST["quantity"]);
    $stock = $_POST["stock"];
    $making_date = $_POST["making_date"];
    $expiry_date = $_POST["expiry_date"];
    $ingredients = trim($_POST["ingredients"]);
    $nutrition_value = trim($_POST["nutrition_value"]);
    $description = trim($_POST["description"]);


    if($name == "" ||
       $price == "" ||
       $quantity == "" ||
       $stock == "" ||
       $making_date == "" ||
       $expiry_date == "")
    {
        $_SESSION["dashboard_message"] =
            "Please fill all required product fields.";
    }
    else
    {

        $product_query = "
            INSERT INTO product
            (
                name,
                image,
                price,
                quantity,
                stock,
                making_date,
                expiry_date,
                ingredients,
                nutrition_value,
                description
            )
            VALUES
            (
                $1,
                $2,
                $3,
                $4,
                $5,
                $6,
                $7,
                $8,
                $9,
                $10
            )
        ";

        $product_result = pg_query_params(
            $conn,
            $product_query,
            array(
                $name,
                $image,
                $price,
                $quantity,
                $stock,
                $making_date,
                $expiry_date,
                $ingredients,
                $nutrition_value,
                $description
            )
        );


        if($product_result)
        {
            $_SESSION["dashboard_success"] =
                "Product added successfully.";
        }
        else
        {
            $_SESSION["dashboard_message"] =
                "Unable to add product.";
        }
    }

    header("Location: dashboard.php");
    exit();
}
/*
    Update Product
*/

if(isset($_POST["update_product"]))
{

    $product_id = $_POST["product_id"];
    $name = trim($_POST["name"]);
    $image = trim($_POST["image"]);
    $price = $_POST["price"];
    $quantity = trim($_POST["quantity"]);
    $stock = $_POST["stock"];
    $making_date = $_POST["making_date"];
    $expiry_date = $_POST["expiry_date"];
    $ingredients = trim($_POST["ingredients"]);
    $nutrition_value = trim($_POST["nutrition_value"]);
    $description = trim($_POST["description"]);


    if($name == "" ||
       $price == "" ||
       $quantity == "" ||
       $stock == "" ||
       $making_date == "" ||
       $expiry_date == "")
    {
        $_SESSION["dashboard_message"] =
            "Please fill all required product fields.";
    }
    else
    {

        $update_product_query = "
            UPDATE product
            SET
                name = $1,
                image = $2,
                price = $3,
                quantity = $4,
                stock = $5,
                making_date = $6,
                expiry_date = $7,
                ingredients = $8,
                nutrition_value = $9,
                description = $10
            WHERE product_id = $11
        ";

        $update_product_result = pg_query_params(
            $conn,
            $update_product_query,
            array(
                $name,
                $image,
                $price,
                $quantity,
                $stock,
                $making_date,
                $expiry_date,
                $ingredients,
                $nutrition_value,
                $description,
                $product_id
            )
        );


        if($update_product_result)
        {
            $_SESSION["dashboard_success"] =
                "Product updated successfully.";
        }
        else
        {
            $_SESSION["dashboard_message"] =
                "Unable to update product.";
        }
    }

    header("Location: dashboard.php");
    exit();
}

/*
    Delete Product
*/

if(isset($_POST["delete_product"]))
{

    $product_id = $_POST["product_id"];


    $delete_query = "
        DELETE FROM product
        WHERE product_id = $1
    ";


    $delete_result = pg_query_params(
        $conn,
        $delete_query,
        array($product_id)
    );


    if($delete_result)
    {
        $_SESSION["dashboard_success"] =
            "Product deleted successfully.";
    }
    else
    {
        $_SESSION["dashboard_message"] =
            "Unable to delete product. It may be used in an order.";
    }


    header("Location: dashboard.php");
    exit();
}


/*
    Add Milk Collection
*/

if(isset($_POST["add_milk"]))
{

    $date = $_POST["milk_date"];
    $time = $_POST["milk_time"];
    $supplier_id = $_POST["supplier_id"];
    $milk_quality = trim($_POST["milk_quality"]);
    $milk_quantity = $_POST["milk_quantity"];
    $compensation = $_POST["compensation"];
    $description = trim($_POST["milk_description"]);


    if($date == "" ||
       $time == "" ||
       $supplier_id == "" ||
       $milk_quality == "" ||
       $milk_quantity == "" ||
       $compensation == "")
    {
        $_SESSION["dashboard_message"] =
            "Please fill all required milk collection fields.";
    }
    else
    {

        $milk_query = "
            INSERT INTO milk_collection
            (
                date,
                time,
                supplier_id,
                milk_quality,
                milk_quantity,
                compensation,
                description
            )
            VALUES
            (
                $1,
                $2,
                $3,
                $4,
                $5,
                $6,
                $7
            )
        ";


        $milk_result = pg_query_params(
            $conn,
            $milk_query,
            array(
                $date,
                $time,
                $supplier_id,
                $milk_quality,
                $milk_quantity,
                $compensation,
                $description
            )
        );


        if($milk_result)
        {
            $_SESSION["dashboard_success"] =
                "Milk collection record added successfully.";
        }
        else
        {
            $_SESSION["dashboard_message"] =
                "Unable to add milk collection record.";
        }
    }


    header("Location: dashboard.php");
    exit();
}


/*
    Delete Milk Collection
*/

if(isset($_POST["delete_milk"]))
{

    $collection_id =
        $_POST["collection_id"];


    $delete_milk_query = "
        DELETE FROM milk_collection
        WHERE collection_id = $1
    ";


    $delete_milk_result = pg_query_params(
        $conn,
        $delete_milk_query,
        array($collection_id)
    );


    if($delete_milk_result)
    {
        $_SESSION["dashboard_success"] =
            "Milk collection record deleted successfully.";
    }
    else
    {
        $_SESSION["dashboard_message"] =
            "Unable to delete milk collection record.";
    }


    header("Location: dashboard.php");
    exit();
}


/*
    Worker / Admin Update Order Status
*/

if(isset($_POST["update_order_status"]))
{

    $order_id = $_POST["order_id"];
    $status = $_POST["status"];


    $update_order_query = "
        UPDATE orders
        SET status = $1
        WHERE order_id = $2
    ";


    $update_order_result = pg_query_params(
        $conn,
        $update_order_query,
        array(
            $status,
            $order_id
        )
    );


    if($update_order_result)
    {
        $_SESSION["dashboard_success"] =
            "Order status updated successfully.";
    }
    else
    {
        $_SESSION["dashboard_message"] =
            "Unable to update order status.";
    }


    header("Location: dashboard.php");
    exit();
}


/*
========================================================
ADMIN ONLY OPERATIONS
========================================================
*/


if($role == "admin")
{

    /*
        Update Farm Information
    */

    if(isset($_POST["update_farm"]))
    {
        $farm_id = $_POST["farm_id"];
        $farm_name = trim($_POST["farm_name"]);
        $farm_address = trim($_POST["farm_address"]);
        $farm_location = trim($_POST["farm_location"]);
        $farm_phone = trim($_POST["farm_phone"]);
        $farm_email = trim($_POST["farm_email"]);
        $farm_date = $_POST["farm_date"];
        $farm_owner = trim($_POST["farm_owner"]);

        if($farm_name == "" ||
           $farm_address == "" ||
           $farm_location == "" ||
           $farm_phone == "" ||
           $farm_email == "" ||
           $farm_date == "" ||
           $farm_owner == "")
        {
            $_SESSION["dashboard_message"] =
                "Please fill all farm information.";
        }
        else if(!filter_var($farm_email, FILTER_VALIDATE_EMAIL))
        {
            $_SESSION["dashboard_message"] =
                "Please enter a valid farm email address.";
        }
        else
        {
            $update_farm_query = "
                UPDATE farm
                SET
                    name = $1,
                    address = $2,
                    location = $3,
                    phone_no = $4,
                    email_id = $5,
                    establish_date = $6,
                    owner = $7
                WHERE farm_id = $8
            ";

            $update_farm_result = pg_query_params(
                $conn,
                $update_farm_query,
                array(
                    $farm_name,
                    $farm_address,
                    $farm_location,
                    $farm_phone,
                    $farm_email,
                    $farm_date,
                    $farm_owner,
                    $farm_id
                )
            );

            if($update_farm_result)
            {
                $_SESSION["dashboard_success"] =
                    "Farm information updated successfully.";
            }
            else
            {
                $_SESSION["dashboard_message"] =
                    "Unable to update farm information.";
            }
        }

        header("Location: dashboard.php");
        exit();
    }


    /*
        Delete User
    */

    if(isset($_POST["delete_user"]))
    {

        $delete_user_id =
            $_POST["user_id"];


        $delete_user_query = "
            DELETE FROM users
            WHERE user_id = $1
        ";


        $delete_user_result = pg_query_params(
            $conn,
            $delete_user_query,
            array($delete_user_id)
        );


        if($delete_user_result)
        {
            $_SESSION["dashboard_success"] =
                "User deleted successfully.";
        }
        else
        {
            $_SESSION["dashboard_message"] =
                "Unable to delete user. Related records may exist.";
        }


        header("Location: dashboard.php");
        exit();
    }


    /*
        Delete Worker
    */

    if(isset($_POST["delete_worker"]))
    {

        $delete_worker_id =
            $_POST["worker_id"];


        $delete_worker_query = "
            DELETE FROM workers
            WHERE worker_id = $1
        ";


        $delete_worker_result = pg_query_params(
            $conn,
            $delete_worker_query,
            array($delete_worker_id)
        );


        if($delete_worker_result)
        {
            $_SESSION["dashboard_success"] =
                "Worker deleted successfully.";
        }
        else
        {
            $_SESSION["dashboard_message"] =
                "Unable to delete worker.";
        }


        header("Location: dashboard.php");
        exit();
    }


    /*
        Delete Customer
    */

    if(isset($_POST["delete_customer"]))
    {

        $delete_customer_id =
            $_POST["customer_id"];


        $delete_customer_query = "
            DELETE FROM customer
            WHERE customer_id = $1
        ";


        $delete_customer_result = pg_query_params(
            $conn,
            $delete_customer_query,
            array($delete_customer_id)
        );


        if($delete_customer_result)
        {
            $_SESSION["dashboard_success"] =
                "Customer deleted successfully.";
        }
        else
        {
            $_SESSION["dashboard_message"] =
                "Unable to delete customer.";
        }


        header("Location: dashboard.php");
        exit();
    }


    /*
        Delete Supplier
    */

    if(isset($_POST["delete_supplier"]))
    {

        $delete_supplier_id =
            $_POST["supplier_id"];


        $delete_supplier_query = "
            DELETE FROM supplier
            WHERE supplier_id = $1
        ";


        $delete_supplier_result = pg_query_params(
            $conn,
            $delete_supplier_query,
            array($delete_supplier_id)
        );


        if($delete_supplier_result)
        {
            $_SESSION["dashboard_success"] =
                "Supplier deleted successfully.";
        }
        else
        {
            $_SESSION["dashboard_message"] =
                "Unable to delete supplier.";
        }


        header("Location: dashboard.php");
        exit();
    }


    /*
        Add Supplier
    */

    if(isset($_POST["add_supplier"]))
    {

        $supplier_name =
            trim($_POST["supplier_name"]);

        $supplier_phone =
            trim($_POST["supplier_phone"]);

        $supplier_address =
            trim($_POST["supplier_address"]);

        $supplier_description =
            trim($_POST["supplier_description"]);


        if($supplier_name == "" ||
           $supplier_phone == "" ||
           $supplier_address == "")
        {
            $_SESSION["dashboard_message"] =
                "Please fill all required supplier fields.";
        }
        else
        {

            $farm_query = "
                SELECT farm_id
                FROM farm
                LIMIT 1
            ";


            $farm_result =
                pg_query($conn, $farm_query);


            if(pg_num_rows($farm_result) == 0)
            {
                $_SESSION["dashboard_message"] =
                    "Farm information not found.";
            }
            else
            {

                $farm =
                    pg_fetch_assoc($farm_result);

                $farm_id =
                    $farm["farm_id"];


                $supplier_query = "
                    INSERT INTO supplier
                    (
                        name,
                        phone_no,
                        address,
                        description,
                        farm_id
                    )
                    VALUES
                    (
                        $1,
                        $2,
                        $3,
                        $4,
                        $5
                    )
                ";


                $supplier_result =
                    pg_query_params(
                        $conn,
                        $supplier_query,
                        array(
                            $supplier_name,
                            $supplier_phone,
                            $supplier_address,
                            $supplier_description,
                            $farm_id
                        )
                    );


                if($supplier_result)
                {
                    $_SESSION["dashboard_success"] =
                        "Supplier added successfully.";
                }
                else
                {
                    $_SESSION["dashboard_message"] =
                        "Unable to add supplier.";
                }
            }
        }


        header("Location: dashboard.php");
        exit();
    }
}


/*
========================================================
COUNTS
========================================================
*/


/* Product Count */

$product_count_query = "
    SELECT COUNT(*) AS total
    FROM product
";

$product_count_result =
    pg_query($conn, $product_count_query);

$product_count =
    pg_fetch_assoc($product_count_result);


/* User Count */

$user_count_query = "
    SELECT COUNT(*) AS total
    FROM users
";

$user_count_result =
    pg_query($conn, $user_count_query);

$user_count =
    pg_fetch_assoc($user_count_result);


/* Worker Count */

$worker_count_query = "
    SELECT COUNT(*) AS total
    FROM workers
";

$worker_count_result =
    pg_query($conn, $worker_count_query);

$worker_count =
    pg_fetch_assoc($worker_count_result);


/* Customer Count */

$customer_count_query = "
    SELECT COUNT(*) AS total
    FROM customer
";

$customer_count_result =
    pg_query($conn, $customer_count_query);

$customer_count =
    pg_fetch_assoc($customer_count_result);


/* Supplier Count */

$supplier_count_query = "
    SELECT COUNT(*) AS total
    FROM supplier
";

$supplier_count_result =
    pg_query($conn, $supplier_count_query);

$supplier_count =
    pg_fetch_assoc($supplier_count_result);


/* Order Count */

$order_count_query = "
    SELECT COUNT(*) AS total
    FROM orders
";

$order_count_result =
    pg_query($conn, $order_count_query);

$order_count =
    pg_fetch_assoc($order_count_result);


/*
========================================================
FARM INFORMATION
========================================================
*/

$farm_info_query = "
    SELECT
        farm_id,
        name,
        address,
        location,
        phone_no,
        email_id,
        establish_date,
        owner
    FROM farm
    LIMIT 1
";

$farm_info_result = pg_query(
    $conn,
    $farm_info_query
);

$farm_info = pg_fetch_assoc(
    $farm_info_result
);


/*
========================================================
INCOME
========================================================
*/

$income_query = "
    SELECT COALESCE(SUM(amount), 0) AS total
    FROM order_payments
";

$income_result =
    pg_query($conn, $income_query);

$income =
    pg_fetch_assoc($income_result);


/*
========================================================
EXPENSE
========================================================
*/

$expense_query = "
    SELECT COALESCE(SUM(compensation), 0) AS total
    FROM milk_collection
";

$expense_result =
    pg_query($conn, $expense_query);

$expense =
    pg_fetch_assoc($expense_result);


/*
========================================================
MILK COLLECTION
========================================================
*/

$milk_query = "
    SELECT
        mc.collection_id,
        mc.date,
        mc.time,
        mc.milk_quality,
        mc.milk_quantity,
        mc.compensation,
        mc.description,
        s.name AS supplier_name
    FROM milk_collection mc
    JOIN supplier s
        ON mc.supplier_id = s.supplier_id
    ORDER BY mc.collection_id DESC
";

$milk_result =
    pg_query($conn, $milk_query);


/*
========================================================
SUPPLIERS FOR MILK FORM
========================================================
*/

$supplier_query = "
    SELECT
        supplier_id,
        name
    FROM supplier
    ORDER BY supplier_id
";

$supplier_result =
    pg_query($conn, $supplier_query);


/*
========================================================
PRODUCT LIST
========================================================
*/

$edit_product = null;

if(isset($_GET["edit_product_id"]))
{
    $edit_product_id = $_GET["edit_product_id"];

    $edit_product_query = "
        SELECT
            product_id,
            name,
            image,
            price,
            quantity,
            stock,
            making_date,
            expiry_date,
            ingredients,
            nutrition_value,
            description
        FROM product
        WHERE product_id = $1
    ";

    $edit_product_result = pg_query_params(
        $conn,
        $edit_product_query,
        array($edit_product_id)
    );

    if(pg_num_rows($edit_product_result) > 0)
    {
        $edit_product = pg_fetch_assoc($edit_product_result);
    }
}

$product_query = "
    SELECT
        product_id,
        name,
        price,
        quantity,
        stock,
        making_date,
        expiry_date
    FROM product
    ORDER BY product_id DESC
";

$product_result =
    pg_query($conn, $product_query);


/*
========================================================
ORDERS
========================================================
*/

$order_query = "
    SELECT
        o.order_id,
        o.date,
        o.time,
        o.status,
        u.name AS customer_name,
        u.address AS delivery_address
    FROM orders o
    JOIN customer c
        ON o.customer_id = c.customer_id
    JOIN users u
        ON c.user_id = u.user_id
    ORDER BY o.order_id DESC
";

$order_result =
    pg_query($conn, $order_query);


/*
========================================================
FEEDBACK
========================================================
*/

$feedback_query = "
    SELECT
        f.feedback_id,
        f.message,
        f.rating,
        f.date,
        f.time,
        u.name AS customer_name
    FROM feedback f
    JOIN customer c
        ON f.customer_id = c.customer_id
    JOIN users u
        ON c.user_id = u.user_id
    ORDER BY f.feedback_id DESC
";

$feedback_result =
    pg_query($conn, $feedback_query);


/*
========================================================
ADMIN TABLES
========================================================
*/


/* Users */

$users_query = "
    SELECT
        user_id,
        name,
        email_id,
        phone_no,
        gender,
        role
    FROM users
    ORDER BY user_id DESC
";

$users_result =
    pg_query($conn, $users_query);


/* Workers */

$workers_query = "
    SELECT
        w.worker_id,
        w.description,
        u.name,
        u.email_id,
        u.phone_no
    FROM workers w
    JOIN users u
        ON w.user_id = u.user_id
    ORDER BY w.worker_id DESC
";

$workers_result =
    pg_query($conn, $workers_query);


/* Customers */

$customers_query = "
    SELECT
        c.customer_id,
        u.name,
        u.email_id,
        u.phone_no
    FROM customer c
    JOIN users u
        ON c.user_id = u.user_id
    ORDER BY c.customer_id DESC
";

$customers_result =
    pg_query($conn, $customers_query);


/* Suppliers */

$suppliers_query = "
    SELECT
        supplier_id,
        name,
        phone_no,
        address,
        description
    FROM supplier
    ORDER BY supplier_id DESC
";

$suppliers_result =
    pg_query($conn, $suppliers_query);

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>
    Dashboard - Onkareshwar Dairy Farm
</title>

<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<link rel="stylesheet"
      href="dashboard.css">

</head>


<body>


<header>

    <h1>
        Onkareshwar Dairy Farm
    </h1>

</header>


<nav class="navbar">

    <a href="home.html">

        <i class="fa-solid fa-house"></i>

        Home

    </a>


    <div class="nav-right">

        <span>

            <i class="fa-solid fa-user"></i>

            <?php

            echo htmlspecialchars(
                $_SESSION["name"]
            );

            ?>

        </span>

    </div>

</nav>


<main>


    <h2 class="dashboard-title">

        <?php

        if($role == "admin")
        {
            echo "Admin Dashboard";
        }
        else
        {
            echo "Worker Dashboard";
        }

        ?>

    </h2>


    <?php

    if($success_message != "")
    {

    ?>

        <div class="success-message">

            <i class="fa-solid fa-circle-check"></i>

            <?php

            echo htmlspecialchars(
                $success_message
            );

            ?>

        </div>

    <?php

    }


    if($message != "")
    {

    ?>

        <div class="message">

            <?php

            echo htmlspecialchars(
                $message
            );

            ?>

        </div>

    <?php

    }


    ?>


    <!-- ================================================= -->
    <!-- SUMMARY CARDS -->
    <!-- ================================================= -->


    <section class="summary-section">


        <div class="summary-card">

            <i class="fa-solid fa-box"></i>

            <h3>
                Products
            </h3>

            <p>

                <?php

                echo $product_count["total"];

                ?>

            </p>

        </div>


        <div class="summary-card">

            <i class="fa-solid fa-users"></i>

            <h3>
                Users
            </h3>

            <p>

                <?php

                echo $user_count["total"];

                ?>

            </p>

        </div>


        <div class="summary-card">

            <i class="fa-solid fa-user-tie"></i>

            <h3>
                Workers
            </h3>

            <p>

                <?php

                echo $worker_count["total"];

                ?>

            </p>

        </div>


        <div class="summary-card">

            <i class="fa-solid fa-user"></i>

            <h3>
                Customers
            </h3>

            <p>

                <?php

                echo $customer_count["total"];

                ?>

            </p>

        </div>


        <div class="summary-card">

            <i class="fa-solid fa-truck"></i>

            <h3>
                Suppliers
            </h3>

            <p>

                <?php

                echo $supplier_count["total"];

                ?>

            </p>

        </div>


        <div class="summary-card">

            <i class="fa-solid fa-cart-shopping"></i>

            <h3>
                Orders
            </h3>

            <p>

                <?php

                echo $order_count["total"];

                ?>

            </p>

        </div>


    </section>



    <!-- ================================================= -->
    <!-- FARM MANAGEMENT -->
    <!-- ================================================= -->


    <?php

    if($role == "admin")
    {

    ?>

    <section class="dashboard-section">

        <div class="section-heading">

            <h2>

                <i class="fa-solid fa-house"></i>

                Farm Management

            </h2>


            <button
                type="button"
                onclick="openFarmPopup()">

                <i class="fa-solid fa-pen"></i>

                Update Farm Info

            </button>

        </div>


        <div class="farm-information">


            <div class="farm-row">

                <strong>
                    Farm Name
                </strong>

                <span>

                    <?php

                    echo htmlspecialchars(
                        $farm_info["name"]
                    );

                    ?>

                </span>

            </div>


            <div class="farm-row">

                <strong>
                    Address
                </strong>

                <span>

                    <?php

                    echo htmlspecialchars(
                        $farm_info["address"]
                    );

                    ?>

                </span>

            </div>


            <div class="farm-row">

                <strong>
                    Location
                </strong>

                <span>

                    <?php

                    echo htmlspecialchars(
                        $farm_info["location"]
                    );

                    ?>

                </span>

            </div>


            <div class="farm-row">

                <strong>
                    Phone Number
                </strong>

                <span>

                    <?php

                    echo htmlspecialchars(
                        $farm_info["phone_no"]
                    );

                    ?>

                </span>

            </div>


            <div class="farm-row">

                <strong>
                    Email ID
                </strong>

                <span>

                    <?php

                    echo htmlspecialchars(
                        $farm_info["email_id"]
                    );

                    ?>

                </span>

            </div>


            <div class="farm-row">

                <strong>
                    Establish Date
                </strong>

                <span>

                    <?php

                    echo htmlspecialchars(
                        $farm_info["establish_date"]
                    );

                    ?>

                </span>

            </div>


            <div class="farm-row">

                <strong>
                    Owner
                </strong>

                <span>

                    <?php

                    echo htmlspecialchars(
                        $farm_info["owner"]
                    );

                    ?>

                </span>

            </div>


        </div>

    </section>

    <?php

    }

    ?>



    <!-- ================================================= -->
    <!-- ADMIN FINANCE -->
    <!-- ================================================= -->


    <?php

    if($role == "admin")
    {

    ?>


    <section class="dashboard-section">

        <h2>

            Finance Summary

        </h2>


        <div class="finance-container">


            <div class="finance-card income">

                <i class="fa-solid fa-arrow-up"></i>

                <h3>
                    Total Income
                </h3>

                <p>

                    ₹<?php

                    echo $income["total"];

                    ?>

                </p>

                <small>
                    From customer payments
                </small>

            </div>


            <div class="finance-card expense">

                <i class="fa-solid fa-arrow-down"></i>

                <h3>
                    Total Expenses
                </h3>

                <p>

                    ₹<?php

                    echo $expense["total"];

                    ?>

                </p>

                <small>
                    Supplier milk compensation
                </small>

            </div>


            <div class="finance-card">

                <i class="fa-solid fa-chart-line"></i>

                <h3>
                    Current Balance
                </h3>

                <p>

                    ₹<?php

                    echo
                        $income["total"] -
                        $expense["total"];

                    ?>

                </p>

                <small>
                    Income - Expenses
                </small>

            </div>


        </div>

    </section>


    <?php

    }


    ?>



    <!-- ================================================= -->
    <!-- PRODUCT MANAGEMENT -->
    <!-- ================================================= -->


    <section class="dashboard-section">

        <div class="section-heading">

            <h2>

                Product Management

            </h2>


            <button
                type="button"
                onclick="openProductPopup()">

                <i class="fa-solid fa-plus"></i>

                Add Product

            </button>

        </div>


        <div class="table-container">

            <table>

                <thead>

                    <tr>

                        <th>
                            ID
                        </th>

                        <th>
                            Name
                        </th>

                        <th>
                            Price
                        </th>

                        <th>
                            Pack Size
                        </th>

                        <th>
                            Stock
                        </th>

                        <th>
                            Making Date
                        </th>

                        <th>
                            Expiry Date
                        </th>

                        <th>
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody>

                <?php

                if(pg_num_rows($product_result) > 0)
                {

                    while($product =
                        pg_fetch_assoc(
                            $product_result
                        ))
                    {

                ?>

                    <tr>

                        <td>

                            <?php

                            echo $product["product_id"];

                            ?>

                        </td>

                        <td>

                            <?php

                            echo htmlspecialchars(
                                $product["name"]
                            );

                            ?>

                        </td>

                        <td>

                            ₹<?php

                            echo $product["price"];

                            ?>

                        </td>

                        <td>

                            <?php

                            echo htmlspecialchars(
                                $product["quantity"]
                            );

                            ?>

                        </td>

                        <td>

                            <?php

                            echo $product["stock"];

                            ?>

                        </td>

                        <td>

                            <?php

                            echo $product["making_date"];

                            ?>

                        </td>

                        <td>

                            <?php

                            echo $product["expiry_date"];

                            ?>

                        </td>

                        <td>

                            <form
                                method="post">

                                <input type="hidden"
                                    name="product_id"
                                    value="<?php echo $product["product_id"]; ?>">

                                <button
                                    type="button"
                                    class="delete-button"
                                    onclick="window.location.href='dashboard.php?edit_product_id=<?php echo $product["product_id"]; ?>';">

                                    <i class="fa-solid fa-pen"></i>

                                    Update

                                </button>

                                <button
                                    type="submit"
                                    name="delete_product"
                                    class="delete-button"
                                    onclick="return confirm('Are you sure you want to delete this product?');">

                                    <i class="fa-solid fa-trash"></i>

                                    Delete

                                </button>

                            </form>

                        </td>

                    </tr>

                <?php

                    }

                }
                else
                {

                ?>

                    <tr>

                        <td colspan="8">

                            No products available.

                        </td>

                    </tr>

                <?php

                }

                ?>

                </tbody>

            </table>

        </div>

    </section>



    <!-- ================================================= -->
    <!-- MILK COLLECTION -->
    <!-- ================================================= -->


    <section class="dashboard-section">

        <div class="section-heading">

            <h2>

                Milk Collection

            </h2>


            <button
                type="button"
                onclick="openMilkPopup()">

                <i class="fa-solid fa-plus"></i>

                Add Collection

            </button>

        </div>


        <div class="table-container">

            <table>

                <thead>

                    <tr>

                        <th>
                            ID
                        </th>

                        <th>
                            Date
                        </th>

                        <th>
                            Time
                        </th>

                        <th>
                            Supplier
                        </th>

                        <th>
                            Quality
                        </th>

                        <th>
                            Quantity
                        </th>

                        <th>
                            Compensation
                        </th>

                        <th>
                            Description
                        </th>

                        <th>
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody>

                <?php

                if(pg_num_rows($milk_result) > 0)
                {

                    while($milk =
                        pg_fetch_assoc(
                            $milk_result
                        ))
                    {

                ?>

                    <tr>

                        <td>

                            <?php

                            echo $milk["collection_id"];

                            ?>

                        </td>

                        <td>

                            <?php

                            echo $milk["date"];

                            ?>

                        </td>

                        <td>

                            <?php

                            echo $milk["time"];

                            ?>

                        </td>

                        <td>

                            <?php

                            echo htmlspecialchars(
                                $milk["supplier_name"]
                            );

                            ?>

                        </td>

                        <td>

                            <?php

                            echo htmlspecialchars(
                                $milk["milk_quality"]
                            );

                            ?>

                        </td>

                        <td>

                            <?php

                            echo $milk["milk_quantity"];

                            ?>

                        </td>

                        <td>

                            ₹<?php

                            echo $milk["compensation"];

                            ?>

                        </td>

                        <td>

                            <?php

                            echo htmlspecialchars(
                                $milk["description"]
                            );

                            ?>

                        </td>

                        <td>

                            <form
                                method="post"
                                onsubmit="return confirm('Delete this milk collection record?');">

                                <input
                                    type="hidden"
                                    name="collection_id"
                                    value="<?php

                                    echo $milk["collection_id"];

                                    ?>"
                                >
                            
                                <button
                                    type="submit"
                                    name="delete_milk"
                                    class="delete-button">

                                    <i class="fa-solid fa-trash"></i>

                                    Delete

                                </button>

                            </form>

                        </td>

                    </tr>

                <?php

                    }

                }
                else
                {

                ?>

                    <tr>

                        <td colspan="9">

                            No milk collection records available.

                        </td>

                    </tr>

                <?php

                }

                ?>

                </tbody>

            </table>

        </div>

    </section>



    <!-- ================================================= -->
    <!-- ORDERS -->
    <!-- ================================================= -->


    <section class="dashboard-section">

        <div class="section-heading">

            <h2>

                Orders

            </h2>

        </div>


        <div class="table-container">

            <table>

                <thead>

                    <tr>

                        <th>
                            Order ID
                        </th>

                        <th>
                            Customer
                        </th>

                        <th>
                            Date
                        </th>

                        <th>
                            Time
                        </th>

                        <th>
                            Delivery Address
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody>

                <?php

                if(pg_num_rows($order_result) > 0)
                {

                    while($order =
                        pg_fetch_assoc(
                            $order_result
                        ))
                    {

                ?>

                    <tr>

                        <td>

                            <?php

                            echo $order["order_id"];

                            ?>

                        </td>

                        <td>

                            <?php

                            echo htmlspecialchars(
                                $order["customer_name"]
                            );

                            ?>

                        </td>

                        <td>

                            <?php

                            echo $order["date"];

                            ?>

                        </td>

                        <td>

                            <?php

                            echo $order["time"];

                            ?>

                        </td>

                        <td>

                            <?php

                            echo htmlspecialchars(
                                $order["delivery_address"]
                            );

                            ?>

                        </td>

                        <td>

                            <span class="status">

                                <?php

                                echo htmlspecialchars(
                                    $order["status"]
                                );

                                ?>

                            </span>

                        </td>

                        <td>

                            <?php

                            if(
                                $order["status"] == "Pending"
                            )
                            {

                            ?>

                            <form
                                method="post">

                                <input
                                    type="hidden"
                                    name="order_id"
                                    value="<?php

                                    echo $order["order_id"];

                                    ?>"
                                >


                                <input
                                    type="hidden"
                                    name="status"
                                    value="Completed"
                                >


                                <button
                                    type="submit"
                                    name="update_order_status"
                                    class="complete-button">

                                    <i class="fa-solid fa-check"></i>

                                    Complete

                                </button>

                            </form>

                            <?php

                            }
                            else
                            {

                            ?>

                                <span class="completed-text">

                                    Completed

                                </span>

                            <?php

                            }

                            ?>

                        </td>

                    </tr>

                <?php

                    }

                }
                else
                {

                ?>

                    <tr>

                        <td colspan="7">

                            No orders available.

                        </td>

                    </tr>

                <?php

                }

                ?>

                </tbody>

            </table>

        </div>

    </section>



    <!-- ================================================= -->
    <!-- CUSTOMER FEEDBACK -->
    <!-- ================================================= -->


    <section class="dashboard-section">

        <h2>

            Customer Feedback

        </h2>


        <div class="feedback-list">

        <?php

        if(pg_num_rows($feedback_result) > 0)
        {

            while($feedback =
                pg_fetch_assoc(
                    $feedback_result
                ))
            {

        ?>

            <div class="feedback-card">

                <h3>

                    <?php

                    echo htmlspecialchars(
                        $feedback["customer_name"]
                    );

                    ?>

                </h3>


                <p class="rating">

                    Rating:

                    <?php

                    for(
                        $i = 1;
                        $i <= $feedback["rating"];
                        $i++
                    )
                    {
                        echo "★";
                    }

                    ?>

                </p>


                <p>

                    <?php

                    echo htmlspecialchars(
                        $feedback["message"]
                    );

                    ?>

                </p>


                <small>

                    <?php

                    echo $feedback["date"];

                    ?>

                    &nbsp;

                    <?php

                    echo $feedback["time"];

                    ?>

                </small>

            </div>

        <?php

            }

        }
        else
        {

        ?>

            <p>

                No customer feedback available.

            </p>

        <?php

        }

        ?>

        </div>

    </section>



    <?php

    /*
    ========================================================
    ADMIN MANAGEMENT
    ========================================================
    */

    if($role == "admin")
    {

    ?>


    <!-- ================================================= -->
    <!-- USERS -->
    <!-- ================================================= -->


    <section class="dashboard-section">

        <h2>

            Users Management

        </h2>


        <div class="table-container">

            <table>

                <thead>

                    <tr>

                        <th>
                            ID
                        </th>

                        <th>
                            Name
                        </th>

                        <th>
                            Email
                        </th>

                        <th>
                            Phone
                        </th>

                        <th>
                            Gender
                        </th>

                        <th>
                            Role
                        </th>

                        <th>
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody>

                <?php

                while($user =
                    pg_fetch_assoc(
                        $users_result
                    ))
                {

                ?>

                    <tr>

                        <td>

                            <?php

                            echo $user["user_id"];

                            ?>

                        </td>

                        <td>

                            <?php

                            echo htmlspecialchars(
                                $user["name"]
                            );

                            ?>

                        </td>

                        <td>

                            <?php

                            echo htmlspecialchars(
                                $user["email_id"]
                            );

                            ?>

                        </td>

                        <td>

                            <?php

                            echo htmlspecialchars(
                                $user["phone_no"]
                            );

                            ?>

                        </td>

                        <td>

                            <?php

                            echo htmlspecialchars(
                                $user["gender"]
                            );

                            ?>

                        </td>

                        <td>

                            <?php

                            echo htmlspecialchars(
                                $user["role"]
                            );

                            ?>

                        </td>

                        <td>

                            <form
                                method="post"
                                onsubmit="return confirm('Delete this user?');">

                                <input
                                    type="hidden"
                                    name="user_id"
                                    value="<?php

                                    echo $user["user_id"];

                                    ?>"
                                >


                                <button
                                    type="submit"
                                    name="delete_user"
                                    class="delete-button">

                                    Delete

                                </button>

                            </form>

                        </td>

                    </tr>

                <?php

                }

                ?>

                </tbody>

            </table>

        </div>

    </section>



    <!-- ================================================= -->
    <!-- WORKERS -->
    <!-- ================================================= -->


    <section class="dashboard-section">

        <h2>

            Workers Management

        </h2>


        <div class="table-container">

            <table>

                <thead>

                    <tr>

                        <th>
                            ID
                        </th>

                        <th>
                            Name
                        </th>

                        <th>
                            Email
                        </th>

                        <th>
                            Phone
                        </th>

                        <th>
                            Description
                        </th>

                        <th>
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody>

                <?php

                while($worker =
                    pg_fetch_assoc(
                        $workers_result
                    ))
                {

                ?>

                    <tr>

                        <td>

                            <?php

                            echo $worker["worker_id"];

                            ?>

                        </td>

                        <td>

                            <?php

                            echo htmlspecialchars(
                                $worker["name"]
                            );

                            ?>

                        </td>

                        <td>

                            <?php

                            echo htmlspecialchars(
                                $worker["email_id"]
                            );

                            ?>

                        </td>

                        <td>

                            <?php

                            echo htmlspecialchars(
                                $worker["phone_no"]
                            );

                            ?>

                        </td>

                        <td>

                            <?php

                            echo htmlspecialchars(
                                $worker["description"]
                            );

                            ?>

                        </td>

                        <td>

                            <form
                                method="post"
                                onsubmit="return confirm('Delete this worker?');">

                                <input
                                    type="hidden"
                                    name="worker_id"
                                    value="<?php

                                    echo $worker["worker_id"];

                                    ?>"
                                >


                                <button
                                    type="submit"
                                    name="delete_worker"
                                    class="delete-button">

                                    Delete

                                </button>

                            </form>

                        </td>

                    </tr>

                <?php

                }

                ?>

                </tbody>

            </table>

        </div>

    </section>



    <!-- ================================================= -->
    <!-- CUSTOMERS -->
    <!-- ================================================= -->


    <section class="dashboard-section">

        <h2>

            Customers Management

        </h2>


        <div class="table-container">

            <table>

                <thead>

                    <tr>

                        <th>
                            ID
                        </th>

                        <th>
                            Name
                        </th>

                        <th>
                            Email
                        </th>

                        <th>
                            Phone
                        </th>

                        <th>
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody>

                <?php

                while($customer =
                    pg_fetch_assoc(
                        $customers_result
                    ))
                {

                ?>

                    <tr>

                        <td>

                            <?php

                            echo $customer["customer_id"];

                            ?>

                        </td>

                        <td>

                            <?php

                            echo htmlspecialchars(
                                $customer["name"]
                            );

                            ?>

                        </td>

                        <td>

                            <?php

                            echo htmlspecialchars(
                                $customer["email_id"]
                            );

                            ?>

                        </td>

                        <td>

                            <?php

                            echo htmlspecialchars(
                                $customer["phone_no"]
                            );

                            ?>

                        </td>

                        <td>

                            <form
                                method="post"
                                onsubmit="return confirm('Delete this customer?');">

                                <input
                                    type="hidden"
                                    name="customer_id"
                                    value="<?php

                                    echo $customer["customer_id"];

                                    ?>"
                                >


                                <button
                                    type="submit"
                                    name="delete_customer"
                                    class="delete-button">

                                    Delete

                                </button>

                            </form>

                        </td>

                    </tr>

                <?php

                }

                ?>

                </tbody>

            </table>

        </div>

    </section>



    <!-- ================================================= -->
    <!-- SUPPLIERS -->
    <!-- ================================================= -->


    <section class="dashboard-section">

        <div class="section-heading">

            <h2>

                Suppliers Management

            </h2>


            <button
                type="button"
                onclick="openSupplierPopup()">

                <i class="fa-solid fa-plus"></i>

                Add Supplier

            </button>

        </div>


        <div class="table-container">

            <table>

                <thead>

                    <tr>

                        <th>
                            ID
                        </th>

                        <th>
                            Name
                        </th>

                        <th>
                            Phone
                        </th>

                        <th>
                            Address
                        </th>

                        <th>
                            Description
                        </th>

                        <th>
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody>

                <?php

                while($supplier =
                    pg_fetch_assoc(
                        $suppliers_result
                    ))
                {

                ?>

                    <tr>

                        <td>

                            <?php

                            echo $supplier["supplier_id"];

                            ?>

                        </td>

                        <td>

                            <?php

                            echo htmlspecialchars(
                                $supplier["name"]
                            );

                            ?>

                        </td>

                        <td>

                            <?php

                            echo htmlspecialchars(
                                $supplier["phone_no"]
                            );

                            ?>

                        </td>

                        <td>

                            <?php

                            echo htmlspecialchars(
                                $supplier["address"]
                            );

                            ?>

                        </td>

                        <td>

                            <?php

                            echo htmlspecialchars(
                                $supplier["description"]
                            );

                            ?>

                        </td>

                        <td>

                            <form
                                method="post"
                                onsubmit="return confirm('Delete this supplier?');">

                                <input
                                    type="hidden"
                                    name="supplier_id"
                                    value="<?php

                                    echo $supplier["supplier_id"];

                                    ?>"
                                >


                                <button
                                    type="submit"
                                    name="delete_supplier"
                                    class="delete-button">

                                    Delete

                                </button>

                            </form>

                        </td>

                    </tr>

                <?php

                }

                ?>

                </tbody>

            </table>

        </div>

    </section>


    <?php

    }

    ?>


</main>



<!-- ================================================= -->
<!-- ADD PRODUCT POPUP -->
<!-- ================================================= -->

<div
    class="popup"
    id="productPopup"
>

    <div class="popup-content large-popup">


        <span
            class="close"
            onclick="closeProductPopup()">

            &times;

        </span>


        <h2>

            Add Product

        </h2>


        <form
            method="post"
            action="dashboard.php"
        >


            <label>
                Product Name
            </label>

            <input
                type="text"
                name="name"
                required
            >


            <label>
                Image Path
            </label>

            <input
                type="text"
                name="image"
                placeholder="images/milk.jpg"
            >


            <label>
                Price
            </label>

            <input
                type="number"
                name="price"
                step="0.01"
                min="0"
                required
            >


            <label>
                Pack Size
            </label>

            <input
                type="text"
                name="quantity"
                placeholder="1 litre / 250 gm"
                required
            >


            <label>
                Stock
            </label>

            <input
                type="number"
                name="stock"
                min="0"
                required
            >


            <label>
                Making Date
            </label>

            <input
                type="date"
                name="making_date"
                required
            >


            <label>
                Expiry Date
            </label>

            <input
                type="date"
                name="expiry_date"
                required
            >


            <label>
                Ingredients
            </label>

            <textarea
                name="ingredients"
                rows="3"
            ></textarea>


            <label>
                Nutrition Value
            </label>

            <textarea
                name="nutrition_value"
                rows="3"
            ></textarea>


            <label>
                Description
            </label>

            <textarea
                name="description"
                rows="3"
            ></textarea>


            <button
                type="submit"
                name="add_product">

                Add Product

            </button>


            <button
                type="button"
                onclick="closeProductPopup()">

                Cancel

            </button>


        </form>

    </div>

</div>



<!-- ================================================= -->
<!-- ADD MILK POPUP -->
<!-- ================================================= -->

<div
    class="popup"
    id="milkPopup"
>

    <div class="popup-content large-popup">


        <span
            class="close"
            onclick="closeMilkPopup()">

            &times;

        </span>


        <h2>

            Add Milk Collection

        </h2>


        <form
            method="post"
            action="dashboard.php"
        >


            <label>
                Date
            </label>

            <input
                type="date"
                name="milk_date"
                required
            >


            <label>
                Time
            </label>

            <input
                type="time"
                name="milk_time"
                required
            >


            <label>
                Supplier
            </label>

            <select
                name="supplier_id"
                required
            >

                <option value="">

                    Select Supplier

                </option>


                <?php

                if(pg_num_rows($supplier_result) > 0)
                {

                    while($supplier =
                        pg_fetch_assoc(
                            $supplier_result
                        ))
                    {

                ?>

                    <option
                        value="<?php

                        echo $supplier["supplier_id"];

                        ?>"
                    >

                        <?php

                        echo htmlspecialchars(
                            $supplier["name"]
                        );

                        ?>

                    </option>

                <?php

                    }

                }

                ?>

            </select>


            <label>
                Milk Quality
            </label>

            <input
                type="text"
                name="milk_quality"
                placeholder="Good / Excellent"
                required
            >


            <label>
                Milk Quantity
            </label>

            <input
                type="number"
                name="milk_quantity"
                step="0.01"
                min="0"
                required
            >


            <label>
                Compensation
            </label>

            <input
                type="number"
                name="compensation"
                step="0.01"
                min="0"
                required
            >


            <label>
                Description
            </label>

            <textarea
                name="milk_description"
                rows="3"
            ></textarea>


            <button
                type="submit"
                name="add_milk">

                Add Collection

            </button>


            <button
                type="button"
                onclick="closeMilkPopup()">

                Cancel

            </button>


        </form>

    </div>

</div>



<!-- ================================================= -->
<!-- ADD SUPPLIER POPUP -->
<!-- ================================================= -->

<?php

if($role == "admin")
{

?>

<div
    class="popup"
    id="supplierPopup"
>

    <div class="popup-content">


        <span
            class="close"
            onclick="closeSupplierPopup()">

            &times;

        </span>


        <h2>

            Add Supplier

        </h2>


        <form
            method="post"
            action="dashboard.php"
        >


            <label>
                Supplier Name
            </label>

            <input
                type="text"
                name="supplier_name"
                required
            >


            <label>
                Phone Number
            </label>

            <input
                type="text"
                name="supplier_phone"
                required
            >


            <label>
                Address
            </label>

            <textarea
                name="supplier_address"
                rows="3"
                required
            ></textarea>


            <label>
                Description
            </label>

            <textarea
                name="supplier_description"
                rows="3"
            ></textarea>


            <button
                type="submit"
                name="add_supplier">

                Add Supplier

            </button>


            <button
                type="button"
                onclick="closeSupplierPopup()">

                Cancel

            </button>


        </form>

    </div>

</div>

<?php

}

?>



<!-- ================================================= -->
<!-- UPDATE FARM POPUP -->
<!-- ================================================= -->

<?php

if($role == "admin")
{

?>

<div
    class="popup"
    id="farmPopup"
>

    <div class="popup-content">


        <span
            class="close"
            onclick="closeFarmPopup()">

            &times;

        </span>


        <h2>

            Update Farm Information

        </h2>


        <form
            method="post"
            action="dashboard.php"
        >


            <input
                type="hidden"
                name="farm_id"
                value="<?php

                echo $farm_info["farm_id"];

                ?>"
            >


            <label>
                Farm Name
            </label>

            <input
                type="text"
                name="farm_name"
                value="<?php

                echo htmlspecialchars(
                    $farm_info["name"]
                );

                ?>"
                required
            >


            <label>
                Address
            </label>

            <input
                type="text"
                name="farm_address"
                value="<?php

                echo htmlspecialchars(
                    $farm_info["address"]
                );

                ?>"
                required
            >


            <label>
                Location
            </label>

            <input
                type="text"
                name="farm_location"
                value="<?php

                echo htmlspecialchars(
                    $farm_info["location"]
                );

                ?>"
                required
            >


            <label>
                Phone Number
            </label>

            <input
                type="tel"
                name="farm_phone"
                value="<?php

                echo htmlspecialchars(
                    $farm_info["phone_no"]
                );

                ?>"
                required
            >


            <label>
                Email ID
            </label>

            <input
                type="email"
                name="farm_email"
                value="<?php

                echo htmlspecialchars(
                    $farm_info["email_id"]
                );

                ?>"
                required
            >


            <label>
                Establish Date
            </label>

            <input
                type="date"
                name="farm_date"
                value="<?php

                echo htmlspecialchars(
                    $farm_info["establish_date"]
                );

                ?>"
                required
            >


            <label>
                Owner
            </label>

            <input
                type="text"
                name="farm_owner"
                value="<?php

                echo htmlspecialchars(
                    $farm_info["owner"]
                );

                ?>"
                required
            >


            <button
                type="submit"
                name="update_farm">

                <i class="fa-solid fa-pen"></i>

                Update Farm Info

            </button>


            <button
                type="button"
                onclick="closeFarmPopup()">

                Cancel

            </button>


        </form>

    </div>

</div>

<?php

}

?>


<!-- ================================================= -->
<!-- UPDATE PRODUCT POPUP -->
<!-- ================================================= -->

<?php if($edit_product != null) { ?>

<div
    class="popup"
    id="updateProductPopup"
    style="display: flex;">

    <div class="popup-content large-popup">

        <span
            class="close"
            onclick="closeUpdateProductPopup()">

            &times;

        </span>

        <h2>

            Update Product

        </h2>

        <form
            method="post"
            action="dashboard.php">

            <input
                type="hidden"
                name="product_id"
                value="<?php echo htmlspecialchars($edit_product["product_id"]); ?>">

            <label>
                Product Name
            </label>

            <input
                type="text"
                name="name"
                value="<?php echo htmlspecialchars($edit_product["name"]); ?>"
                required>

            <label>
                Image Path
            </label>

            <input
                type="text"
                name="image"
                value="<?php echo htmlspecialchars($edit_product["image"]); ?>"
                placeholder="images/milk.jpg">

            <label>
                Price
            </label>

            <input
                type="number"
                name="price"
                step="0.01"
                min="0"
                value="<?php echo htmlspecialchars($edit_product["price"]); ?>"
                required>

            <label>
                Pack Size
            </label>

            <input
                type="text"
                name="quantity"
                value="<?php echo htmlspecialchars($edit_product["quantity"]); ?>"
                placeholder="1 litre / 250 gm"
                required>

            <label>
                Stock
            </label>

            <input
                type="number"
                name="stock"
                min="0"
                value="<?php echo htmlspecialchars($edit_product["stock"]); ?>"
                required>

            <label>
                Making Date
            </label>

            <input
                type="date"
                name="making_date"
                value="<?php echo htmlspecialchars($edit_product["making_date"]); ?>"
                required>

            <label>
                Expiry Date
            </label>

            <input
                type="date"
                name="expiry_date"
                value="<?php echo htmlspecialchars($edit_product["expiry_date"]); ?>"
                required>

            <label>
                Ingredients
            </label>

            <textarea
                name="ingredients"
                rows="3"><?php echo htmlspecialchars($edit_product["ingredients"]); ?></textarea>

            <label>
                Nutrition Value
            </label>

            <textarea
                name="nutrition_value"
                rows="3"><?php echo htmlspecialchars($edit_product["nutrition_value"]); ?></textarea>

            <label>
                Description
            </label>

            <textarea
                name="description"
                rows="3"><?php echo htmlspecialchars($edit_product["description"]); ?></textarea>

            <button
                type="submit"
                name="update_product">

                Update Product

            </button>

            <button
                type="button"
                onclick="closeUpdateProductPopup()">

                Cancel

            </button>

        </form>

    </div>

</div>

<?php } ?>


<script>


/*
    Product Popup
*/

function openProductPopup()
{
    document.getElementById(
        "productPopup"
    ).style.display = "flex";
}


function closeProductPopup()
{
    document.getElementById(
        "productPopup"
    ).style.display = "none";
}


/*
    Update Product Popup
*/

function openUpdateProductPopup()
{
    document.getElementById(
        "updateProductPopup"
    ).style.display = "flex";
}


function closeUpdateProductPopup()
{
    document.getElementById(
        "updateProductPopup"
    ).style.display = "none";
}


/*
    Milk Popup
*/

function openMilkPopup()
{
    document.getElementById(
        "milkPopup"
    ).style.display = "flex";
}


function closeMilkPopup()
{
    document.getElementById(
        "milkPopup"
    ).style.display = "none";
}


/*
    Supplier Popup
*/

function openSupplierPopup()
{
    document.getElementById(
        "supplierPopup"
    ).style.display = "flex";
}


function closeSupplierPopup()
{
    document.getElementById(
        "supplierPopup"
    ).style.display = "none";
}


/*
    Farm Popup
*/

function openFarmPopup()
{
    document.getElementById(
        "farmPopup"
    ).style.display = "flex";
}


function closeFarmPopup()
{
    document.getElementById(
        "farmPopup"
    ).style.display = "none";
}


/*
    Close Popup
    When Clicking Outside
*/

window.onclick = function(event)
{
    if(event.target.classList.contains("popup"))
    {
        event.target.style.display = "none";
    }
}

</script>


<footer>

    <p>

        &copy; rk.solutions.pvt.ltd

    </p>

</footer>


</body>

</html>

<?php

pg_close($conn);

?>