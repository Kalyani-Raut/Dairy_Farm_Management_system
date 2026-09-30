CREATE TABLE farm(
    farm_id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name VARCHAR(100),
    address VARCHAR(200),
    location VARCHAR(100),
    phone_no VARCHAR(15),
    email_id VARCHAR(100),
    establish_date DATE,
    owner VARCHAR(100)
);
CREATE TABLE users(
    user_id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name VARCHAR(100),
    email_id VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    unique_code VARCHAR(50) UNIQUE,
    phone_no VARCHAR(15),
    address VARCHAR(200),
    role VARCHAR(30),
    farm_id INT,
    FOREIGN KEY(farm_id) REFERENCES farm(farm_id)
);
CREATE TABLE customer(
    customer_id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    user_id INT UNIQUE,
    FOREIGN KEY(user_id) REFERENCES users(user_id)
);
CREATE TABLE workers(
    worker_id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    description VARCHAR(300),
    user_id INT UNIQUE,
    FOREIGN KEY(user_id) REFERENCES users(user_id)
);
CREATE TABLE supplier(
    supplier_id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name VARCHAR(100),
    phone_no VARCHAR(15),
    address VARCHAR(200),
    description VARCHAR(300),
    farm_id INT,
    FOREIGN KEY(farm_id) REFERENCES farm(farm_id)
);
CREATE TABLE product(
    product_id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    name VARCHAR(100),
    image VARCHAR(255),
    price DECIMAL(10,2),
    quantity VARCHAR(50),
    stock INT,
    making_date DATE,
    expiry_date DATE,
    ingredients TEXT,
    nutrition_value TEXT,
    description TEXT
);
CREATE TABLE orders(
    order_id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    date DATE,
    time TIME,
    delivery_add VARCHAR(300),
    description TEXT,
    status VARCHAR(50),
    customer_id INT,
    FOREIGN KEY(customer_id) REFERENCES customer(customer_id)
);
CREATE TABLE order_items(
    order_item_id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    product_id INT,
    order_id INT,
    quantity INT,
    price DECIMAL(10,2),
    total_price DECIMAL(10,2),
    FOREIGN KEY(product_id) REFERENCES product(product_id),
    FOREIGN KEY(order_id) REFERENCES orders(order_id)
);
CREATE TABLE order_payments(
    payment_id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    amount DECIMAL(10,2),
    date DATE,
    time TIME,
    payment_type VARCHAR(50),
    description TEXT,
    order_id INT UNIQUE,
    FOREIGN KEY(order_id) REFERENCES orders(order_id)
);
CREATE TABLE feedback(
    feedback_id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    message TEXT,
    rating INT,
    date DATE,
    time TIME,
    customer_id INT,
    FOREIGN KEY(customer_id) REFERENCES customer(customer_id)
);
CREATE TABLE milk_collection(
    collection_id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    date DATE,
    time TIME,
    supplier_id INT,
    milk_quality VARCHAR(100),
    milk_quantity DECIMAL(10,2),
    compensation DECIMAL(10,2),
    description TEXT,
    FOREIGN KEY(supplier_id) REFERENCES supplier(supplier_id)
);
INSERT INTO farm
(name, address, location, phone_no, email_id, establish_date, owner)
VALUES
(
    'Onkareshwar Dairy Farm',
    'Sangamner',
    'Maharashtra',
    '9876543210',
    'onkareshwardairy@gmail.com',
    '2020-06-15',
    'Swapnil Raut'
);
ALTER TABLE users
ADD COLUMN gender VARCHAR(20);
select * from users;
INSERT INTO product
(name, image, price, quantity, stock, making_date, expiry_date, ingredients, nutrition_value, description)
VALUES
(
    'Fresh Milk',
    'images/milk.jpg',
    60.00,
    '1 litre',
    20,
    '2026-09-14',
    '2026-09-16',
    'Pure Cow Milk',
    'Protein, Calcium, Vitamin D',
    'Fresh and pure cow milk.'
),
(
    'Paneer',
    'images/paneer.jpg',
    90.00,
    '250 gm',
    15,
    '2026-09-14',
    '2026-09-17',
    'Milk, Citric Acid',
    'Protein, Calcium, Fat',
    'Fresh homemade dairy paneer.'
),
(
    'Curd',
    'images/curd.jpg',
    40.00,
    '500 gm',
    25,
    '2026-09-14',
    '2026-09-18',
    'Milk, Bacterial Culture',
    'Protein, Calcium, Probiotics',
    'Fresh and creamy dairy curd.'
);
DELETE FROM users
WHERE email_id = 'shruti@gmail.com';
select * from users;

SELECT order_id, status
FROM orders;
SELECT tgname
FROM pg_trigger
WHERE tgname = 'payment_after_order_completed';

DROP TRIGGER IF EXISTS payment_after_order_completed
ON orders;

SELECT *
FROM orders;

SELECT order_id, status
FROM orders
ORDER BY order_id DESC
LIMIT 5;

SELECT *
FROM order_payments
ORDER BY payment_id DESC;

SELECT column_name, data_type
FROM information_schema.columns
WHERE table_name = 'order_payments'
ORDER BY ordinal_position;

CREATE OR REPLACE FUNCTION create_payment_after_completed()
RETURNS TRIGGER
AS $$
DECLARE
    total_amount DECIMAL(10,2);
BEGIN
    IF OLD.status = 'Pending'
       AND NEW.status = 'Completed'
    THEN
        SELECT COALESCE(SUM(total_price), 0)
        INTO total_amount
        FROM order_items
        WHERE order_id = NEW.order_id;

        INSERT INTO order_payments
        (
            amount,
            date,
            time,
            payment_type,
            description,
            order_id
        )
        VALUES
        (
            total_amount,
            CURRENT_DATE,
            CURRENT_TIME,
            'Cash',
            'Payment successful',
            NEW.order_id
        );
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER payment_after_order_completed
AFTER UPDATE OF status
ON orders
FOR EACH ROW
EXECUTE FUNCTION create_payment_after_completed();

SELECT tgname
FROM pg_trigger
WHERE tgname = 'payment_after_order_completed';

DELETE FROM order_payments;
DELETE FROM order_items;
DELETE FROM orders;

SELECT * FROM order_payments;
SELECT * FROM order_items;
SELECT * FROM orders;

SELECT column_name, data_type
FROM information_schema.columns
WHERE table_name = 'users'
ORDER BY ordinal_position;

TRUNCATE TABLE
    order_payments,
    order_items,
    orders
RESTART IDENTITY;

SELECT
    table_name,
    column_name,
    data_type,
    character_maximum_length,
    numeric_precision,
    numeric_scale,
    is_nullable,
    column_default
FROM information_schema.columns
WHERE table_name IN
(
    'farm',
    'users',
    'customer',
    'workers',
    'product',
    'supplier',
    'milk_collection',
    'orders',
    'order_items',
    'order_payments',
    'feedback'
)
ORDER BY
    table_name,
    ordinal_position;

SELECT
    tc.table_name,
    tc.constraint_name,
    tc.constraint_type,
    kcu.column_name
FROM information_schema.table_constraints tc
LEFT JOIN information_schema.key_column_usage kcu
    ON tc.constraint_name = kcu.constraint_name
    AND tc.table_name = kcu.table_name
WHERE tc.table_name IN
(
    'farm',
    'users',
    'customer',
    'workers',
    'product',
    'supplier',
    'milk_collection',
    'orders',
    'order_items',
    'order_payments',
    'feedback'
)
ORDER BY
    tc.table_name,
    tc.constraint_type,
    kcu.column_name;	