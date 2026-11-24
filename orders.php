<?php
// Order History & Order Confirmation Page for Grocery Basket
require_once __DIR__ . '/functions.php';

$highlightOrderId = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT);
$userId = !empty($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;

// Search for orders
$orders = [];
if ($highlightOrderId) {
    // If specific order ID is requested (e.g. right after checkout)
    if ($userId && ($_SESSION['role'] ?? '') !== 'admin') {
        $stmt = mysqli_prepare($db, 'SELECT * FROM `orders` WHERE `id` = ? AND (`user_id` = ? OR `user_id` IS NULL) ORDER BY `id` DESC');
        mysqli_stmt_bind_param($stmt, 'ii', $highlightOrderId, $userId);
    } else {
        $stmt = mysqli_prepare($db, 'SELECT * FROM `orders` WHERE `id` = ? ORDER BY `id` DESC');
        mysqli_stmt_bind_param($stmt, 'i', $highlightOrderId);
    }
    if ($stmt) {
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) {
            $orders[] = $row;
        }
    }
} elseif ($userId) {
    // If logged-in customer views order history
    $stmt = mysqli_prepare($db, 'SELECT * FROM `orders` WHERE `user_id` = ? ORDER BY `id` DESC');
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $userId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) {
            $orders[] = $row;
        }
    }
}

// Function to fetch order items for a given order ID
function get_order_items(mysqli $db, int $orderId): array
{
    $items = [];
    $stmt = mysqli_prepare($db, 'SELECT oi.*, p.image FROM `order_items` oi LEFT JOIN `product` p ON p.id = oi.product_id WHERE oi.`order_id` = ?');
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $orderId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($r = mysqli_fetch_assoc($res)) {
            $items[] = $r;
        }
    }
    return $items;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Order History - Grocery Basket</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css?v=2">
    <style>
        @media print {
            .navbar, .site-footer, .btn, .breadcrumb, .no-print {
                display: none !important;
            }
            .paper-card {
                box-shadow: none !important;
                border: 1px solid #ccc !important;
            }
        }
    </style>
</head>
<body class="bg-light">

<?php include __DIR__ . '/header.php'; ?>

<main class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3 no-print">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="category.php">Categories</a></li>
            <li class="breadcrumb-item active" aria-current="page">Order History</li>
        </ol>
    </nav>

    <!-- Flash message display -->
    <div class="no-print">
        <?php show_flash(); ?>
    </div>

    <!-- Success Confirmation Banner if just ordered -->
    <?php if ($highlightOrderId && !empty($orders)): ?>
        <div class="alert alert-success d-flex align-items-center justify-content-between p-3 mb-4 shadow-sm no-print">
            <div>
                <h4 class="alert-heading mb-1 fw-bold">🎉 Order Placed Successfully!</h4>
                <p class="mb-0 small">Thank you! Your order <strong>#<?php echo (int) $highlightOrderId; ?></strong> has been received and saved. Our packing team is preparing your fresh groceries.</p>
            </div>
            <button class="btn btn-outline-success btn-sm ms-3 d-none d-sm-inline-block" onclick="window.print()">
                🖨️ Print Receipt
            </button>
        </div>
    <?php endif; ?>

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold mb-1">
                <?php echo $userId ? 'My Order History' : 'Order Details'; ?>
            </h1>
            <p class="text-muted small mb-0">View order summaries, item receipts, and delivery tracking.</p>
        </div>
        <div class="no-print mt-2 mt-sm-0">
            <a href="category.php" class="btn btn-outline-secondary btn-sm">&larr; Shop More Groceries</a>
        </div>
    </div>

    <?php if (!empty($orders)): ?>
        <div class="row g-4">
            <?php foreach ($orders as $order): ?>
                <?php 
                $orderItems = get_order_items($db, (int) $order['id']); 
                $status = $order['status'] ?? 'Processing';
                $statusBadgeClass = 'bg-warning text-dark';
                if ($status === 'Dispatched') $statusBadgeClass = 'bg-info text-dark';
                if ($status === 'Delivered') $statusBadgeClass = 'bg-success';
                if ($status === 'Cancelled') $statusBadgeClass = 'bg-danger';
                ?>
                <div class="col-12">
                    <section class="paper-card p-4 p-md-5 shadow-sm bg-white border">
                        <!-- Order Header -->
                        <div class="d-flex flex-wrap justify-content-between align-items-center pb-3 mb-3 border-bottom">
                            <div>
                                <span class="badge bg-secondary-subtle text-secondary border me-2">Order #<?php echo (int) $order['id']; ?></span>
                                <span class="badge <?php echo $statusBadgeClass; ?>"><?php echo e($status); ?></span>
                                <div class="text-muted small mt-1">
                                    Placed on: <strong><?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?></strong>
                                </div>
                            </div>
                            <div class="text-end mt-2 mt-sm-0 no-print">
                                <button class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                                    🖨️ Print Receipt
                                </button>
                            </div>
                        </div>

                        <!-- Customer & Delivery Info -->
                        <div class="row g-3 mb-4 p-3 bg-light rounded small">
                            <div class="col-md-4">
                                <strong class="text-muted d-block text-uppercase" style="font-size: 11px;">Delivery Recipient:</strong>
                                <span class="fw-bold text-dark"><?php echo e($order['customer_name']); ?></span>
                            </div>
                            <div class="col-md-4">
                                <strong class="text-muted d-block text-uppercase" style="font-size: 11px;">Contact Details:</strong>
                                <span> <?php echo e($order['customer_phone']); ?></span><br>
                                <span> <?php echo e($order['customer_email']); ?></span>
                            </div>
                            <div class="col-md-4">
                                <strong class="text-muted d-block text-uppercase" style="font-size: 11px;">Delivery Address:</strong>
                                <span>📍 <?php echo nl2br(e($order['delivery_address'])); ?></span>
                            </div>
                        </div>

                        <!-- Ordered Items Table -->
                        <h6 class="fw-bold text-uppercase small text-muted mb-2">Items in this Order</h6>
                        <div class="table-responsive mb-3">
                            <table class="table align-middle table-sm table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 50px;">Photo</th>
                                        <th>Grocery Item</th>
                                        <th>Price (₹)</th>
                                        <th class="text-center" style="width: 80px;">Qty</th>
                                        <th class="text-end" style="width: 110px;">Subtotal (₹)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($orderItems)): ?>
                                        <?php foreach ($orderItems as $item): ?>
                                            <tr>
                                                <td style="width: 50px;">
                                                    <?php $img = image_url($item['image'] ?? ''); ?>
                                                    <?php if ($img !== ''): ?>
                                                        <img src="<?php echo e($img); ?>" alt="<?php echo e($item['product_name']); ?>" 
                                                             width="40" height="40"
                                                             style="width: 40px; height: 40px; min-width: 40px; max-width: 40px; max-height: 40px; object-fit: cover; border-radius: 6px; display: block; border: 1px solid #e2e8f0;">
                                                    <?php else: ?>
                                                        <div class="product-image-placeholder small text-center rounded" 
                                                             style="width: 40px; height: 40px; line-height: 40px; font-size: 11px; border: 1px solid #e2e8f0;">
                                                            🥬
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <strong><?php echo e($item['product_name']); ?></strong>
                                                </td>
                                                <td>₹<?php echo number_format((float) $item['price'], 2); ?></td>
                                                <td class="text-center">× <?php echo (int) $item['quantity']; ?></td>
                                                <td class="text-end fw-bold text-success">
                                                    ₹<?php echo number_format((float) $item['subtotal'], 2); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="4" class="text-end fw-semibold">Delivery Fee:</td>
                                        <td class="text-end text-success fw-bold">FREE</td>
                                    </tr>
                                    <tr class="table-light">
                                        <td colspan="4" class="text-end fw-bold fs-6">Grand Total:</td>
                                        <td class="text-end fw-bold text-success fs-5">
                                            ₹<?php echo number_format((float) $order['total_amount'], 2); ?>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="d-flex justify-content-between align-items-center small text-muted pt-2 border-top">
                            <span>Payment Method: <strong><?php echo e($order['payment_method']); ?></strong></span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle">Saved in Database</span>
                        </div>
                    </section>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <!-- No Orders Found -->
        <section class="paper-card p-5 text-center shadow-sm bg-white my-4">
            <span class="fs-1 d-block mb-2">📦</span>
            <h3 class="fw-bold">No Order History Found</h3>
            <p class="text-muted small">
                <?php if ($userId): ?>
                    You haven't placed any grocery orders yet. Start adding items to your cart!
                <?php else: ?>
                    Please log in to your account to view your past orders, or place a new order from your shopping cart.
                <?php endif; ?>
            </p>
            <div class="mt-3">
                <a class="btn btn-shop px-4" href="category.php">Browse Fresh Groceries</a>
                <?php if (!$userId): ?>
                    <a class="btn btn-outline-secondary ms-2" href="login.php">Log In</a>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/footer.php'; ?>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
