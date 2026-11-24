<?php
// Practical 6: Update an Existing Product using PHP and MySQL
require_once __DIR__ . '/functions.php';

// Only administrators can edit products
require_role('admin');

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    set_flash('Invalid product ID.', 'danger');
    redirect('admin.php');
}

// Fetch current product details from database
$stmt = mysqli_prepare($db, 'SELECT `id`, `name`, `price`, `image`, `category_id` FROM `product` WHERE `id` = ?');
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$product = mysqli_fetch_assoc($res);

if (!$product) {
    set_flash('Product not found in the database.', 'danger');
    redirect('admin.php');
}

$errors = [];

// Handle Product Update Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $categoryId = filter_var($_POST['category_id'] ?? null, FILTER_VALIDATE_INT);

    if (!verify_csrf()) {
        $errors[] = 'Session expired. Please try submitting again.';
    }
    if (empty($name) || mb_strlen($name) > 100) {
        $errors[] = 'Product name must be between 1 and 100 characters.';
    }
    if (!is_numeric($price) || (float) $price <= 0 || (float) $price > 99999999.99) {
        $errors[] = 'Please enter a valid price greater than zero.';
    }
    if (!$categoryId) {
        $errors[] = 'Please choose a category.';
    }

    // Process new image upload if provided
    $newImage = null;
    if (empty($errors)) {
        $imgError = null;
        $newImage = store_uploaded_image('image', $imgError);
        if ($imgError) {
            $errors[] = $imgError;
        }
    }

    if (empty($errors)) {
        // Validate selected category exists
        $catCheck = mysqli_prepare($db, 'SELECT `id` FROM `categories` WHERE `id` = ?');
        mysqli_stmt_bind_param($catCheck, 'i', $categoryId);
        mysqli_stmt_execute($catCheck);
        if (mysqli_num_rows(mysqli_stmt_get_result($catCheck)) === 0) {
            $errors[] = 'Selected category does not exist.';
        } else {
            // Keep old image if no new image was uploaded
            $imageToSave = $newImage ?? $product['image'];
            $priceValue = (float) $price;

            $updateStmt = mysqli_prepare($db, 'UPDATE `product` SET `name` = ?, `price` = ?, `image` = ?, `category_id` = ? WHERE `id` = ?');
            if ($updateStmt) {
                mysqli_stmt_bind_param($updateStmt, 'sdsii', $name, $priceValue, $imageToSave, $categoryId, $id);
                if (mysqli_stmt_execute($updateStmt)) {
                    set_flash('Product "' . $name . '" updated successfully!', 'success');
                    redirect('admin.php?tab=products');
                } else {
                    $errors[] = 'Failed to update product due to a database error.';
                }
            } else {
                $errors[] = 'Database error preparing update query.';
            }
        }
    }

    // Update in-memory values for form retention
    $product['name'] = $name;
    $product['price'] = $price;
    $product['category_id'] = $categoryId;
    if ($newImage) {
        $product['image'] = $newImage;
    }
}

// Fetch categories for dropdown
$categories = mysqli_query($db, 'SELECT `id`, `name` FROM `categories` ORDER BY `name`');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Product - Admin Panel</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css?v=2">
</head>
<body class="bg-light">

<?php include __DIR__ . '/header.php'; ?>

<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-7">
            <section class="paper-card p-4 p-md-5 shadow-sm">
                <div class="d-flex align-items-center mb-3">
                    <span class="fs-2 me-2">✏️</span>
                    <div>
                        <h1 class="h3 fw-bold mb-0">Edit Grocery Product</h1>
                        <p class="text-muted small mb-0">Modify product details, category, price, or photo.</p>
                    </div>
                </div>

                <!-- Display Errors -->
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger" role="alert">
                        <ul class="mb-0 ps-3 small">
                            <?php foreach ($errors as $err): ?>
                                <li><?php echo e($err); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" action="update.php?id=<?php echo (int) $id; ?>" enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>

                    <div class="mb-3">
                        <label class="form-label" for="name">Product Name <span class="text-danger">*</span></label>
                        <input class="form-control" type="text" id="name" name="name" 
                               value="<?php echo e($product['name']); ?>" maxlength="100" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label" for="price">Price (₹) <span class="text-danger">*</span></label>
                            <input class="form-control" type="number" id="price" name="price" 
                                   value="<?php echo e($product['price']); ?>" min="0.01" step="0.01" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="category_id">Category <span class="text-danger">*</span></label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">Select category</option>
                                <?php if ($categories): ?>
                                    <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
                                        <option value="<?php echo (int) $cat['id']; ?>" 
                                            <?php echo (int) $cat['id'] === (int) $product['category_id'] ? 'selected' : ''; ?>>
                                            <?php echo e($cat['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Current Image Preview & Replacement -->
                    <div class="mb-4">
                        <label class="form-label d-block">Current Product Photo</label>
                        <?php $currentImg = image_url($product['image']); ?>
                        <?php if ($currentImg !== ''): ?>
                            <div class="d-flex align-items-center gap-3 p-2 border rounded bg-white mb-2">
                                <img src="<?php echo e($currentImg); ?>" alt="<?php echo e($product['name']); ?>" 
                                     width="70" height="70"
                                     style="width: 70px; height: 70px; min-width: 70px; max-width: 70px; max-height: 70px; object-fit: cover; border-radius: 6px;">
                                <span class="small text-muted"><?php echo e($product['image']); ?></span>
                            </div>
                        <?php else: ?>
                            <p class="small text-muted mb-2">No photo currently attached to this product.</p>
                        <?php endif; ?>

                        <label class="form-label mt-2" for="image">Replace Photo (Optional)</label>
                        <input class="form-control" type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.webp,.svg">
                        <div class="form-text small">Leave empty to keep existing photo. Accepts JPG, PNG, WEBP, or SVG.</div>
                    </div>

                    <div class="d-flex gap-2">
                        <button class="btn btn-shop px-4" type="submit">Save Changes</button>
                        <a class="btn btn-outline-secondary" href="admin.php?tab=products">Cancel</a>
                    </div>
                </form>
            </section>
        </div>
    </div>
</main>

<?php include __DIR__ . '/footer.php'; ?>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
