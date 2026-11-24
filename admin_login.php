<?php
// Practical 5: Admin Login Page using PHP and Bootstrap
require_once __DIR__ . '/functions.php';

// Check if any admin exists in the database
$result = mysqli_query($db, "SELECT COUNT(*) AS `total` FROM `users` WHERE `role` = 'admin'");
$adminExists = $result && (int) mysqli_fetch_assoc($result)['total'] > 0;

$error = '';
$name = '';
$email = '';

// Handle Admin Login or Initial Setup
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (!verify_csrf()) {
        $error = 'Session expired. Please try again.';
    } elseif (!$adminExists) {
        // Initial setup for the first admin
        $name = trim($_POST['name'] ?? '');
        if (empty($name) || empty($email) || empty($password)) {
            $error = 'Please fill in all fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($db, "INSERT INTO `users` (`name`, `email`, `contact`, `password`, `role`) VALUES (?, ?, '', ?, 'admin')");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'sss', $name, $email, $hash);
                if (mysqli_stmt_execute($stmt)) {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = mysqli_insert_id($db);
                    $_SESSION['user_name'] = $name;
                    $_SESSION['role'] = 'admin';
                    set_flash('Admin account created successfully! Welcome to the Admin Panel.', 'success');
                    redirect('admin.php');
                } else {
                    $error = 'Email is already taken by another account.';
                }
            } else {
                $error = 'Database error creating admin account.';
            }
        }
    } else {
        // Standard Admin Login
        if (empty($email) || empty($password)) {
            $error = 'Please enter both admin email and password.';
        } else {
            $stmt = mysqli_prepare($db, "SELECT `id`, `name`, `password` FROM `users` WHERE `email` = ? AND `role` = 'admin'");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 's', $email);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                $admin = mysqli_fetch_assoc($res);

                if ($admin && password_verify($password, $admin['password'])) {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = (int) $admin['id'];
                    $_SESSION['user_name'] = $admin['name'];
                    $_SESSION['role'] = 'admin';
                    set_flash('Welcome to Admin Panel, ' . $admin['name'] . '!', 'success');
                    redirect('admin.php');
                } else {
                    $error = 'Invalid admin credentials.';
                }
            } else {
                $error = 'Database authentication error.';
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
    <title>Administrator Login - Grocery Basket</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light">

<?php include __DIR__ . '/header.php'; ?>

<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <section class="paper-card p-4 p-md-5 shadow-sm border-top border-4 border-success">
                <div class="text-center mb-4">
                    <span class="fs-1">🔐</span>
                    <h1 class="h3 fw-bold mt-2">
                        <?php echo $adminExists ? 'Admin Portal Login' : 'Setup First Admin'; ?>
                    </h1>
                    <p class="text-muted small">
                        <?php echo $adminExists ? 'Authorized store management access only' : 'Create the store master administrator account'; ?>
                    </p>
                </div>

                <?php show_flash(); ?>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger py-2 small" role="alert">
                        <?php echo e($error); ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="admin_login.php">
                    <?php echo csrf_field(); ?>

                    <?php if (!$adminExists): ?>
                        <div class="mb-3">
                            <label class="form-label" for="name">Admin Name</label>
                            <input class="form-control" type="text" id="name" name="name" 
                                   value="<?php echo e($name); ?>" placeholder="e.g. Store Manager" required>
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label" for="email">Admin Email</label>
                        <input class="form-control" type="email" id="email" name="email" 
                               value="<?php echo e($email); ?>" placeholder="admin@grocery.com" required autofocus>
                    </div>

                    <div class="mb-4">
                        <label class="form-label" for="password">Password</label>
                        <input class="form-control" type="password" id="password" name="password" 
                               placeholder="Enter admin password" required>
                    </div>

                    <button class="btn btn-success w-100 py-2" type="submit">
                        <?php echo $adminExists ? 'Login to Admin Panel' : 'Create Admin Account'; ?>
                    </button>
                </form>

                <!-- Demo helper credentials -->
                <div class="alert alert-light border mt-4 p-3 small text-muted">
                    <strong class="d-block text-dark mb-1">Pre-configured Admin Credentials:</strong>
                    Email: <code>admin@grocery.com</code><br>
                    Password: <code>admin123</code>
                </div>

                <div class="text-center mt-3 pt-3 border-top small">
                    <a href="login.php" class="text-decoration-none">&larr; Back to Customer Login</a>
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
