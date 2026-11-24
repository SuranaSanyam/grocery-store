<?php
$rootConn = @mysqli_connect('localhost', 'root', '');
if ($rootConn) {
    mysqli_query($rootConn, "CREATE DATABASE IF NOT EXISTS `grocery` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
}
require_once __DIR__ . '/db.php';

// Create required tables
$queries = [
    "CREATE TABLE IF NOT EXISTS `categories` (
        `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL UNIQUE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS `product` (
        `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `price` DECIMAL(10,2) NOT NULL,
        `image` VARCHAR(255) NOT NULL DEFAULT '',
        `category_id` INT NULL DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS `users` (
        `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `email` VARCHAR(255) NOT NULL UNIQUE,
        `contact` VARCHAR(30) NOT NULL,
        `password` VARCHAR(255) NOT NULL,
        `role` ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS `contact` (
        `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(255) NOT NULL,
        `email` VARCHAR(255) NOT NULL,
        `contact` VARCHAR(255) NOT NULL,
        `msg` VARCHAR(255) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS `orders` (
        `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NULL DEFAULT NULL,
        `customer_name` VARCHAR(100) NOT NULL,
        `customer_email` VARCHAR(255) NOT NULL,
        `customer_phone` VARCHAR(30) NOT NULL,
        `delivery_address` TEXT NOT NULL,
        `total_amount` DECIMAL(10,2) NOT NULL,
        `payment_method` VARCHAR(50) NOT NULL DEFAULT 'Cash on Delivery',
        `status` VARCHAR(50) NOT NULL DEFAULT 'Processing',
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS `order_items` (
        `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `order_id` INT NOT NULL,
        `product_id` INT NULL DEFAULT NULL,
        `product_name` VARCHAR(100) NOT NULL,
        `price` DECIMAL(10,2) NOT NULL,
        `quantity` INT NOT NULL,
        `subtotal` DECIMAL(10,2) NOT NULL,
        KEY (`order_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];

foreach ($queries as $query) {
    if (!mysqli_query($db, $query)) {
        http_response_code(500);
        exit('Database setup error: ' . htmlspecialchars(mysqli_error($db), ENT_QUOTES, 'UTF-8'));
    }
}

// Ensure category_id column exists in product
$column = mysqli_query($db, "SHOW COLUMNS FROM `product` LIKE 'category_id'");
if ($column && mysqli_num_rows($column) === 0) {
    mysqli_query($db, 'ALTER TABLE `product` ADD `category_id` INT NULL DEFAULT NULL');
}

// Starter categories
$starterCategories = ['Fruit & vegetables', 'Dairy & eggs', 'Bakery', 'Pantry'];
$seedCat = mysqli_prepare($db, 'INSERT IGNORE INTO `categories` (`name`) VALUES (?)');
if ($seedCat) {
    foreach ($starterCategories as $categoryName) {
        mysqli_stmt_bind_param($seedCat, 's', $categoryName);
        mysqli_stmt_execute($seedCat);
    }
}

// Map category names to IDs
$catMap = [];
$catRes = mysqli_query($db, "SELECT `id`, `name` FROM `categories`");
if ($catRes) {
    while ($row = mysqli_fetch_assoc($catRes)) {
        $catMap[$row['name']] = (int) $row['id'];
    }
}

// Remove dummy test rows if any
mysqli_query($db, "DELETE FROM `product` WHERE `id` IN (5, 6, 7) OR `category_id` IS NULL");

// Starter grocery products
$starterProducts = [
    ['name' => 'Fresh Kashmiri Apples', 'price' => 120.00, 'image' => 'apples.jpg', 'cat' => $catMap['Fruit & vegetables'] ?? 1],
    ['name' => 'Farm Fresh Whole Milk', 'price' => 65.00, 'image' => 'milk.jpg', 'cat' => $catMap['Dairy & eggs'] ?? 2],
    ['name' => 'Artisan Brown Bread', 'price' => 45.00, 'image' => 'bread.jpg', 'cat' => $catMap['Bakery'] ?? 3],
    ['name' => 'Premium Basmati Rice', 'price' => 140.00, 'image' => 'rice.svg', 'cat' => $catMap['Pantry'] ?? 4],
    ['name' => 'Farm Fresh Tomatoes', 'price' => 40.00, 'image' => 'tomatoes.svg', 'cat' => $catMap['Fruit & vegetables'] ?? 1],
    ['name' => 'Farm Brown Eggs (12 pcs)', 'price' => 85.00, 'image' => 'eggs.svg', 'cat' => $catMap['Dairy & eggs'] ?? 2],
    ['name' => 'Butter Croissants (4 pcs)', 'price' => 90.00, 'image' => 'croissant.svg', 'cat' => $catMap['Bakery'] ?? 3],
    ['name' => 'Extra Virgin Olive Oil', 'price' => 420.00, 'image' => 'olive_oil.svg', 'cat' => $catMap['Pantry'] ?? 4],
];

foreach ($starterProducts as $prod) {
    $pCheck = mysqli_query($db, "SELECT `id` FROM `product` WHERE `name` = '" . mysqli_real_escape_string($db, $prod['name']) . "'");
    if ($pCheck && mysqli_num_rows($pCheck) === 0) {
        $stmt = mysqli_prepare($db, 'INSERT INTO `product` (`name`, `price`, `image`, `category_id`) VALUES (?, ?, ?, ?)');
        if ($stmt) {
            $pPrice = (float) $prod['price'];
            $pCat = (int) $prod['cat'];
            mysqli_stmt_bind_param($stmt, 'sdsi', $prod['name'], $pPrice, $prod['image'], $pCat);
            mysqli_stmt_execute($stmt);
        }
    }
}

// Starter Admin Account (admin@grocery.com / admin123)
$adminCheck = mysqli_query($db, "SELECT `id` FROM `users` WHERE `role` = 'admin'");
if ($adminCheck && mysqli_num_rows($adminCheck) === 0) {
    $adminHash = password_hash('admin123', PASSWORD_DEFAULT);
    $aStmt = mysqli_prepare($db, "INSERT INTO `users` (`name`, `email`, `contact`, `password`, `role`) VALUES (?, ?, ?, ?, 'admin')");
    if ($aStmt) {
        $aName = 'Store Admin';
        $aEmail = 'admin@grocery.com';
        $aPhone = '9876543210';
        mysqli_stmt_bind_param($aStmt, 'ssss', $aName, $aEmail, $aPhone, $adminHash);
        mysqli_stmt_execute($aStmt);
    }
}

// Starter Customer Account (user@grocery.com / user123)
$userCheck = mysqli_query($db, "SELECT `id` FROM `users` WHERE `email` = 'user@grocery.com'");
if ($userCheck && mysqli_num_rows($userCheck) === 0) {
    $userHash = password_hash('user123', PASSWORD_DEFAULT);
    $uStmt = mysqli_prepare($db, "INSERT INTO `users` (`name`, `email`, `contact`, `password`, `role`) VALUES (?, ?, ?, ?, 'customer')");
    if ($uStmt) {
        $uName = 'Rahul Sharma';
        $uEmail = 'user@grocery.com';
        $uPhone = '9876543211';
        mysqli_stmt_bind_param($uStmt, 'ssss', $uName, $uEmail, $uPhone, $userHash);
        mysqli_stmt_execute($uStmt);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Grocery Basket - Setup Complete</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <section class="paper-card p-4 shadow-sm">
                <div class="d-flex align-items-center mb-3">
                    <span class="badge bg-success fs-6 me-2">Setup Ready</span>
                    <h1 class="h3 mb-0">Grocery Store Database Initialized</h1>
                </div>
                <p class="text-muted">All database tables, categories, starter grocery items, and test accounts are configured.</p>
                
                <div class="card bg-light mb-4 border-0">
                    <div class="card-body">
                        <h6 class="fw-bold mb-2">Pre-configured Demo Accounts:</h6>
                        <ul class="mb-0 small text-secondary">
                            <li><strong>Admin Login:</strong> <code>admin@grocery.com</code> | Password: <code>admin123</code></li>
                            <li><strong>Customer Login:</strong> <code>user@grocery.com</code> | Password: <code>user123</code></li>
                        </ul>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-shop" href="index.php">Go to Grocery Store</a>
                    <a class="btn btn-outline-secondary" href="admin_login.php">Admin Login</a>
                    <a class="btn btn-outline-secondary" href="login.php">Customer Login</a>
                </div>
            </section>
        </div>
    </div>
</main>
</body>
</html>
