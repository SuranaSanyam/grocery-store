<?php
require_once __DIR__ . '/functions.php';
$currentPage = basename($_SERVER['PHP_SELF']);
$cartCount = get_cart_count();
?>
<!-- Navigation Header (Static, Non-overlapping) -->
<nav class="navbar navbar-expand-lg shop-nav shadow-sm">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
            <span class="fs-4">🥬</span>
            <span class="fw-bold">Grocery Basket</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#shopNavigation" aria-controls="shopNavigation" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="shopNavigation">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-1">
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'index.php' ? 'active fw-bold text-success' : ''; ?>" href="index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'category.php' ? 'active fw-bold text-success' : ''; ?>" href="category.php">Categories</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'cart.php' ? 'active fw-bold text-success' : ''; ?>" href="cart.php">
                        🛒 Cart
                        <?php if ($cartCount > 0): ?>
                            <span class="badge rounded-pill bg-success ms-1"><?php echo $cartCount; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'orders.php' ? 'active fw-bold text-success' : ''; ?>" href="orders.php">Orders</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'about.php' ? 'active fw-bold text-success' : ''; ?>" href="about.php">About</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage === 'contact.php' ? 'active fw-bold text-success' : ''; ?>" href="contact.php">Contact</a>
                </li>

                <!-- User Session Controls -->
                <?php if (!empty($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'admin'): ?>
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-sm btn-outline-primary <?php echo $currentPage === 'admin.php' ? 'active' : ''; ?>" href="admin.php">
                            Admin Panel
                        </a>
                    </li>
                    <li class="nav-item ms-lg-1">
                        <a class="btn btn-sm btn-outline-danger" href="logout.php">Logout</a>
                    </li>
                <?php elseif (!empty($_SESSION['user_id'])): ?>
                    <li class="nav-item ms-lg-2">
                        <span class="nav-link text-success fw-semibold"><?php echo e($_SESSION['user_name'] ?? ''); ?></span>
                    </li>
                    <li class="nav-item ms-lg-1">
                        <a class="btn btn-sm btn-outline-danger" href="logout.php">Logout</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item ms-lg-2">
                        <a class="nav-link <?php echo $currentPage === 'login.php' ? 'active fw-bold' : ''; ?>" href="login.php">Login</a>
                    </li>
                    <li class="nav-item ms-lg-1">
                        <a class="btn btn-sm btn-shop px-3" href="register.php">Register</a>
                    </li>
                    <li class="nav-item ms-lg-1">
                        <a class="nav-link small text-secondary" href="admin_login.php" title="Admin Portal">Admin</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
