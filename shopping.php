<?php
session_start();
$message = "";
$success_message = "";
/* Check Login */
if(!isset($_SESSION["user_id"]))
{
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION["user_id"];
$role = $_SESSION["role"];
include("db.php");
/* Get Success Message */
if(isset($_SESSION["success_message"]))
{
    $success_message = $_SESSION["success_message"];
    unset($_SESSION["success_message"]);
}
/* Get Customer ID */
$customer_id = 0;
$customer_query = "
    SELECT customer_id
    FROM customer
    WHERE user_id = $1
";
$customer_result = pg_query_params(
    $conn,
    $customer_query,
    array($user_id)
);
if(pg_num_rows($customer_result) > 0)
{
    $customer = pg_fetch_assoc($customer_result);
    $customer_id = $customer["customer_id"];
}
else
{
    if($role == "customer")
    {
        $message = "Customer account not found.";
    }
}
/*
    Customer Operations
*/
if($role == "customer" && $customer_id > 0)
{
    /*
        Add To Cart
    */
    if(isset($_POST["add_to_cart"]))
    {
        $product_id = $_POST["product_id"];
        $quantity = $_POST["quantity"];
        if($quantity <= 0)
        {
            $message = "Please enter valid quantity.";
        }
        else
        {
            $product_query = "
                SELECT product_id, price, quantity, stock
                FROM product
                WHERE product_id = $1
            ";
            $product_result = pg_query_params(
                $conn,
                $product_query,
                array($product_id)
            );
            if(pg_num_rows($product_result) == 0)
            {
                $message = "Product not found.";
            }
            else
            {
                $product = pg_fetch_assoc($product_result);
                if($product["stock"] <= 0)
                {
                    $message = "Product is out of stock.";
                }
                else if($quantity > $product["stock"])
                {
                    $message = "Requested quantity is not available.";
                }
                else
                {
                    /*
                        Check Existing Pending Order
                    */
                    $order_query = "
                        SELECT order_id
                        FROM orders
                        WHERE customer_id = $1
                        AND status = 'Pending'
                        ORDER BY order_id DESC
                        LIMIT 1
                    ";
                    $order_result = pg_query_params(
                        $conn,
                        $order_query,
                        array($customer_id)
                    );
                    if(pg_num_rows($order_result) > 0)
                    {
                        $order = pg_fetch_assoc($order_result);
                        $order_id = $order["order_id"];
                    }
                    else
                    {
                        /*
                            Create Pending Order
                        */
                        $create_order_query = "
                            INSERT INTO orders
                            (date, time, status, customer_id)
                            VALUES
                            (CURRENT_DATE, CURRENT_TIME, 'Pending', $1)
                            RETURNING order_id
                        ";
                        $create_order_result = pg_query_params(
                            $conn,
                            $create_order_query,
                            array($customer_id)
                        );
                        if($create_order_result)
                        {
                            $order = pg_fetch_assoc(
                                $create_order_result
                            );
                            $order_id = $order["order_id"];
                        }
                        else
                        {
                            $message = "Unable to create cart.";
                        }
                    }
                    if($message == "")
                    {
                        /*
                            Check Existing Product In Cart
                        */
                        $item_query = "
                            SELECT order_item_id, quantity
                            FROM order_items
                            WHERE order_id = $1
                            AND product_id = $2
                        ";
                        $item_result = pg_query_params(
                            $conn,
                            $item_query,
                            array(
                                $order_id,
                                $product_id
                            )
                        );
                        if(pg_num_rows($item_result) > 0)
                        {
                            $item = pg_fetch_assoc(
                                $item_result
                            );
                            $new_quantity = $item["quantity"] + $quantity;
                            if($new_quantity > $product["stock"])
                            {
                                $message =
                                    "Total quantity is greater than available stock.";
                            }
                            else
                            {
                                $update_item_query = "
                                    UPDATE order_items
                                    SET quantity = $1,
                                        total_price = $1 * price
                                    WHERE order_item_id = $2
                                ";
                                $update_result = pg_query_params(
                                    $conn,
                                    $update_item_query,
                                    array(
                                        $new_quantity,
                                        $item["order_item_id"]
                                    )
                                );
                                if($update_result)
                                {
                                    $message =
                                        "Product added to cart.";
                                }
                                else
                                {
                                    $message =
                                        "Unable to update cart.";
                                }
                            }
                        }
                        else
                        {
                            $total_price =
                                $quantity * $product["price"];
                            $insert_item_query = "
                                INSERT INTO order_items
                                (
                                    order_id,
                                    product_id,
                                    quantity,
                                    price,
                                    total_price
                                )
                                VALUES
                                ($1, $2, $3, $4, $5)
                            ";
                            $insert_item_result = pg_query_params(
                                $conn,
                                $insert_item_query,
                                array(
                                    $order_id,
                                    $product_id,
                                    $quantity,
                                    $product["price"],
                                    $total_price
                                )
                            );
                            if($insert_item_result)
                            {
                                $message =
                                    "Product added to cart.";
                            }
                            else
                            {
                                $message =
                                    "Unable to add product to cart.";
                            }
                        }
                    }
                }
            }
        }
    }
    /*
        Remove From Cart
    */
    if(isset($_POST["remove_item"]))
    {
        $order_item_id = $_POST["order_item_id"];
        $remove_query = "
            DELETE FROM order_items
            WHERE order_item_id = $1
            AND order_id IN
            (
                SELECT order_id
                FROM orders
                WHERE customer_id = $2
                AND status = 'Pending'
            )
        ";
        $remove_result = pg_query_params(
            $conn,
            $remove_query,
            array(
                $order_item_id,
                $customer_id
            )
        );
        if($remove_result)
        {
            $message = "Product removed from cart.";
        }
        else
        {
            $message = "Unable to remove product.";
        }
    }
    /*
        Shop Now - Product
    */
    if(isset($_POST["shop_now"]))
    {
        $product_id = $_POST["product_id"];
        $quantity = $_POST["quantity"];
        $payment_method = $_POST["payment_method"];
        if($quantity <= 0)
        {
            $message = "Please enter valid quantity.";
        }
        else if($payment_method != "Cash")
        {
            $message = "Only cash payment is available.";
        }
        else
        {
            $product_query = "
                SELECT product_id, price, quantity, stock
                FROM product
                WHERE product_id = $1
            ";
            $product_result = pg_query_params(
                $conn,
                $product_query,
                array($product_id)
            );
            if(pg_num_rows($product_result) == 0)
            {
                $message = "Product not found.";
            }
            else
            {
                $product = pg_fetch_assoc(
                    $product_result
                );
                if($product["stock"] <= 0)
                {
                    $message = "Product is out of stock.";
                }
                else if($quantity > $product["stock"])
                {
                    $message =
                        "Requested quantity is not available.";
                }
                else
                {
                    $amount =
                        $quantity * $product["price"];
                    /*
                        Create Pending Order
                    */
                    $order_query = "
                        INSERT INTO orders
                        (date, time, status, customer_id)
                        VALUES
                        (
                            CURRENT_DATE,
                            CURRENT_TIME,
                            'Pending',
                            $1
                        )
                        RETURNING order_id
                    ";
                    $order_result = pg_query_params(
                        $conn,
                        $order_query,
                        array($customer_id)
                    );
                    if($order_result)
                    {
                        $order = pg_fetch_assoc(
                            $order_result
                        );
                        $order_id = $order["order_id"];
                        /*
                            Insert Order Item
                        */
                        $total_price =
                            $quantity * $product["price"];
                        $item_query = "
                            INSERT INTO order_items
                            (
                                order_id,
                                product_id,
                                quantity,
                                price,
                                total_price
                            )
                            VALUES
                            ($1, $2, $3, $4, $5)
                        ";
                        $item_result = pg_query_params(
                            $conn,
                            $item_query,
                            array(
                                $order_id,
                                $product_id,
                                $quantity,
                                $product["price"],
                                $total_price
                            )
                        );
                        if($item_result)
                        {
                            /*
                                Reduce Stock
                            */
                            $stock_query = "
                                UPDATE product
                                SET stock = stock - $1
                                WHERE product_id = $2
                                AND stock >= $1
                            ";
                            $stock_result = pg_query_params(
                                $conn,
                                $stock_query,
                                array(
                                    $quantity,
                                    $product_id
                                )
                            );
                            if($stock_result)
                            {
                                /*
                                    Order Placed - Payment Will Be Generated
                                    When Admin/Worker Changes Status To Completed
                                */
                                $_SESSION["success_message"] =
                                    "Order placed successfully. Payment method: Cash. Payment history will be generated when the order is completed.";
                                /*
                                    POST -> Redirect -> GET
                                */
                                header(
                                    "Location: shopping.php"
                                );
                                exit();
                            }
                            else
                            {
                                $message =
                                    "Unable to update stock.";
                            }
                        }
                        else
                        {
                            $message =
                                "Unable to add product to order.";
                        }
                    }
                    else
                    {
                        $message =
                            "Unable to create order.";
                    }
                }
            }
        }
    }
    /*
        Shop Now From Cart
    */
    if(isset($_POST["cart_shop_now"]))
    {
        $order_item_id = $_POST["order_item_id"];
        $payment_method = $_POST["payment_method"];
        if($payment_method != "Cash")
        {
            $message = "Only cash payment is available.";
        }
        else
        {
            /*
                Get Selected Cart Item
            */
            $cart_item_query = "
                SELECT
                    oi.order_item_id,
                    oi.quantity,
                    oi.price,
                    p.product_id,
                    p.name,
                    p.stock,
                    o.order_id
                FROM order_items oi
                JOIN orders o
                    ON oi.order_id = o.order_id
                JOIN product p
                    ON oi.product_id = p.product_id
                WHERE oi.order_item_id = $1
                AND o.customer_id = $2
                AND o.status = 'Pending'
            ";
            $cart_item_result = pg_query_params(
                $conn,
                $cart_item_query,
                array(
                    $order_item_id,
                    $customer_id
                )
            );
            if(pg_num_rows($cart_item_result) == 0)
            {
                $message =
                    "Cart product not found.";
            }
            else
            {
                $cart_product = pg_fetch_assoc(
                    $cart_item_result
                );
                $quantity =
                    $cart_product["quantity"];
                $product_id =
                    $cart_product["product_id"];
                $price =
                    $cart_product["price"];
                $amount =
                    $quantity * $price;
                if($cart_product["stock"] <= 0)
                {
                    $message =
                        "Product is out of stock.";
                }
                else if($quantity > $cart_product["stock"])
                {
                    $message =
                        "Available stock is less than cart quantity.";
                }
                else
                {
                    /*
                        Create New Pending Order
                        For Selected Cart Product
                    */
                    $new_order_query = "
                        INSERT INTO orders
                        (date, time, status, customer_id)
                        VALUES
                        (
                            CURRENT_DATE,
                            CURRENT_TIME,
                            'Pending',
                            $1
                        )
                        RETURNING order_id
                    ";
                    $new_order_result = pg_query_params(
                        $conn,
                        $new_order_query,
                        array($customer_id)
                    );
                    if($new_order_result)
                    {
                        $new_order =
                            pg_fetch_assoc(
                                $new_order_result
                            );
                        $new_order_id =
                            $new_order["order_id"];
                        /*
                            Insert Selected Product
                        */
                        $new_item_query = "
                            INSERT INTO order_items
                            (
                                order_id,
                                product_id,
                                quantity,
                                price,
                                total_price
                            )
                            VALUES
                            ($1, $2, $3, $4, $5)
                        ";
                        $new_item_result = pg_query_params(
                            $conn,
                            $new_item_query,
                            array(
                                $new_order_id,
                                $product_id,
                                $quantity,
                                $price,
                                $amount
                            )
                        );
                        if($new_item_result)
                        {
                            /*
                                Reduce Stock
                            */
                            $stock_query = "
                                UPDATE product
                                SET stock = stock - $1
                                WHERE product_id = $2
                                AND stock >= $1
                            ";
                            $stock_result =
                                pg_query_params(
                                    $conn,
                                    $stock_query,
                                    array(
                                        $quantity,
                                        $product_id
                                    )
                                );
                            if($stock_result)
                            {
                                /*
                                    Remove Product
                                    From Pending Cart
                                */
                                $delete_cart_item_query = "
                                    DELETE FROM order_items
                                    WHERE order_item_id = $1
                                ";
                                $delete_cart_result = pg_query_params(
                                    $conn,
                                    $delete_cart_item_query,
                                    array(
                                        $order_item_id
                                    )
                                );
                                if($delete_cart_result)
                                {
                                    /*
                                        Order Placed - Payment Will Be Generated
                                        When Admin/Worker Changes Status To Completed
                                    */
                                    $_SESSION["success_message"] =
                                        "Order placed successfully. Payment method: Cash. Payment history will be generated when the order is completed.";
                                    /*
                                        POST -> Redirect -> GET
                                    */
                                    header(
                                        "Location: shopping.php"
                                    );
                                    exit();
                                }
                                else
                                {
                                    $message =
                                        "Unable to remove product from cart.";
                                }
                            }
                            else
                            {
                                $message =
                                    "Unable to update stock.";
                            }
                        }
                        else
                        {
                            $message =
                                "Unable to create order item.";
                        }
                    }
                    else
                    {
                        $message =
                            "Unable to create order.";
                    }
                }
            }
        }
    }
    /*
        Add Customer Feedback
    */
    if(isset($_POST["add_feedback"]))
    {
        $rating = $_POST["rating"];
        $feedback_message = trim($_POST["feedback_message"]);
        if($rating == "" || $feedback_message == "")
        {
            $message =
                "Please enter rating and feedback.";
        }
        else if($rating < 1 || $rating > 5)
        {
            $message =
                "Rating must be between 1 and 5.";
        }
        else
        {
            $feedback_query = "
                INSERT INTO feedback
                (
                    message,
                    rating,
                    date,
                    time,
                    customer_id
                )
                VALUES
                (
                    $1,
                    $2,
                    CURRENT_DATE,
                    CURRENT_TIME,
                    $3
                )
            ";
            $feedback_result = pg_query_params(
                $conn,
                $feedback_query,
                array(
                    $feedback_message,
                    $rating,
                    $customer_id
                )
            );
            if($feedback_result)
            {
                $_SESSION["success_message"] =
                    "Feedback submitted successfully.";
                header(
                    "Location: shopping.php"
                );
                exit();
            }
            else
            {
                $message =
                    "Unable to submit feedback.";
            }
        }
    }
}
/*
    POST -> Redirect -> GET
    For Add To Cart / Remove / Errors
*/
if($_SERVER["REQUEST_METHOD"] == "POST")
{
    if($message != "")
    {
        $_SESSION["shopping_message"] = $message;
    }
    header("Location: shopping.php");
    exit();
}
/*
    Get Shopping Products
    Available For All Logged-In Users
*/
$product_query = "
    SELECT
        product_id,
        name,
        price,
        quantity,
        stock,
        image
    FROM product
    ORDER BY product_id
";
$product_result = pg_query(
    $conn,
    $product_query
);
/*
    Get Cart
    Customer Only
*/
$cart_result = false;
if($role == "customer" && $customer_id > 0)
{
    $cart_query = "
        SELECT
            oi.order_item_id,
            p.product_id,
            p.name,
            p.image,
            p.stock,
            oi.quantity,
            oi.price,
            oi.total_price AS subtotal
        FROM order_items oi
        JOIN orders o
            ON oi.order_id = o.order_id
        JOIN product p
            ON oi.product_id = p.product_id
        WHERE o.customer_id = $1
        AND o.status = 'Pending'
        ORDER BY oi.order_item_id DESC
    ";
    $cart_result = pg_query_params(
        $conn,
        $cart_query,
        array($customer_id)
    );
}
/*
    Get Payments
    Customer Only
*/
$payment_result = false;
if($role == "customer" && $customer_id > 0)
{
    $payment_query = "
        SELECT
            payment_id,
            order_id,
            amount,
            date,
            time,
            payment_type,
            description
        FROM order_payments
        WHERE order_id IN
        (
            SELECT order_id
            FROM orders
            WHERE customer_id = $1
        )
        ORDER BY payment_id DESC
    ";
    $payment_result = pg_query_params(
        $conn,
        $payment_query,
        array($customer_id)
    );
}
/*
    Get Normal Message After Redirect
*/
if(isset($_SESSION["shopping_message"]))
{
    $message = $_SESSION["shopping_message"];
    unset($_SESSION["shopping_message"]);
}
/*
    Get Other Customer Reviews
*/
$feedback_query = "
    SELECT
        f.feedback_id,
        f.message,
        f.rating,
        f.date,
        f.time,
        u.name
    FROM feedback f
    JOIN customer c
        ON f.customer_id = c.customer_id
    JOIN users u
        ON c.user_id = u.user_id
    ORDER BY f.feedback_id DESC
";
$feedback_result = pg_query(
    $conn,
    $feedback_query
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <title>
        Shopping - Onkareshwar Dairy Farm
    </title>
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet"
          href="shopping.css">
</head>
<body>
<header>
    <h1>
        Shopping
    </h1>
</header>
<!-- Customer Not Found Message -->
<?php
if($role == "customer" && $customer_id == 0)
{
?>
    <div class="customer-message">
        <?php
        echo htmlspecialchars($message);
        ?>
    </div>
<?php
}
else
{
?>
<!-- Navigation -->
<nav class="navbar">
    <a href="home.html"
       class="home-icon">
        <i class="fa-solid fa-house"></i>
        Home
    </a>
    <?php
    if($role == "customer")
    {
    ?>
        <div class="nav-right">
            <button
                type="button"
                onclick="openCart()">
                <i class="fa-solid fa-cart-shopping"></i>
                My Cart
            </button>
            <button
                type="button"
                onclick="openPayments()">
                <i class="fa-solid fa-credit-card"></i>
                Payment
            </button>
            <button
                type="button"
                onclick="openFeedback()">
                <i class="fa-solid fa-comment"></i>
                Feedback
            </button>
            <button
                type="button"
                onclick="openOtherReviews()">
                <i class="fa-solid fa-comments"></i>
                Other Reviews
            </button>
        </div>
    <?php
    }
    ?>
</nav>
<!-- Success Message -->
<?php
if($success_message != "")
{
?>
    <div class="success-message">
        <i class="fa-solid fa-circle-check"></i>
        <?php
        echo htmlspecialchars($success_message);
        ?>
    </div>
<?php
}
/* Normal Message */
if($message != "")
{
?>
    <div class="message">
        <?php
        echo htmlspecialchars($message);
        ?>
    </div>
<?php
}
?>
<!-- Shopping Image -->
<div class="shopping-image">
    <img src="images/shopping.jpg"
         alt="Dairy Farm Shopping">
</div>
<!-- Main -->
<main>
    <h2 class="shopping-title">
        Our Products
    </h2>
    <div class="shopping-container">
        <?php
        if(pg_num_rows($product_result) > 0)
        {
            while($product = pg_fetch_assoc($product_result))
            {
        ?>
        <div class="product-row">
            <!-- Product Image -->
            <div class="product-image">
                <img
                    src="<?php
                    echo htmlspecialchars(
                        $product["image"]
                    );
                    ?>"
                    alt="<?php
                    echo htmlspecialchars(
                        $product["name"]
                    );
                    ?>"
                >
            </div>
            <!-- Product Details -->
            <div class="product-details">
                <h2>
                    <?php
                    echo htmlspecialchars(
                        $product["name"]
                    );
                    ?>
                </h2>
                <p>
                    <strong>Price:</strong>
                    ₹<?php
                    echo htmlspecialchars(
                        $product["price"]
                    );
                    ?>
                </p>
                <p>
                    <strong>Pack Size:</strong>
                    <?php
                    echo htmlspecialchars(
                        $product["quantity"]
                    );
                    ?>
                </p>
                <p>
                    <strong>Available Stock:</strong>
                    <?php
                    echo htmlspecialchars(
                        $product["stock"]
                    );
                    ?>
                    packs
                </p>
                <?php
                if(
                    $role == "customer" &&
                    $customer_id > 0 &&
                    $product["stock"] > 0
                )
                {
                ?>
                <div class="product-buttons">
                    <!-- Add To Cart -->
                    <button
                        type="button"
                        onclick="openCartQuantity(
                            <?php
                            echo $product["product_id"];
                            ?>
                        )">
                        Add to Cart
                    </button>
                    <!-- Shop Now -->
                    <button
                        type="button"
                        onclick="openShopNow(
                            <?php
                            echo $product["product_id"];
                            ?>,
                            <?php
                            echo $product["price"];
                            ?>
                        )">
                        Shop Now
                    </button>
                </div>
                <?php
                }
                else if($product["stock"] <= 0)
                {
                ?>
                    <p class="out-of-stock">
                        Out of Stock
                    </p>
                <?php
                }
                ?>
            </div>
        </div>
        <!-- Add To Cart Popup -->
        <?php
        if(
            $role == "customer" &&
            $customer_id > 0 &&
            $product["stock"] > 0
        )
        {
        ?>
        <div
            class="popup"
            id="cartPopup<?php
                echo $product["product_id"];
            ?>"
        >
            <div class="popup-content">
                <span
                    class="close"
                    onclick="closeCartQuantity(
                        <?php
                        echo $product["product_id"];
                        ?>
                    )">
                    &times;
                </span>
                <h2>
                    Add to Cart
                </h2>
                <form
                    method="post"
                    action="shopping.php"
                >
                    <input
                        type="hidden"
                        name="product_id"
                        value="<?php
                        echo $product["product_id"];
                        ?>"
                    >
                    <label>
                        How many packs needed?
                    </label>
                    <input
                        type="number"
                        name="quantity"
                        min="1"
                        max="<?php
                        echo $product["stock"];
                        ?>"
                        required
                    >
                    <button
                        type="submit"
                        name="add_to_cart">
                        Add
                    </button>
                    <button
                        type="button"
                        onclick="closeCartQuantity(
                            <?php
                            echo $product["product_id"];
                            ?>
                        )">
                        Cancel
                    </button>
                </form>
            </div>
        </div>
        <!-- Shop Now Popup -->
        <div
            class="popup"
            id="shopPopup<?php
                echo $product["product_id"];
            ?>"
        >
            <div class="popup-content">
                <span
                    class="close"
                    onclick="closeShopNow(
                        <?php
                        echo $product["product_id"];
                        ?>
                    )">
                    &times;
                </span>
                <h2>
                    Shop Now
                </h2>
                <p>
                    <strong>Product:</strong>
                    <?php
                    echo htmlspecialchars(
                        $product["name"]
                    );
                    ?>
                </p>
                <p>
                    <strong>Price:</strong>
                    ₹<?php
                    echo htmlspecialchars(
                        $product["price"]
                    );
                    ?>
                </p>
                <form
                    method="post"
                    action="shopping.php"
                >
                    <input
                        type="hidden"
                        name="product_id"
                        value="<?php
                        echo $product["product_id"];
                        ?>"
                    >
                    <label>
                        Quantity
                    </label>
                    <input
                        type="number"
                        name="quantity"
                        min="1"
                        max="<?php
                        echo $product["stock"];
                        ?>"
                        value="1"
                        required
                    >
                    <label>
                        Select Payment Method
                    </label>
                    <select name="payment_method" required>
                        <option value="Cash">
                            Cash
                        </option>
                    </select>
                    <button
                        type="submit"
                        name="shop_now">
                        Pay
                    </button>
                    <button
                        type="button"
                        onclick="closeShopNow(
                            <?php
                            echo $product["product_id"];
                            ?>
                        )">
                        Cancel
                    </button>
                </form>
            </div>
        </div>
        <?php
        }
        ?>
        <?php
            }
        }
        else
        {
        ?>
            <p class="no-products">
                No products available.
            </p>
        <?php
        }
        ?>
    </div>
</main>
<?php
/*
    Customer Cart and Payment Popups
*/
if($role == "customer" && $customer_id > 0)
{
?>
<!-- Cart Popup -->
<div
    class="popup"
    id="cartMainPopup"
>
    <div class="popup-content cart-content">
        <span
            class="close"
            onclick="closeCart()">
            &times;
        </span>
        <h2>
            My Cart
        </h2>
        <?php
        $cart_total = 0;
        if(
            $cart_result &&
            pg_num_rows($cart_result) > 0
        )
        {
            while($cart = pg_fetch_assoc(
                $cart_result
            ))
            {
                $cart_total += $cart["subtotal"];
        ?>
        <div class="cart-item">
            <div class="cart-product-info">
                <img
                    src="<?php
                    echo htmlspecialchars(
                        $cart["image"]
                    );
                    ?>"
                    alt="<?php
                    echo htmlspecialchars(
                        $cart["name"]
                    );
                    ?>"
                    class="cart-image"
                >
                <div>
                    <h3>
                        <?php
                        echo htmlspecialchars(
                            $cart["name"]
                        );
                        ?>
                    </h3>
                    <p>
                        Quantity:
                        <?php
                        echo htmlspecialchars(
                            $cart["quantity"]
                        );
                        ?>
                    </p>
                    <p>
                        ₹<?php
                        echo htmlspecialchars(
                            $cart["subtotal"]
                        );
                        ?>
                    </p>
                </div>
            </div>
            <div class="cart-buttons">
                <!-- Cart Shop Now -->
                <button
                    type="button"
                    class="shop-cart-button"
                    onclick="openCartShopNow(
                        <?php
                        echo $cart["order_item_id"];
                        ?>
                    )">
                    Shop Now
                </button>
                <!-- Remove -->
                <form
                    method="post"
                    action="shopping.php"
                >
                    <input
                        type="hidden"
                        name="order_item_id"
                        value="<?php
                        echo $cart["order_item_id"];
                        ?>"
                    >
                    <button
                        type="submit"
                        name="remove_item"
                        class="remove-button">
                        Remove
                    </button>
                </form>
            </div>
        </div>
        <!-- Cart Shop Now Popup -->
        <div
            class="popup"
            id="cartShopPopup<?php
                echo $cart["order_item_id"];
            ?>"
        >
            <div class="popup-content">
                <span
                    class="close"
                    onclick="closeCartShopNow(
                        <?php
                        echo $cart["order_item_id"];
                        ?>
                    )">
                    &times;
                </span>
                <h2>
                    Shop Now
                </h2>
                <p>
                    <strong>Product:</strong>
                    <?php
                    echo htmlspecialchars(
                        $cart["name"]
                    );
                    ?>
                </p>
                <p>
                    <strong>Quantity:</strong>
                    <?php
                    echo htmlspecialchars(
                        $cart["quantity"]
                    );
                    ?>
                    packs
                </p>
                <p>
                    <strong>Total:</strong>
                    ₹<?php
                    echo htmlspecialchars(
                        $cart["subtotal"]
                    );
                    ?>
                </p>
                <form
                    method="post"
                    action="shopping.php"
                >
                    <input
                        type="hidden"
                        name="order_item_id"
                        value="<?php
                        echo $cart["order_item_id"];
                        ?>"
                    >
                    <label>
                        Selected Payment Method
                    </label>
                    <select
                        name="payment_method"
                        required
                    >
                        <option value="">
                            Select Method
                        </option>
                        <option value="Cash">
                            Cash
                        </option>
                    </select>
                    <button
                        type="submit"
                        name="cart_shop_now">
                        Pay
                    </button>
                    <button
                        type="button"
                        onclick="closeCartShopNow(
                            <?php
                            echo $cart["order_item_id"];
                            ?>
                        )">
                        Cancel
                    </button>
                </form>
            </div>
        </div>
        <?php
            }
        ?>
        <h3 class="cart-total">
            Total:
            ₹<?php
            echo $cart_total;
            ?>
        </h3>
        <?php
        }
        else
        {
        ?>
            <p>
                Your cart is empty.
            </p>
        <?php
        }
        ?>
        <button
            type="button"
            onclick="closeCart()">
            Close
        </button>
    </div>
</div>
<!-- Payment Popup -->
<div
    class="popup"
    id="paymentPopup"
>
    <div class="popup-content payment-content">
        <span
            class="close"
            onclick="closePayments()">
            &times;
        </span>
        <h2>
            Payments
        </h2>
        <?php
        if(
            $payment_result &&
            pg_num_rows($payment_result) > 0
        )
        {
            while($payment = pg_fetch_assoc(
                $payment_result
            ))
            {
        ?>
        <div class="payment-item">
            <p>
                <strong>Payment ID:</strong>
                <?php
                echo htmlspecialchars(
                    $payment["payment_id"]
                );
                ?>
            </p>
            <p>
                <strong>Order ID:</strong>
                <?php
                echo htmlspecialchars(
                    $payment["order_id"]
                );
                ?>
            </p>
            <p>
                <strong>Amount:</strong>
                ₹<?php
                echo htmlspecialchars(
                    $payment["amount"]
                );
                ?>
            </p>
            <p>
                <strong>Method:</strong>
                <?php
                echo htmlspecialchars(
                    $payment["payment_type"]
                );
                ?>
            </p>
            <p>
                <strong>Date:</strong>
                <?php
                echo htmlspecialchars(
                    $payment["date"]
                );
                ?>
            </p>
            <p>
                <strong>Time:</strong>
                <?php
                echo htmlspecialchars(
                    $payment["time"]
                );
                ?>
            </p>
            <p>
                <strong>Description:</strong>
                <?php
                echo htmlspecialchars(
                    $payment["description"]
                );
                ?>
            </p>
        </div>
        <?php
            }
        }
        else
        {
        ?>
            <p>
                No payments found.
            </p>
        <?php
        }
        ?>
        <button
            type="button"
            onclick="closePayments()">
            Close
        </button>
    </div>
</div>
<!-- Feedback Popup -->
<div
    class="popup"
    id="feedbackPopup">
    <div class="popup-content feedback-content">
        <span
            class="close"
            onclick="closeFeedback()">
            &times;
        </span>
        <h2>Give Feedback</h2>
        <form method="post" action="shopping.php">
            <label>Rating</label>
            <select name="rating" required>
                <option value="">Select Rating</option>
                <option value="5">5 - Excellent</option>
                <option value="4">4 - Very Good</option>
                <option value="3">3 - Good</option>
                <option value="2">2 - Poor</option>
                <option value="1"> 1 - Very Poor</option>
            </select>
            <label>
                Your Feedback
            </label>
            <textarea
                name="feedback_message"
                rows="5"
                placeholder="Write your feedback..."
                required
            ></textarea>
            <button
                type="submit"
                name="add_feedback">
                Submit Feedback
            </button>
            <button type="button" onclick="closeFeedback()">Cancel</button>
        </form>
    </div>
</div>
<!-- Other Reviews Popup -->
<div
    class="popup"
    id="otherReviewsPopup">
    <div class="popup-content reviews-content">
        <span
            class="close"
            onclick="closeOtherReviews()">
            &times;
        </span>
        <h2> Other Customer Reviews</h2>
        <?php
        if(
            $feedback_result &&
            pg_num_rows($feedback_result) > 0
        )
        {
            while($feedback = pg_fetch_assoc(
                $feedback_result
            ))
            {
        ?>
        <div class="review-item">
            <h3>
                <?php
                echo htmlspecialchars(
                    $feedback["name"]
                );
                ?>
            </h3>
            <p class="review-rating">
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
                echo htmlspecialchars(
                    $feedback["date"]
                );
                ?>
                &nbsp;
                <?php
                echo htmlspecialchars(
                    $feedback["time"]
                );
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
                No customer reviews available.
            </p>
        <?php
        }
        ?>
        <button type="button" onclick="closeOtherReviews()">Close </button>
    </div>
</div>
<?php
}
?>
<script>
/*
    Add To Cart Popup
*/
function openCartQuantity(productId)
{
    document.getElementById(
        "cartPopup" + productId
    ).style.display = "flex";
}
function closeCartQuantity(productId)
{
    document.getElementById(
        "cartPopup" + productId
    ).style.display = "none";
}
/*
    Product Shop Now
*/
function openShopNow(productId, price)
{
    document.getElementById(
        "shopPopup" + productId
    ).style.display = "flex";
}
function closeShopNow(productId)
{
    document.getElementById(
        "shopPopup" + productId
    ).style.display = "none";
}
/*
    Main Cart
*/
function openCart()
{
    document.getElementById(
        "cartMainPopup"
    ).style.display = "flex";
}
function closeCart()
{
    document.getElementById(
        "cartMainPopup"
    ).style.display = "none";
}
/*
    Cart Shop Now
*/
function openCartShopNow(orderItemId)
{
    document.getElementById(
        "cartShopPopup" + orderItemId
    ).style.display = "flex";
}
function closeCartShopNow(orderItemId)
{
    document.getElementById(
        "cartShopPopup" + orderItemId
    ).style.display = "none";
}
/*
    Payments
*/
function openPayments()
{
    document.getElementById(
        "paymentPopup"
    ).style.display = "flex";
}
function closePayments()
{
    document.getElementById(
        "paymentPopup"
    ).style.display = "none";
}
/*
    Feedback
*/
function openFeedback()
{
    document.getElementById(
        "feedbackPopup"
    ).style.display = "flex";
}
function closeFeedback()
{
    document.getElementById(
        "feedbackPopup"
    ).style.display = "none";
}
/*
    Other Reviews
*/
function openOtherReviews()
{
    document.getElementById(
        "otherReviewsPopup"
    ).style.display = "flex";
}
function closeOtherReviews()
{
    document.getElementById(
        "otherReviewsPopup"
    ).style.display = "none";
}
/*
    Close Popup When
    Clicking Outside
*/
window.onclick = function(event)
{
    if(event.target.classList.contains("popup"))
    {
        event.target.style.display = "none";
    }
}
</script>
<?php
}
?>
</body>
</html>
<?php
pg_close($conn);
?>
