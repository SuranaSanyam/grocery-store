<?php
// Practical 3 & 7: Detailed Single Product Page using PHP and Bootstrap
require_once __DIR__ . '/functions.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$product = null;

if ($id) {
    // Fetch product details joined with category name
    $stmt = mysqli_prepare($db, 'SELECT p.`id`, p.`name`, p.`price`, p.`image`, p.`category_id`, c.`name` AS category_name
        FROM `product` p 
        LEFT JOIN `categories` c ON c.`id` = p.`category_id` 
        WHERE p.`id` = ?');
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $product = mysqli_fetch_assoc($res);
    }
}

if (!$product) {
    http_response_code(404);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $product ? e($product['name']) : 'Product Not Found'; ?> - Grocery Basket</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css?v=2">
</head>
<body>

<?php include __DIR__ . '/header.php'; ?>

<main class="container py-4">
    <?php if ($product): ?>
        <!-- Breadcrumb Navigation -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb small">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="category.php">Categories</a></li>
                <?php if (!empty($product['category_name'])): ?>
                    <li class="breadcrumb-item">
                        <a href="category.php?id=<?php echo (int) $product['category_id']; ?>">
                            <?php echo e($product['category_name']); ?>
                        </a>
                    </li>
                <?php endif; ?>
                <li class="breadcrumb-item active" aria-current="page"><?php echo e($product['name']); ?></li>
            </ol>
        </nav>

        <section class="paper-card p-4 p-md-5 shadow-sm">
            <div class="row g-4 align-items-center">
                <!-- Product Image Column -->
                <div class="col-md-6 col-lg-5 text-center">
                    <?php $img = image_url($product['image']); ?>
                    <?php if ($img !== ''): ?>
                        <img class="img-fluid rounded border shadow-sm" style="max-height: 380px; width: 100%; object-fit: cover;" 
                             src="<?php echo e($img); ?>" alt="<?php echo e($product['name']); ?>">
                    <?php else: ?>
                        <div class="product-image-placeholder rounded border" style="height: 320px;">
                            <div class="text-center">
                                <span class="fs-1 d-block mb-2">🥬</span>
                                <span>Fresh Grocery Item</span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Product Information Column -->
                <div class="col-md-6 col-lg-7">
                    <span class="badge bg-success-subtle text-success border border-success-subtle mb-2">
                        <?php echo e($product['category_name'] ?: 'Everyday Essentials'); ?>
                    </span>

                    <h1 class="h2 fw-bold mb-2"><?php echo e($product['name']); ?></h1>

                    <div class="d-flex align-items-baseline gap-2 mb-3">
                        <span class="display-6 fw-bold text-success">
                            ₹<?php echo number_format((float) $product['price'], 2); ?>
                        </span>
                        <span class="text-muted small">(Inclusive of all taxes)</span>
                    </div>

                    <div class="mb-3">
                        <span class="badge bg-success me-1">✔ In Stock</span>
                        <span class="badge bg-light text-secondary border">Standard Packing</span>
                    </div>

                    <p class="text-muted mb-4">
                        Hand-selected for quality and fresh flavour. Perfect for everyday home cooking, nutritional balance, and healthy family meals.
                    </p>

                    <!-- Product Specifications Table -->
                    <div class="table-responsive mb-4">
                        <table class="table table-sm table-borderless small mb-0">
                            <tbody>
                                <tr>
                                    <th class="text-muted" style="width: 140px;">Category:</th>
                                    <td><?php echo e($product['category_name'] ?: 'General Grocery'); ?></td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Shelf Life:</th>
                                    <td>Guaranteed fresh upon delivery</td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Storage Advice:</th>
                                    <td>Store in a cool, dry place away from direct sunlight</td>
                                </tr>
                                <tr>
                                    <th class="text-muted">Delivery:</th>
                                    <td>Free next-day delivery on neighbourhood orders above ₹299</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Action Buttons: Add to Cart & Inquiry -->
                    <div class="pt-3 border-top">
                        <form method="post" action="cart.php" class="d-flex flex-wrap align-items-center gap-2 mb-3">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="product_id" value="<?php echo (int) $product['id']; ?>">
                            <div class="d-flex align-items-center">
                                <label for="prod_qty" class="me-2 small fw-semibold text-muted">Quantity:</label>
                                <input type="number" id="prod_qty" name="quantity" value="1" min="1" max="50" 
                                       class="form-control text-center" style="width: 75px;">
                            </div>
                            <button class="btn btn-shop px-4 py-2" type="submit">
                                 Add to Cart
                            </button>
                            <a class="btn btn-outline-success px-3 py-2" href="contact.php?product=<?php echo urlencode($product['name']); ?>">
                                 Quick Inquiry
                            </a>
                        </form>

                        <div class="d-flex flex-wrap gap-2 pt-2">
                            <?php if (!empty($product['category_id'])): ?>
                                <a class="btn btn-sm btn-outline-secondary" href="category.php?id=<?php echo (int) $product['category_id']; ?>">
                                    &larr; More in <?php echo e($product['category_name']); ?>
                                </a>
                            <?php endif; ?>
                            <a class="btn btn-sm btn-outline-secondary" href="category.php">All Categories</a>
                            <a class="btn btn-sm btn-outline-secondary" href="index.php">Store Home</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    <?php else: ?>
        <!-- 404 Product Not Found -->
        <section class="paper-card p-5 text-center shadow-sm my-5">
            <span class="fs-1">🔍</span>
            <h1 class="h3 fw-bold mt-3">Product Not Found</h1>
            <p class="text-muted">The product you are looking for may have been removed, sold out, or the link is incorrect.</p>
            <div class="mt-4">
                <a class="btn btn-shop" href="category.php">Browse All Categories</a>
                <a class="btn btn-outline-secondary ms-2" href="index.php">Go to Homepage</a>
            </div>
        </section>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/footer.php'; ?>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
