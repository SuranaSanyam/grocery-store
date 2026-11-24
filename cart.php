<?php
// Shopping Cart & Order Placement for Grocery Basket
require_once __DIR__ . '/functions.php';

// Initialize session cart if not exists
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$errors = [];
$userId = !empty($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;

// Pre-fill user data if logged in
$customerName = $_SESSION['user_name'] ?? '';
$customerEmail = '';
$customerPhone = '';
$deliveryAddress = '';

if ($userId) {
    $uStmt = mysqli_prepare($db, 'SELECT `name`, `email`, `contact` FROM `users` WHERE `id` = ?');
    if ($uStmt) {
        mysqli_stmt_bind_param($uStmt, 'i', $userId);
        mysqli_stmt_execute($uStmt);
        $uRes = mysqli_stmt_get_result($uStmt);
        if ($uData = mysqli_fetch_assoc($uRes)) {
            $customerName = $uData['name'];
            $customerEmail = $uData['email'];
            $customerPhone = $uData['contact'];
        }
    }
}

// Handle Cart Actions & Checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Add item to cart
    if ($action === 'add') {
        $prodId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
        $qty = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT) ?: 1;
        if ($prodId && $qty > 0) {
            $_SESSION['cart'][$prodId] = ($_SESSION['cart'][$prodId] ?? 0) + $qty;
            set_flash('Item added to your shopping cart!', 'success');
        }
        redirect('cart.php');
    }
    // 2. Update quantity
    elseif ($action === 'update') {
        $prodId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
        $qty = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
        if ($prodId) {
            if ($qty > 0) {
                $_SESSION['cart'][$prodId] = $qty;
            } else {
                unset($_SESSION['cart'][$prodId]);
            }
            set_flash('Cart updated.', 'info');
        }
        redirect('cart.php');
    }
    // 3. Remove single item
    elseif ($action === 'remove') {
        $prodId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
        if ($prodId && isset($_SESSION['cart'][$prodId])) {
            unset($_SESSION['cart'][$prodId]);
            set_flash('Item removed from cart.', 'info');
        }
        redirect('cart.php');
    }
    // 4. Clear entire cart
    elseif ($action === 'clear') {
        $_SESSION['cart'] = [];
        set_flash('Your shopping cart has been cleared.', 'info');
        redirect('cart.php');
    }
    // 5. Place Order & Save in Database
    elseif ($action === 'place_order') {
        if (!verify_csrf()) {
            $errors[] = 'Session expired. Please try submitting your order again.';
        } elseif (empty($_SESSION['cart'])) {
            $errors[] = 'Your cart is empty. Add items before placing an order.';
        } else {
            $customerName = trim($_POST['customer_name'] ?? '');
            $customerPhone = trim($_POST['customer_phone'] ?? '');
            $customerEmail = trim($_POST['customer_email'] ?? '');
            $deliveryAddress = trim($_POST['delivery_address'] ?? '');

            if (empty($customerName) || mb_strlen($customerName) > 100) {
                $errors[] = 'Please enter your full name.';
            }
            if (empty($customerPhone) || strlen($customerPhone) > 30) {
                $errors[] = 'Please enter a contact phone number.';
            }
            if (!empty($customerEmail) && !filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Please enter a valid email address.';
            }
            if (empty($deliveryAddress) || mb_strlen($deliveryAddress) < 5) {
                $errors[] = 'Please provide a complete delivery address for delivery.';
            }

            if (empty($errors)) {
                // Fetch product data and compute total
                $ids = array_map('intval', array_keys($_SESSION['cart']));
                $idList = implode(',', $ids);
                $cartQuery = mysqli_query($db, "SELECT `id`, `name`, `price` FROM `product` WHERE `id` IN ($idList)");
                
                $orderTotal = 0.0;
                $itemsToInsert = [];
                if ($cartQuery) {
                    while ($prod = mysqli_fetch_assoc($cartQuery)) {
                        $pId = (int) $prod['id'];
                        $qty = (int) ($_SESSION['cart'][$pId] ?? 1);
                        $price = (float) $prod['price'];
                        $subtotal = $price * $qty;
                        $orderTotal += $subtotal;
                        $itemsToInsert[] = [
                            'product_id' => $pId,
                            'name' => $prod['name'],
                            'price' => $price,
                            'quantity' => $qty,
                            'subtotal' => $subtotal
                        ];
                    }
                }

                if ($orderTotal > 0 && !empty($itemsToInsert)) {
                    // Insert into orders table
                    $orderStmt = mysqli_prepare($db, 'INSERT INTO `orders` (`user_id`, `customer_name`, `customer_email`, `customer_phone`, `delivery_address`, `total_amount`, `payment_method`, `status`) VALUES (?, ?, ?, ?, ?, ?, \'Cash on Delivery\', \'Processing\')');
                    if ($orderStmt) {
                        mysqli_stmt_bind_param($orderStmt, 'issssd', $userId, $customerName, $customerEmail, $customerPhone, $deliveryAddress, $orderTotal);
                        if (mysqli_stmt_execute($orderStmt)) {
                            $newOrderId = mysqli_insert_id($db);

                            // Insert into order_items table
                            $itemStmt = mysqli_prepare($db, 'INSERT INTO `order_items` (`order_id`, `product_id`, `product_name`, `price`, `quantity`, `subtotal`) VALUES (?, ?, ?, ?, ?, ?)');
                            if ($itemStmt) {
                                foreach ($itemsToInsert as $it) {
                                    mysqli_stmt_bind_param($itemStmt, 'iisdid', $newOrderId, $it['product_id'], $it['name'], $it['price'], $it['quantity'], $it['subtotal']);
                                    mysqli_stmt_execute($itemStmt);
                                }
                            }

                            // Empty the cart
                            $_SESSION['cart'] = [];
                            set_flash('Your grocery order #' . $newOrderId . ' has been placed successfully!', 'success');
                            redirect('orders.php?order_id=' . $newOrderId);
                        } else {
                            $errors[] = 'Failed to save order in the database. Please try again.';
                        }
                    } else {
                        $errors[] = 'Database error preparing order statement.';
                    }
                } else {
                    $errors[] = 'Could not calculate order total.';
                }
            }
        }
    }
}

// Fetch products currently in cart for display
$cartItems = [];
$totalAmount = 0.0;

if (!empty($_SESSION['cart'])) {
    $ids = array_map('intval', array_keys($_SESSION['cart']));
    if (!empty($ids)) {
        $idList = implode(',', $ids);
        $cartQuery = mysqli_query($db, "SELECT p.`id`, p.`name`, p.`price`, p.`image`, c.`name` AS category_name 
            FROM `product` p 
            LEFT JOIN `categories` c ON c.`id` = p.`category_id` 
            WHERE p.`id` IN ($idList)");
        if ($cartQuery) {
            while ($row = mysqli_fetch_assoc($cartQuery)) {
                $pid = (int) $row['id'];
                $qty = (int) ($_SESSION['cart'][$pid] ?? 1);
                $subtotal = ((float) $row['price']) * $qty;
                $totalAmount += $subtotal;
                $cartItems[] = [
                    'id' => $pid,
                    'name' => $row['name'],
                    'price' => (float) $row['price'],
                    'image' => $row['image'],
                    'category' => $row['category_name'],
                    'qty' => $qty,
                    'subtotal' => $subtotal
                ];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Shopping Cart - Grocery Basket</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css?v=2">
</head>
<body class="bg-light">

<?php include __DIR__ . '/header.php'; ?>

<main class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">Your Shopping Cart</h1>
            <p class="text-muted small mb-0">Review selected grocery items, enter delivery address, and place order.</p>
        </div>
        <div class="mt-2 mt-sm-0 d-flex gap-2">
            <a href="orders.php" class="btn btn-outline-primary btn-sm">📦 View Order History</a>
            <a href="category.php" class="btn btn-outline-secondary btn-sm">&larr; Continue Shopping</a>
        </div>
    </div>

    <!-- Flash message display -->
    <?php show_flash(); ?>

    <!-- Error Alerts -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger" role="alert">
            <strong class="d-block mb-1">Please fix the following:</strong>
            <ul class="mb-0 ps-3 small">
                <?php foreach ($errors as $err): ?>
                    <li><?php echo e($err); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (!empty($cartItems)): ?>
        <div class="row g-4">
            <!-- Cart Items Column -->
            <div class="col-lg-7">
                <div class="table-responsive paper-card shadow-sm bg-white mb-3">
                    <table class="table align-middle table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width: 60px; min-width: 60px;">Item</th>
                                <th>Product</th>
                                <th>Price</th>
                                <th style="width: 120px;">Quantity</th>
                                <th>Subtotal</th>
                                <th class="text-end" style="width: 50px;">Remove</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cartItems as $item): ?>
                                <tr>
                                    <td style="width: 60px; min-width: 60px;">
                                        <?php $img = image_url($item['image']); ?>
                                        <?php if ($img !== ''): ?>
                                            <img src="<?php echo e($img); ?>" alt="<?php echo e($item['name']); ?>" 
                                                 width="48" height="48"
                                                 style="width: 48px; height: 48px; min-width: 48px; max-width: 48px; max-height: 48px; object-fit: cover; border-radius: 6px; display: block; border: 1px solid #e2e8f0;">
                                        <?php else: ?>
                                            <div class="product-image-placeholder small" style="width: 48px; height: 48px;">🥬</div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="product.php?id=<?php echo $item['id']; ?>" class="text-decoration-none text-dark fw-bold">
                                            <?php echo e($item['name']); ?>
                                        </a>
                                        <div class="small text-muted"><?php echo e($item['category'] ?: 'Grocery'); ?></div>
                                    </td>
                                    <td>₹<?php echo number_format($item['price'], 2); ?></td>
                                    <td>
                                        <form method="post" action="cart.php" class="d-flex align-items-center gap-1">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                            <input class="form-control form-control-sm text-center" type="number" 
                                                   name="quantity" value="<?php echo $item['qty']; ?>" min="1" max="99" style="width: 55px;">
                                            <button class="btn btn-sm btn-outline-secondary px-2" type="submit" title="Update">✔</button>
                                        </form>
                                    </td>
                                    <td class="fw-bold text-success">
                                        ₹<?php echo number_format($item['subtotal'], 2); ?>
                                    </td>
                                    <td class="text-end">
                                        <form method="post" action="cart.php" class="d-inline">
                                            <input type="hidden" name="action" value="remove">
                                            <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                            <button class="btn btn-sm btn-outline-danger" type="submit" title="Remove item">✕</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between mb-4">
                    <form method="post" action="cart.php" onsubmit="return confirm('Empty your shopping cart?');">
                        <input type="hidden" name="action" value="clear">
                        <button class="btn btn-outline-danger btn-sm" type="submit">Clear Cart</button>
                    </form>
                    <a href="category.php" class="btn btn-outline-success btn-sm">+ Add More Items</a>
                </div>
            </div>

            <!-- Delivery Details & Order Submission Form -->
            <div class="col-lg-5">
                <div class="paper-card p-4 shadow-sm bg-white border-top border-4 border-success">
                    <h5 class="fw-bold mb-3">Delivery &amp; Checkout Details</h5>

                    <form method="post" action="cart.php">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="place_order">

                        <div class="mb-3">
                            <label class="form-label small fw-semibold" for="customer_name">Full Name <span class="text-danger">*</span></label>
                            <input class="form-control form-control-sm" type="text" id="customer_name" name="customer_name" 
                                   value="<?php echo e($customerName); ?>" placeholder="e.g. Rahul Sharma" required>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold" for="customer_phone">Contact Number <span class="text-danger">*</span></label>
                                <input class="form-control form-control-sm" type="tel" id="customer_phone" name="customer_phone" 
                                       value="<?php echo e($customerPhone); ?>" placeholder="e.g. 9876543210" required>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold" for="customer_email">Email Address</label>
                                <input class="form-control form-control-sm" type="email" id="customer_email" name="customer_email" 
                                       value="<?php echo e($customerEmail); ?>" placeholder="name@example.com">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold" for="delivery_address">Delivery Address <span class="text-danger">*</span></label>
                            <textarea class="form-control form-control-sm" id="delivery_address" name="delivery_address" rows="3" 
                                      placeholder="Apartment, Street name, Landmark, City..." required><?php echo e($deliveryAddress); ?></textarea>
                        </div>

                        <!-- Price Breakdown -->
                        <div class="p-3 bg-light rounded border mb-3">
                            <div class="d-flex justify-content-between mb-1 small text-muted">
                                <span>Items Subtotal:</span>
                                <span>₹<?php echo number_format($totalAmount, 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2 small text-muted">
                                <span>Delivery Fee:</span>
                                <span class="text-success fw-bold">FREE</span>
                            </div>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between fs-5 fw-bold text-dark">
                                <span>Total Amount:</span>
                                <span class="text-success">₹<?php echo number_format($totalAmount, 2); ?></span>
                            </div>
                            <div class="small text-muted mt-2">
                                💵 Payment Mode: <strong>Cash on Delivery (COD)</strong>
                            </div>
                        </div>

                        <button class="btn btn-shop w-100 py-2 fw-semibold" type="submit">
                            Place Order &amp; Save in Database
                        </button>
                    </form>

                    <div class="mt-3 text-center small text-muted">
                        🔒 Safe &amp; verified checkout. Free neighbour delivery.
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="text-center py-5 paper-card bg-white shadow-sm my-4">
            <span class="fs-1 d-block mb-2">🛒</span>
            <h3 class="fw-bold">Your Cart is Currently Empty</h3>
            <p class="text-muted small">You have not added any groceries to your shopping cart yet.</p>
            <div class="d-flex gap-2 justify-content-center mt-3">
                <a class="btn btn-shop px-4 py-2" href="category.php">Browse Fresh Groceries</a>
                <a class="btn btn-outline-secondary px-4 py-2" href="orders.php">View Past Orders</a>
            </div>
        </div>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/footer.php'; ?>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
