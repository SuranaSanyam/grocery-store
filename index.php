<?php
// Practical: Home Page for Grocery Store Web Application
require_once __DIR__ . '/functions.php';

// Fetch all categories
$categories = mysqli_query($db, 'SELECT `id`, `name` FROM `categories` ORDER BY `name`');

// Fetch recent products with their category names
$products = mysqli_query($db, 'SELECT p.`id`, p.`name`, p.`price`, p.`image`, c.`name` AS category_name
    FROM `product` p 
    LEFT JOIN `categories` c ON c.`id` = p.`category_id`
    ORDER BY p.`id` DESC LIMIT 8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Grocery Basket - Fresh Everyday Groceries</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css?v=2">
</head>
<body>

<?php include __DIR__ . '/header.php'; ?>

<main class="container py-3">
    <!-- Hero Banner Section -->
    <section class="hero text-center text-md-start">
        <div class="row align-items-center">
            <div class="col-md-8">
                <span class="badge bg-success mb-2 px-3 py-2 text-uppercase">100% Farm Fresh</span>
                <h1 class="display-6 mb-3">Fresh Groceries Delivered Daily to Your Kitchen</h1>
                <p class="mb-4">Shop your daily essentials from fresh fruits and vegetables to bakery goods, farm milk, and pantry staples.</p>
                <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-md-start">
                    <a class="btn btn-shop px-4 py-2" href="category.php">Shop All Categories</a>
                    <a class="btn btn-outline-success px-4 py-2" href="#products-heading">View Products</a>
                </div>
            </div>
            <div class="col-md-4 text-center mt-4 mt-md-0 d-none d-md-block">
                <span style="font-size: 5rem;">🥦🍎🍞</span>
            </div>
        </div>
    </section>

    <!-- Flash message display -->
    <?php show_flash(); ?>

    <!-- Categories Section -->
    <section class="mb-5" aria-labelledby="categories-heading">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="section-heading mb-0" id="categories-heading">Shop By Category</h2>
            <a href="category.php" class="text-decoration-none small fw-semibold">View All &rarr;</a>
        </div>
        <div class="row g-3">
            <?php if ($categories && mysqli_num_rows($categories) > 0): ?>
                <?php while ($category = mysqli_fetch_assoc($categories)): ?>
                    <div class="col-6 col-md-3">
                        <a class="category-card p-3 shadow-sm text-center text-decoration-none d-block h-100" 
                           href="category.php?id=<?php echo (int) $category['id']; ?>">
                            <div class="fw-bold fs-6 text-dark mb-1"><?php echo e($category['name']); ?></div>
                            <span class="small text-success">Browse &rarr;</span>
                        </a>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12"><p class="text-muted">No categories available yet. Please run <a href="setup.php">setup.php</a>.</p></div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Products Showcase Section -->
    <section class="mb-5" aria-labelledby="products-heading">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="section-heading mb-0" id="products-heading">Featured Grocery Products</h2>
            <a href="category.php" class="text-decoration-none small fw-semibold">Browse Full Catalogue &rarr;</a>
        </div>
        <div class="row g-3">
            <?php if ($products && mysqli_num_rows($products) > 0): ?>
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
                                        <a class="btn btn-sm btn-outline-secondary" href="product.php?id=<?php echo (int) $product['id']; ?>">Details</a>
                                        <form method="post" action="cart.php" class="d-inline">
                                            <input type="hidden" name="action" value="add">
                                            <input type="hidden" name="product_id" value="<?php echo (int) $product['id']; ?>">
                                            <input type="hidden" name="quantity" value="1">
                                            <button class="btn btn-sm btn-shop" type="submit" title="Add to Cart">+ Cart</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </article>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12"><p class="text-muted">No products found. Please add products from the admin panel.</p></div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Value Highlights Section -->
    <section class="py-4 my-4 bg-white border rounded p-4">
        <div class="row text-center g-4">
            <div class="col-md-4">
                <div class="fs-2 mb-2">🌿</div>
                <h5 class="fw-bold">100% Quality Assured</h5>
                <p class="text-muted small mb-0">Handpicked farm-fresh fruits, vegetables, and pantry staples every morning.</p>
            </div>
            <div class="col-md-4">
                <div class="fs-2 mb-2">🚚</div>
                <h5 class="fw-bold">Fast Local Delivery</h5>
                <p class="text-muted small mb-0">Quick doorstep delivery across your neighbourhood so food stays crisp and fresh.</p>
            </div>
            <div class="col-md-4">
                <div class="fs-2 mb-2">🏷️</div>
                <h5 class="fw-bold">Everyday Low Prices</h5>
                <p class="text-muted small mb-0">Transparent fair pricing with great value deals on your essential groceries.</p>
            </div>
        </div>
    </section>
</main>

<?php include __DIR__ . '/footer.php'; ?>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
