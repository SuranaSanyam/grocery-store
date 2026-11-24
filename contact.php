<?php
// Practical 2: Contact Page with Bootstrap and PHP Database Processing
require_once __DIR__ . '/functions.php';

$errors = [];
$name = '';
$email = '';
$contact = '';
$msg = '';

// Pre-fill message if product parameter is passed from product page
$productQuery = filter_input(INPUT_GET, 'product', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
if ($productQuery) {
    $msg = "Hello, I would like to inquire about ordering: " . $productQuery;
}

// Handle Direct Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $contact = trim($_POST['contact'] ?? $_POST['mobile'] ?? '');
    $msg = trim($_POST['msg'] ?? '');

    if (!verify_csrf()) {
        $errors[] = 'Session expired. Please try sending your message again.';
    }
    if (empty($name) || mb_strlen($name) > 255) {
        $errors[] = 'Please enter your name.';
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (empty($contact) || strlen($contact) > 30) {
        $errors[] = 'Please enter your contact phone number.';
    }
    if (empty($msg) || mb_strlen($msg) > 500) {
        $errors[] = 'Please write a message (up to 500 characters).';
    }

    if (empty($errors)) {
        $stmt = mysqli_prepare($db, 'INSERT INTO `contact` (`name`, `email`, `contact`, `msg`) VALUES (?, ?, ?, ?)');
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'ssss', $name, $email, $contact, $msg);
            if (mysqli_stmt_execute($stmt)) {
                set_flash('Thank you! Your message has been sent successfully. Our store team will get in touch with you shortly.', 'success');
                redirect('contact.php');
            } else {
                $errors[] = 'Failed to save your message due to a database error.';
            }
        } else {
            $errors[] = 'Database error preparing contact statement.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contact Us - Grocery Basket</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include __DIR__ . '/header.php'; ?>

<main class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Header title -->
            <div class="text-center mb-4">
                <span class="fs-1">📞</span>
                <h1 class="h2 fw-bold mt-2">Get In Touch</h1>
                <p class="text-muted small">Have questions about grocery delivery, bulk orders, or daily arrivals? We would love to help.</p>
            </div>

            <!-- Flash Message -->
            <?php show_flash(); ?>

            <!-- Error Alerts -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0 ps-3 small">
                        <?php foreach ($errors as $err): ?>
                            <li><?php echo e($err); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="row g-4 mt-1">
                <!-- Store Info Column -->
                <div class="col-md-5">
                    <div class="paper-card p-4 h-100 shadow-sm bg-white">
                        <h2 class="h5 fw-bold text-success mb-3">Grocery Basket Store</h2>
                        <p class="text-muted small mb-4">Your trusted neighbourhood grocer for farm-fresh vegetables, dairy, and kitchen provisions.</p>

                        <div class="d-flex align-items-start gap-3 mb-3">
                            <span class="fs-4">📍</span>
                            <div>
                                <strong class="d-block small">Store Address</strong>
                                <span class="small text-muted">124 Green Market Road, Opp. City Park, Sector 4</span>
                            </div>
                        </div>

                        <div class="d-flex align-items-start gap-3 mb-3">
                            <span class="fs-4">📱</span>
                            <div>
                                <strong class="d-block small">Customer Helpline</strong>
                                <span class="small text-muted">+91 98765 43210 / +91 98765 43211</span>
                            </div>
                        </div>

                        <div class="d-flex align-items-start gap-3 mb-3">
                            <span class="fs-4">✉️</span>
                            <div>
                                <strong class="d-block small">Email Address</strong>
                                <span class="small text-muted">support@grocerybasket.local</span>
                            </div>
                        </div>

                        <div class="d-flex align-items-start gap-3">
                            <span class="fs-4">⏰</span>
                            <div>
                                <strong class="d-block small">Working Hours</strong>
                                <span class="small text-muted">Mon - Sat: 8:00 AM - 9:00 PM<br>Sunday: 9:00 AM - 6:00 PM</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact Form Column -->
                <div class="col-md-7">
                    <div class="paper-card p-4 p-md-5 h-100 shadow-sm bg-white">
                        <h2 class="h5 fw-bold mb-3">Send Us a Message</h2>
                        <form method="post" action="contact.php">
                            <?php echo csrf_field(); ?>

                            <div class="mb-3">
                                <label class="form-label" for="name">Your Name <span class="text-danger">*</span></label>
                                <input class="form-control" type="text" id="name" name="name" 
                                       value="<?php echo e($name); ?>" placeholder="Enter your full name" required>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="email">Email Address <span class="text-danger">*</span></label>
                                    <input class="form-control" type="email" id="email" name="email" 
                                           value="<?php echo e($email); ?>" placeholder="name@example.com" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="contact">Contact Number <span class="text-danger">*</span></label>
                                    <input class="form-control" type="tel" id="contact" name="contact" 
                                           value="<?php echo e($contact); ?>" placeholder="e.g. 9876543210" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label" for="msg">Message / Inquiry <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="msg" name="msg" rows="4" 
                                          placeholder="Type your message, inquiry, or delivery request here..." required><?php echo e($msg); ?></textarea>
                            </div>

                            <button class="btn btn-shop px-4 py-2" type="submit">Submit Message</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/footer.php'; ?>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
