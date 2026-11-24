<?php
// Practical 6: Delete an Existing Product using PHP and MySQL
require_once __DIR__ . '/functions.php';

// Only administrators can delete products
require_role('admin');

// Enforce POST method and CSRF protection
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
    set_flash('Invalid request or session expired. Please try deleting again.', 'warning');
    redirect('admin.php?tab=products');
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    set_flash('Invalid product ID.', 'danger');
    redirect('admin.php?tab=products');
}

// Fetch product name for friendly feedback
$stmtName = mysqli_prepare($db, 'SELECT `name` FROM `product` WHERE `id` = ?');
$prodName = 'Product';
if ($stmtName) {
    mysqli_stmt_bind_param($stmtName, 'i', $id);
    mysqli_stmt_execute($stmtName);
    $res = mysqli_stmt_get_result($stmtName);
    if ($row = mysqli_fetch_assoc($res)) {
        $prodName = $row['name'];
    }
}

// Prepare and execute deletion
$stmt = mysqli_prepare($db, 'DELETE FROM `product` WHERE `id` = ?');
if ($stmt) {
    mysqli_stmt_bind_param($stmt, 'i', $id);
    if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
        set_flash('Product "' . $prodName . '" was successfully deleted.', 'success');
    } else {
        set_flash('The product could not be deleted (it may have already been removed).', 'warning');
    }
} else {
    set_flash('Database error while attempting to delete product.', 'danger');
}

redirect('admin.php?tab=products');
