<?php
// Customer Login Page using PHP and Bootstrap
require_once __DIR__ . '/functions.php';

$error = '';
$email = '';

// Handle Login Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    // Verify CSRF
    if (!verify_csrf()) {
        $error = 'Session expired. Please try again.';
    } elseif (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Query user from database
        $stmt = mysqli_prepare($db, 'SELECT `id`, `name`, `password`, `role` FROM `users` WHERE `email` = ? AND `role` = \'customer\'');
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($res);

            // Check password match
            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['role'] = 'customer';

                set_flash('Welcome back, ' . $user['name'] . '!', 'success');
                redirect('index.php');
            } else {
                $error = 'Invalid email address or password.';
            }
        } else {
            $error = 'Login failed due to a database error.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Customer Login - Grocery Basket</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include __DIR__ . '/header.php'; ?>

<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <section class="paper-card p-4 p-md-5 shadow-sm">
                <div class="text-center mb-4">
                    <span class="fs-1">🛍️</span>
                    <h1 class="h3 fw-bold mt-2">Customer Login</h1>
                    <p class="text-muted small">Sign in to your Grocery Basket account</p>
                </div>

                <!-- Flash messages (e.g. from registration) -->
                <?php show_flash(); ?>

                <!-- Error Alert -->
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger py-2 small" role="alert">
                        <?php echo e($error); ?>
                    </div>
                <?php endif; ?>

                <!-- Login Form -->
                <form method="post" action="login.php">
                    <?php echo csrf_field(); ?>

                    <div class="mb-3">
                        <label class="form-label" for="email">Email Address</label>
                        <input class="form-control" type="email" id="email" name="email" 
                               value="<?php echo e($email); ?>" placeholder="name@example.com" required autofocus>
                    </div>

                    <div class="mb-4">
                        <label class="form-label" for="password">Password</label>
                        <input class="form-control" type="password" id="password" name="password" 
                               placeholder="Enter your password" required>
                    </div>

                    <button class="btn btn-shop w-100 py-2" type="submit">Log In</button>
                </form>

                <!-- Demo helper box -->
                <div class="alert alert-light border mt-4 p-3 small text-muted">
                    <strong class="d-block text-dark mb-1">Demo Customer Credentials:</strong>
                    Email: <code>user@grocery.com</code><br>
                    Password: <code>user123</code>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top small">
                    <a href="register.php" class="text-decoration-none">Create an account</a>
                    <a href="admin_login.php" class="text-secondary text-decoration-none">Admin Login &rarr;</a>
                </div>
            </section>
        </div>
    </div>
</main>

<?php include __DIR__ . '/footer.php'; ?>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
