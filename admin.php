<?php
// Practical 3, 5 & 6: Clean Admin Product, Category & Order Management
require_once __DIR__ . '/functions.php';

// Ensure user has admin privileges
require_role('admin');

$errors = [];

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Session expired. Please try submitting again.';
    } else {
        $action = $_POST['action'] ?? '';

        // 1. Add Category
        if ($action === 'add_category') {
            $categoryName = trim($_POST['category_name'] ?? '');
            if (empty($categoryName) || mb_strlen($categoryName) > 100) {
                $errors[] = 'Category name must be between 1 and 100 characters.';
            } else {
                $stmt = mysqli_prepare($db, 'INSERT INTO `categories` (`name`) VALUES (?)');
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, 's', $categoryName);
                    if (mysqli_stmt_execute($stmt)) {
                        set_flash('Category "' . $categoryName . '" was added successfully!', 'success');
                        redirect('admin.php?tab=categories');
                    } else {
                        $errors[] = mysqli_stmt_errno($stmt) === 1062
                            ? 'Category "' . $categoryName . '" already exists.'
                            : 'Database error adding category.';
                    }
                } else {
                    $errors[] = 'Failed to prepare category insertion query.';
                }
            }
        }
        // 2. Delete Category
        elseif ($action === 'delete_category') {
            $catId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
            if ($catId) {
                // Set products in this category to NULL so products are not orphaned
                $unassign = mysqli_prepare($db, 'UPDATE `product` SET `category_id` = NULL WHERE `category_id` = ?');
                if ($unassign) {
                    mysqli_stmt_bind_param($unassign, 'i', $catId);
                    mysqli_stmt_execute($unassign);
                }

                $delCat = mysqli_prepare($db, 'DELETE FROM `categories` WHERE `id` = ?');
                if ($delCat) {
                    mysqli_stmt_bind_param($delCat, 'i', $catId);
                    if (mysqli_stmt_execute($delCat)) {
                        set_flash('Category removed successfully.', 'success');
                        redirect('admin.php?tab=categories');
                    } else {
                        $errors[] = 'Failed to delete category.';
                    }
                }
            } else {
                $errors[] = 'Invalid category ID.';
            }
        }
        // 3. Add Product
        elseif ($action === 'add_product') {
            $name = trim($_POST['name'] ?? '');
            $price = trim($_POST['price'] ?? '');
            $categoryId = filter_var($_POST['category_id'] ?? null, FILTER_VALIDATE_INT);

            if (empty($name) || mb_strlen($name) > 100) {
                $errors[] = 'Product name is required (up to 100 characters).';
            }
            if (!is_numeric($price) || (float) $price <= 0 || (float) $price > 99999999.99) {
                $errors[] = 'Please enter a valid price greater than zero.';
            }
            if (!$categoryId) {
                $errors[] = 'Please select a department category for the product.';
            }

            // Process image upload
            $imageError = null;
            $imageName = store_uploaded_image('image', $imageError);
            if ($imageError) {
                $errors[] = $imageError;
            }

            if (empty($errors)) {
                $imgToSave = $imageName ?? '';
                $priceVal = (float) $price;
                $stmtProd = mysqli_prepare($db, 'INSERT INTO `product` (`name`, `price`, `image`, `category_id`) VALUES (?, ?, ?, ?)');
                if ($stmtProd) {
                    mysqli_stmt_bind_param($stmtProd, 'sdsi', $name, $priceVal, $imgToSave, $categoryId);
                    if (mysqli_stmt_execute($stmtProd)) {
                        set_flash('Product "' . $name . '" added successfully to inventory!', 'success');
                        redirect('admin.php?tab=products');
                    } else {
                        $errors[] = 'Failed to insert product into database.';
                    }
                } else {
                    $errors[] = 'Database error preparing product insert query.';
                }
            }
        }
        // 4. Update Order Status
        elseif ($action === 'update_order_status') {
            $orderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
            $newStatus = trim($_POST['status'] ?? 'Processing');
            $allowed = ['Processing', 'Dispatched', 'Delivered', 'Cancelled'];
            if ($orderId && in_array($newStatus, $allowed, true)) {
                $uStmt = mysqli_prepare($db, 'UPDATE `orders` SET `status` = ? WHERE `id` = ?');
                if ($uStmt) {
                    mysqli_stmt_bind_param($uStmt, 'si', $newStatus, $orderId);
                    mysqli_stmt_execute($uStmt);
                    set_flash("Order #$orderId status updated to '$newStatus'!", 'success');
                    redirect('admin.php?tab=orders');
                }
            }
        }
        // 5. Delete Order
        elseif ($action === 'delete_order') {
            $orderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
            if ($orderId) {
                mysqli_query($db, "DELETE FROM `order_items` WHERE `order_id` = $orderId");
                mysqli_query($db, "DELETE FROM `orders` WHERE `id` = $orderId");
                set_flash("Order #$orderId has been deleted from records.", 'info');
                redirect('admin.php?tab=orders');
            }
        }
        // 6. Delete Customer Message
        elseif ($action === 'delete_message') {
            $msgId = filter_input(INPUT_POST, 'message_id', FILTER_VALIDATE_INT);
            if ($msgId) {
                $delMsg = mysqli_prepare($db, 'DELETE FROM `contact` WHERE `id` = ?');
                if ($delMsg) {
                    mysqli_stmt_bind_param($delMsg, 'i', $msgId);
                    mysqli_stmt_execute($delMsg);
                    set_flash('Customer inquiry removed.', 'success');
                    redirect('admin.php?tab=messages');
                }
            }
        }
    }
}

// Active Tab
$activeTab = $_GET['tab'] ?? 'products';

// Fetch stats for dashboard cards
$totalProds = 0;
$totalCats = 0;
$totalMsgs = 0;
$totalOrders = 0;

$pCount = mysqli_query($db, "SELECT COUNT(*) as c FROM `product`");
if ($pCount) $totalProds = (int) mysqli_fetch_assoc($pCount)['c'];

$cCount = mysqli_query($db, "SELECT COUNT(*) as c FROM `categories`");
if ($cCount) $totalCats = (int) mysqli_fetch_assoc($cCount)['c'];

$oCount = mysqli_query($db, "SELECT COUNT(*) as c FROM `orders`");
if ($oCount) $totalOrders = (int) mysqli_fetch_assoc($oCount)['c'];

$mCount = mysqli_query($db, "SELECT COUNT(*) as c FROM `contact`");
if ($mCount) $totalMsgs = (int) mysqli_fetch_assoc($mCount)['c'];

// Fetch all categories with product counts
$categoriesWithCount = mysqli_query($db, 'SELECT c.`id`, c.`name`, COUNT(p.`id`) AS product_count 
    FROM `categories` c 
    LEFT JOIN `product` p ON p.`category_id` = c.`id` 
    GROUP BY c.`id`, c.`name` 
    ORDER BY c.`name` ASC');

// Fetch categories for dropdown
$categoriesDropdown = mysqli_query($db, 'SELECT `id`, `name` FROM `categories` ORDER BY `name` ASC');

// Fetch all products
$products = mysqli_query($db, 'SELECT p.`id`, p.`name`, p.`price`, p.`image`, c.`name` AS category_name
    FROM `product` p 
    LEFT JOIN `categories` c ON c.`id` = p.`category_id` 
    ORDER BY p.`id` DESC');

// Fetch all orders with items summary
$allOrders = mysqli_query($db, 'SELECT o.*, 
    (SELECT GROUP_CONCAT(CONCAT(oi.product_name, " × ", oi.quantity) SEPARATOR ", ") 
     FROM `order_items` oi WHERE oi.order_id = o.id) AS items_summary 
    FROM `orders` o 
    ORDER BY o.`id` DESC');

// Fetch recent messages
$messages = mysqli_query($db, 'SELECT `id`, `name`, `email`, `contact`, `msg` FROM `contact` ORDER BY `id` DESC');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard - Grocery Store Management</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css?v=2">
</head>
<body class="bg-light">

<?php include __DIR__ . '/header.php'; ?>

<main class="container py-4">
    <!-- Dashboard Top Bar -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h1 class="h3 fw-bold mb-1">Store Admin Dashboard</h1>
            <p class="text-muted small mb-0">Manage grocery inventory, categories, customer orders, and inquiries.</p>
        </div>
        <div class="d-flex gap-2 mt-2 mt-sm-0">
            <a href="index.php" class="btn btn-outline-success btn-sm">
                🌐 View Store Front
            </a>
            <a href="logout.php" class="btn btn-outline-danger btn-sm">
                Logout
            </a>
        </div>
    </div>

    <!-- Flash message display -->
    <?php show_flash(); ?>

    <!-- Error Alerts -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger" role="alert">
            <strong class="d-block mb-1">Action Error:</strong>
            <ul class="mb-0 ps-3 small">
                <?php foreach ($errors as $err): ?>
                    <li><?php echo e($err); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Overview Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card shadow-sm text-center">
                <span class="text-muted small text-uppercase fw-semibold">Grocery Items</span>
                <div class="stat-number"><?php echo $totalProds; ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card shadow-sm text-center">
                <span class="text-muted small text-uppercase fw-semibold">Categories</span>
                <div class="stat-number"><?php echo $totalCats; ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card shadow-sm text-center">
                <span class="text-muted small text-uppercase fw-semibold">Orders Placed</span>
                <div class="stat-number text-success"><?php echo $totalOrders; ?></div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card shadow-sm text-center">
                <span class="text-muted small text-uppercase fw-semibold">Inquiries</span>
                <div class="stat-number"><?php echo $totalMsgs; ?></div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs for Clean Dashboard Layout -->
    <ul class="nav nav-pills mb-4 bg-white p-2 rounded border shadow-sm">
        <li class="nav-item">
            <a class="nav-link fw-semibold <?php echo $activeTab === 'products' ? 'active bg-success' : 'text-dark'; ?>" 
               href="admin.php?tab=products">
                🛒 Products Inventory (<?php echo $totalProds; ?>)
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link fw-semibold <?php echo $activeTab === 'categories' ? 'active bg-success' : 'text-dark'; ?>" 
               href="admin.php?tab=categories">
                🏷️ Categories (<?php echo $totalCats; ?>)
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link fw-semibold <?php echo $activeTab === 'orders' ? 'active bg-success' : 'text-dark'; ?>" 
               href="admin.php?tab=orders">
                📦 Customer Orders (<?php echo $totalOrders; ?>)
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link fw-semibold <?php echo $activeTab === 'messages' ? 'active bg-success' : 'text-dark'; ?>" 
               href="admin.php?tab=messages">
                📬 Customer Inquiries (<?php echo $totalMsgs; ?>)
            </a>
        </li>
    </ul>

    <!-- ================= TAB 1: PRODUCTS INVENTORY ================= -->
    <?php if ($activeTab === 'products'): ?>
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 fw-bold mb-0">Product Inventory</h2>
                <button class="btn btn-shop btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#addProductCollapse" aria-expanded="false" aria-controls="addProductCollapse">
                    ➕ Add New Product
                </button>
            </div>

            <!-- Collapsible Add Product Form -->
            <div class="collapse mb-4" id="addProductCollapse">
                <div class="paper-card p-4 shadow-sm border-top border-4 border-success">
                    <h5 class="fw-bold mb-2">Insert New Grocery Item</h5>
                    <p class="text-muted small mb-3">Fill out the details below to add a new product to your online shop.</p>
                    
                    <form method="post" action="admin.php?tab=products" enctype="multipart/form-data">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="add_product">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="product_name">Product Name <span class="text-danger">*</span></label>
                                <input class="form-control" type="text" id="product_name" name="name" 
                                       placeholder="e.g. Fresh Kashmiri Apples 1kg" maxlength="100" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="product_price">Price (₹) <span class="text-danger">*</span></label>
                                <input class="form-control" type="number" id="product_price" name="price" 
                                       placeholder="e.g. 120.00" min="0.01" step="0.01" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="product_category">Department <span class="text-danger">*</span></label>
                                <select class="form-select" id="product_category" name="category_id" required>
                                    <option value="">Select category</option>
                                    <?php if ($categoriesDropdown): ?>
                                        <?php while ($cRow = mysqli_fetch_assoc($categoriesDropdown)): ?>
                                            <option value="<?php echo (int) $cRow['id']; ?>">
                                                <?php echo e($cRow['name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="product_image">Product Photo</label>
                                <input class="form-control" type="file" id="product_image" name="image" accept=".jpg,.jpeg,.png,.webp,.svg">
                                <div class="form-text small">Accepted formats: JPG, PNG, WEBP, or SVG.</div>
                            </div>
                            <div class="col-12 mt-3">
                                <button class="btn btn-success px-4" type="submit">Save Product</button>
                                <button class="btn btn-outline-secondary ms-2" type="button" data-bs-toggle="collapse" data-bs-target="#addProductCollapse">Cancel</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Products Table -->
        <div class="table-responsive paper-card shadow-sm bg-white">
            <table class="table align-middle table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width: 60px; min-width: 60px; max-width: 60px;">Photo</th>
                        <th>Product Name</th>
                        <th>Department</th>
                        <th>Price (₹)</th>
                        <th class="text-end" style="width: 180px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($products && mysqli_num_rows($products) > 0): ?>
                        <?php while ($prod = mysqli_fetch_assoc($products)): ?>
                            <tr>
                                <td style="width: 60px; min-width: 60px; max-width: 60px;">
                                    <?php $img = image_url($prod['image']); ?>
                                    <?php if ($img !== ''): ?>
                                        <img class="admin-thumbnail rounded" 
                                             src="<?php echo e($img); ?>" 
                                             alt="<?php echo e($prod['name']); ?>"
                                             width="48" height="48"
                                             style="width: 48px; height: 48px; min-width: 48px; max-width: 48px; max-height: 48px; object-fit: cover; border: 1px solid #dee2e6; display: block;">
                                    <?php else: ?>
                                        <div class="product-image-placeholder rounded small text-center" 
                                             style="width: 48px; height: 48px; min-width: 48px; max-width: 48px; line-height: 48px; font-size: 11px; border: 1px solid #dee2e6;">
                                            No img
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo e($prod['name']); ?></strong>
                                    <div class="small text-muted d-block d-sm-none">
                                        <?php echo e($prod['category_name'] ?: 'Uncategorised'); ?> | ₹<?php echo number_format((float) $prod['price'], 2); ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-secondary border">
                                        <?php echo e($prod['category_name'] ?: 'Uncategorised'); ?>
                                    </span>
                                </td>
                                <td class="fw-bold text-success">
                                    ₹<?php echo number_format((float) $prod['price'], 2); ?>
                                </td>
                                <td class="text-end text-nowrap">
                                    <a class="btn btn-sm btn-outline-primary me-1" href="update.php?id=<?php echo (int) $prod['id']; ?>">
                                        ✏️ Edit
                                    </a>
                                    <form method="post" action="delete.php" class="d-inline" 
                                          onsubmit="return confirm('Are you sure you want to delete this product: <?php echo addslashes($prod['name']); ?>?');">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="id" value="<?php echo (int) $prod['id']; ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">
                                            🗑️ Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                No products found in inventory. Click <strong>"+ Add New Product"</strong> above to add one.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- ================= TAB 2: CATEGORIES MANAGEMENT ================= -->
    <?php if ($activeTab === 'categories'): ?>
        <div class="row g-4">
            <div class="col-md-5">
                <section class="paper-card p-4 shadow-sm bg-white">
                    <h2 class="h5 fw-bold mb-2">Add New Department</h2>
                    <p class="text-muted small mb-3">Create a category to organise groceries (e.g. Beverages, Organic Snacks).</p>

                    <form method="post" action="admin.php?tab=categories">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="add_category">

                        <div class="mb-3">
                            <label class="form-label" for="category_name">Category Name <span class="text-danger">*</span></label>
                            <input class="form-control" type="text" id="category_name" name="category_name" 
                                   placeholder="e.g. Organic Snacks" maxlength="100" required>
                        </div>
                        <button class="btn btn-shop w-100" type="submit">Add Category</button>
                    </form>
                </section>
            </div>

            <div class="col-md-7">
                <section class="paper-card p-4 shadow-sm bg-white">
                    <h2 class="h5 fw-bold mb-3">Existing Grocery Categories</h2>
                    <div class="table-responsive">
                        <table class="table align-middle table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Category Name</th>
                                    <th>Linked Products</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($categoriesWithCount && mysqli_num_rows($categoriesWithCount) > 0): ?>
                                    <?php while ($cat = mysqli_fetch_assoc($categoriesWithCount)): ?>
                                        <tr>
                                            <td>
                                                <a href="category.php?id=<?php echo (int) $cat['id']; ?>" class="fw-bold text-decoration-none text-dark" target="_blank">
                                                    <?php echo e($cat['name']); ?> ↗
                                                </a>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-secondary border">
                                                    <?php echo (int) $cat['product_count']; ?> items
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <form method="post" action="admin.php?tab=categories" class="d-inline"
                                                      onsubmit="return confirm('Are you sure you want to delete the category: <?php echo addslashes($cat['name']); ?>?');">
                                                    <?php echo csrf_field(); ?>
                                                    <input type="hidden" name="action" value="delete_category">
                                                    <input type="hidden" name="category_id" value="<?php echo (int) $cat['id']; ?>">
                                                    <button class="btn btn-sm btn-outline-danger" type="submit">
                                                        🗑️ Delete
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">No categories created yet.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    <?php endif; ?>

    <!-- ================= TAB 3: CUSTOMER ORDERS ================= -->
    <?php if ($activeTab === 'orders'): ?>
        <section class="paper-card p-4 shadow-sm bg-white">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 fw-bold mb-0">Customer Orders Received</h2>
                <span class="text-muted small">Total: <?php echo $totalOrders; ?> orders</span>
            </div>

            <div class="table-responsive">
                <table class="table align-middle table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width: 70px;">Order #</th>
                            <th style="width: 170px;">Customer</th>
                            <th>Items Ordered</th>
                            <th style="width: 110px;">Total (₹)</th>
                            <th style="width: 140px;">Order Date</th>
                            <th style="width: 150px;">Status</th>
                            <th class="text-end" style="width: 80px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($allOrders && mysqli_num_rows($allOrders) > 0): ?>
                            <?php while ($ord = mysqli_fetch_assoc($allOrders)): ?>
                                <?php
                                $status = $ord['status'] ?? 'Processing';
                                $badgeClass = 'bg-warning text-dark';
                                if ($status === 'Dispatched') $badgeClass = 'bg-info text-dark';
                                if ($status === 'Delivered') $badgeClass = 'bg-success';
                                if ($status === 'Cancelled') $badgeClass = 'bg-danger';
                                ?>
                                <tr>
                                    <td>
                                        <a href="orders.php?order_id=<?php echo (int) $ord['id']; ?>" class="fw-bold text-decoration-none" target="_blank" title="View Order Receipt">
                                            #<?php echo (int) $ord['id']; ?> ↗
                                        </a>
                                    </td>
                                    <td>
                                        <strong class="d-block"><?php echo e($ord['customer_name']); ?></strong>
                                        <span class="small text-muted">📱 <?php echo e($ord['customer_phone']); ?></span>
                                        <div class="small text-secondary text-truncate" style="max-width: 160px;" title="<?php echo e($ord['delivery_address']); ?>">
                                            📍 <?php echo e($ord['delivery_address']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <?php echo e($ord['items_summary'] ?: 'Standard items'); ?>
                                        </div>
                                    </td>
                                    <td class="fw-bold text-success">
                                        ₹<?php echo number_format((float) $ord['total_amount'], 2); ?>
                                    </td>
                                    <td class="small text-muted">
                                        <?php echo date('d M, h:i A', strtotime($ord['created_at'])); ?>
                                    </td>
                                    <td>
                                        <form method="post" action="admin.php?tab=orders" class="d-flex align-items-center gap-1">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="action" value="update_order_status">
                                            <input type="hidden" name="order_id" value="<?php echo (int) $ord['id']; ?>">
                                            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                                <option value="Processing" <?php echo $status === 'Processing' ? 'selected' : ''; ?>>Processing</option>
                                                <option value="Dispatched" <?php echo $status === 'Dispatched' ? 'selected' : ''; ?>>Dispatched</option>
                                                <option value="Delivered" <?php echo $status === 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                                                <option value="Cancelled" <?php echo $status === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td class="text-end">
                                        <form method="post" action="admin.php?tab=orders" class="d-inline"
                                              onsubmit="return confirm('Delete record for order #<?php echo (int)$ord['id']; ?>?');">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete_order">
                                            <input type="hidden" name="order_id" value="<?php echo (int) $ord['id']; ?>">
                                            <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <span class="fs-2 d-block mb-2">📦</span>
                                    No customer orders placed yet. When a shopper orders from the cart, it appears here.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>

    <!-- ================= TAB 4: CUSTOMER MESSAGES ================= -->
    <?php if ($activeTab === 'messages'): ?>
        <section class="paper-card p-4 shadow-sm bg-white">
            <h2 class="h5 fw-bold mb-3">Customer Inquiries</h2>
            <div class="table-responsive">
                <table class="table align-middle table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th style="width: 180px;">Customer</th>
                            <th style="width: 200px;">Contact</th>
                            <th>Message</th>
                            <th class="text-end" style="width: 90px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($messages && mysqli_num_rows($messages) > 0): ?>
                            <?php $mIdx = 1; while ($msg = mysqli_fetch_assoc($messages)): ?>
                                <tr>
                                    <td class="text-muted small"><?php echo $mIdx++; ?></td>
                                    <td><strong><?php echo e($msg['name']); ?></strong></td>
                                    <td class="small">
                                        <div><a href="mailto:<?php echo e($msg['email']); ?>" class="text-decoration-none"><?php echo e($msg['email']); ?></a></div>
                                        <div><?php echo e($msg['contact']); ?></div>
                                    </td>
                                    <td>
                                        <div class="p-2 bg-light rounded small border">
                                            <?php echo nl2br(e($msg['msg'])); ?>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <form method="post" action="admin.php?tab=messages" class="d-inline" 
                                              onsubmit="return confirm('Delete this message?');">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="action" value="delete_message">
                                            <input type="hidden" name="message_id" value="<?php echo (int) $msg['id']; ?>">
                                            <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-5">
                                    <span class="fs-2 d-block mb-2">📭</span>
                                    No customer inquiries received yet.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/footer.php'; ?>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
