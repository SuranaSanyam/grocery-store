<?php
// Practical 2 & 4: Registration Page with Server-Side Validations & Database Connectivity
require_once __DIR__ . '/functions.php';

$errors = [];
$name = '';
$email = '';
$contact = '';

// Handle Registration Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $contact = trim($_POST['contact'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // 1. Verify CSRF Token
    if (!verify_csrf()) {
        $errors[] = 'Session expired. Please try submitting the form again.';
    }

    // 2. Server-side validation: Name
    if (empty($name)) {
        $errors[] = 'Please enter your full name.';
    } elseif (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
        $errors[] = 'Name must be between 2 and 100 characters.';
    }

    // 3. Server-side validation: Email
    if (empty($email)) {
        $errors[] = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
        $errors[] = 'Please enter a valid email address (e.g., yourname@example.com).';
    } else {
        // Check if email is already registered
        $stmtEmail = mysqli_prepare($db, 'SELECT `id` FROM `users` WHERE `email` = ?');
        if ($stmtEmail) {
            mysqli_stmt_bind_param($stmtEmail, 's', $email);
            mysqli_stmt_execute($stmtEmail);
            $res = mysqli_stmt_get_result($stmtEmail);
            if (mysqli_num_rows($res) > 0) {
                $errors[] = 'An account with this email address already exists.';
            }
        }
    }

    // 4. Server-side validation: Contact Number
    if (empty($contact)) {
        $errors[] = 'Please enter your contact number.';
    } elseif (!preg_match('/^[0-9+\s-]{7,15}$/', $contact)) {
        $errors[] = 'Please enter a valid phone number (digits only, 7 to 15 digits).';
    }

    // 5. Server-side validation: Password
    if (empty($password)) {
        $errors[] = 'Please choose a password.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    }

    // 6. Server-side validation: Confirm Password matching
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match. Please re-enter your password.';
    }

    // If no validation errors, proceed with user insertion
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $role = 'customer';

        $insertStmt = mysqli_prepare($db, 'INSERT INTO `users` (`name`, `email`, `contact`, `password`, `role`) VALUES (?, ?, ?, ?, ?)');
        if ($insertStmt) {
            mysqli_stmt_bind_param($insertStmt, 'sssss', $name, $email, $contact, $hashedPassword, $role);
            if (mysqli_stmt_execute($insertStmt)) {
                set_flash('Registration successful! You can now log in with your credentials.', 'success');
                redirect('login.php');
            } else {
                $errors[] = 'Registration failed due to a database error. Please try again.';
            }
        } else {
            $errors[] = 'Database query preparation failed.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Customer Registration - Grocery Basket</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include __DIR__ . '/header.php'; ?>

<main class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">
            <section class="paper-card p-4 p-md-5 shadow-sm">
                <div class="text-center mb-4">
                    <span class="fs-1">📝</span>
                    <h1 class="h3 fw-bold mt-2">Create Customer Account</h1>
                    <p class="text-muted small">Register to order fresh groceries, track deliveries, and save favourites.</p>
                </div>

                <!-- Display Server-side Validation Errors -->
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger" role="alert">
                        <strong class="d-block mb-1">Please correct the following errors:</strong>
                        <ul class="mb-0 ps-3 small">
                            <?php foreach ($errors as $err): ?>
                                <li><?php echo e($err); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Registration Form -->
                <form method="post" action="register.php" novalidate>
                    <?php echo csrf_field(); ?>

                    <div class="mb-3">
                        <label class="form-label" for="name">Full Name <span class="text-danger">*</span></label>
                        <input class="form-control" type="text" id="name" name="name" 
                               value="<?php echo e($name); ?>" placeholder="e.g. Rahul Sharma" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="email">Email Address <span class="text-danger">*</span></label>
                        <input class="form-control" type="email" id="email" name="email" 
                               value="<?php echo e($email); ?>" placeholder="name@example.com" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="contact">Contact Number <span class="text-danger">*</span></label>
                        <input class="form-control" type="tel" id="contact" name="contact" 
                               value="<?php echo e($contact); ?>" placeholder="e.g. 9876543210" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="password">Password <span class="text-danger">*</span></label>
                            <input class="form-control" type="password" id="password" name="password" 
                                   placeholder="Minimum 6 characters" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="confirm_password">Confirm Password <span class="text-danger">*</span></label>
                            <input class="form-control" type="password" id="confirm_password" name="confirm_password" 
                                   placeholder="Repeat password" required>
                        </div>
                    </div>

                    <button class="btn btn-shop w-100 py-2 mt-2" type="submit">Register Now</button>
                </form>

                <div class="text-center mt-4 pt-3 border-top">
                    <p class="small text-muted mb-0">Already registered? <a href="login.php" class="fw-semibold">Login here</a></p>
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
