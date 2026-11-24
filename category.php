<?php
// Display Products Category-Wise using PHP and Bootstrap
require_once __DIR__ . '/functions.php';

// Check if a specific category ID was requested
$categoryId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$selectedCategory = null;
$products = null;
$error = null;

// Fetch all available categories for the top filter tabs
$allCategories = mysqli_query($db, 'SELECT `id`, `name` FROM `categories` ORDER BY `name`');

if ($categoryId) {
    // 1. Fetch the requested category
    $stmtCat = mysqli_prepare($db, 'SELECT `id`, `name` FROM `categories` WHERE `id` = ?');
    if ($stmtCat) {
        mysqli_stmt_bind_param($stmtCat, 'i', $categoryId);
        mysqli_stmt_execute($stmtCat);
        $res = mysqli_stmt_get_result($stmtCat);
        $selectedCategory = mysqli_fetch_assoc($res);
    }

    if (!$selectedCategory) {
        http_response_code(404);
        $error = 'The requested category could not be found.';
    } else {
        // 2. Fetch products belonging strictly to this category
        $stmtProd = mysqli_prepare($db, 'SELECT p.`id`, p.`name`, p.`price`, p.`image`, c.`name` AS category_name
            FROM `product` p 
            LEFT JOIN `categories` c ON c.`id` = p.`category_id` 
            WHERE p.`category_id` = ? 
            ORDER BY p.`name` ASC');
        if ($stmtProd) {
            mysqli_stmt_bind_param($stmtProd, 'i', $categoryId);
            mysqli_stmt_execute($stmtProd);
            $products = mysqli_stmt_get_result($stmtProd);
        }
    }
} else {
    // If no category ID is specified, show all products across all categories
    $products = mysqli_query($db, 'SELECT p.`id`, p.`name`, p.`price`, p.`image`, c.`name` AS category_name
        FROM `product` p 
        LEFT JOIN `categories` c ON c.`id` = p.`category_id` 
        ORDER BY p.`id` DESC');
}

$productCount = $products ? mysqli_num_rows($products) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $selectedCategory ? e($selectedCategory['name']) : 'Browse All Categories'; ?> - Grocery Basket</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css?v=2">
</head>
<body>

<?php include __DIR__ . '/header.php'; ?>

<main class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item <?php echo !$selectedCategory ? 'active' : ''; ?>">
                <?php if ($selectedCategory): ?>
                    <a href="category.php">Categories</a>
                <?php else: ?>
                    All Categories
                <?php endif; ?>
            </li>
            <?php if ($selectedCategory): ?>
                <li class="breadcrumb-item active" aria-current="page"><?php echo e($selectedCategory['name']); ?></li>
            <?php endif; ?>
        </ol>
    </nav>

    <!-- Page Title & Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 fw-bold mb-1">
                <?php echo $selectedCategory ? e($selectedCategory['name']) : 'All Grocery Items'; ?>
            </h1>
            <p class="text-muted small mb-0">
                <?php if ($selectedCategory): ?>
                    Browse all fresh items available in our <?php echo e($selectedCategory['name']); ?> department.
                <?php else: ?>
                    Explore our complete selection of fresh produce, dairy, bakery, and pantry essentials.
                <?php endif; ?>
            </p>
        </div>
        <div class="mt-2 mt-sm-0">
            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">
                <?php echo $productCount; ?> <?php echo $productCount === 1 ? 'item' : 'items'; ?> available
            </span>
        </div>
    </div>

    <!-- Category Filter Tabs / Pills -->
    <section class="mb-4">
        <div class="d-flex flex-wrap gap-2 pb-2 border-bottom">
            <a href="category.php" 
               class="btn btn-sm <?php echo !$selectedCategory ? 'btn-success' : 'btn-outline-secondary'; ?>">
                All Departments
            </a>
            <?php if ($allCategories): ?>
                <?php while ($cat = mysqli_fetch_assoc($allCategories)): ?>
                    <a href="category.php?id=<?php echo (int) $cat['id']; ?>" 
                       class="btn btn-sm <?php echo ($selectedCategory && (int)$selectedCategory['id'] === (int)$cat['id']) ? 'btn-success' : 'btn-outline-secondary'; ?>">
                        <?php echo e($cat['name']); ?>
                    </a>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- Error message if category not found -->
    <?php if ($error): ?>
        <div class="alert alert-warning my-4">
            <?php echo e($error); ?> <a href="category.php" class="alert-link">Browse all categories</a>.
        </div>
    <?php endif; ?>

    <!-- Product Grid Category-wise -->
    <?php if ($products && $productCount > 0): ?>
        <div class="row g-3">
            <?php while ($product = mysqli_fetch_assoc($products)): ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <article class="product-card h-100 shadow-sm">
                        <a href="product.php?id=<?php echo (int) $product['id']; ?>" class="text-decoration-none">
                            <?php $image = image_url($product['image']); ?>
                            <?php if ($image !== ''): ?>
                                <img class="product-image" src="<?php echo e($image); ?>" alt="<?php echo e($product['name']); ?>">
                            <?php else: ?>
                                <div class="product-image-placeholder">🥬 Fresh Grocery</div>
                            <?php endif; ?>
                        </a>
                        <div class="card-body">
                            <span class="badge bg-light text-secondary border mb-2 align-self-start">
                                <?php echo e($product['category_name'] ?: 'Grocery'); ?>
                            </span>
                            <h2>
                                <a class="text-decoration-none text-dark" href="product.php?id=<?php echo (int) $product['id']; ?>">
                                    <?php echo e($product['name']); ?>
                                </a>
                            </h2>
                            <div class="mt-auto pt-2 d-flex justify-content-between align-items-center gap-1">
                                <span class="product-price">₹<?php echo number_format((float) $product['price'], 2); ?></span>
                                <div class="d-flex gap-1">
                                    <a class="btn btn-sm btn-outline-secondary" href="product.php?id=<?php echo (int) $product['id']; ?>">
                                        Details
                                    </a>
                                    <form method="post" action="cart.php" class="d-inline">
                                        <input type="hidden" name="action" value="add">
                                        <input type="hidden" name="product_id" value="<?php echo (int) $product['id']; ?>">
                                        <input type="hidden" name="quantity" value="1">
                                        <button class="btn btn-sm btn-shop" type="submit" title="Add to Cart">
                                            + Cart
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>
            <?php endwhile; ?>
        </div>
    <?php elseif (!$error): ?>
        <div class="text-center py-5 bg-white border rounded">
            <span class="fs-1">🛒</span>
            <h4 class="mt-2">No products in this category yet</h4>
            <p class="text-muted small">We are restocking this section soon. Please check back later or explore other categories.</p>
            <a class="btn btn-shop mt-2" href="category.php">View All Products</a>
        </div>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/footer.php'; ?>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
