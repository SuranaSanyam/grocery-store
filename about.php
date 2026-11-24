<?php
// About Us Page for Grocery Basket Store
require_once __DIR__ . '/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>About Us - Grocery Basket</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include __DIR__ . '/header.php'; ?>

<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <section class="paper-card p-4 p-md-5 shadow-sm bg-white">
                <span class="badge bg-success-subtle text-success border border-success-subtle mb-2">Our Story</span>
                <h1 class="h2 fw-bold mb-3">About Grocery Basket</h1>
                <p class="lead text-muted">
                    Your friendly neighbourhood online grocery destination, committed to bringing the freshest farm produce, wholesome dairy, freshly baked goods, and everyday kitchen essentials directly to your doorstep.
                </p>

                <hr class="my-4">

                <div class="row g-4 my-2">
                    <div class="col-md-6">
                        <div class="p-3 border rounded h-100 bg-light">
                            <h5 class="fw-bold text-success mb-2">🌿 Farm-to-Table Freshness</h5>
                            <p class="small text-muted mb-0">
                                We partner directly with regional farmers and certified dairy producers to deliver crisp, chemical-free greens, seasonal fruits, and pure milk every single morning.
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 border rounded h-100 bg-light">
                            <h5 class="fw-bold text-success mb-2">⚡ Hassle-Free Neighbourhood Delivery</h5>
                            <p class="small text-muted mb-0">
                                Skip the crowded supermarket queues and parking struggles. Order your weekly basket in minutes and receive it conveniently at your home.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-3">
                    <h4 class="fw-bold mb-3">Our Core Values</h4>
                    <ul class="text-secondary small lh-lg">
                        <li><strong>Honest &amp; Fair Pricing:</strong> Transparent, competitive market rates on all grocery goods without hidden markups.</li>
                        <li><strong>Rigorous Quality Inspection:</strong> Every batch of produce is sorted and checked by hand before dispatch.</li>
                        <li><strong>Sustainable Community Sourcing:</strong> Supporting local growers and reducing food miles.</li>
                    </ul>
                </div>

                <div class="mt-4 pt-3 border-top d-flex flex-wrap gap-2">
                    <a class="btn btn-shop px-4" href="category.php">Explore Our Groceries</a>
                    <a class="btn btn-outline-secondary" href="contact.php">Contact Our Team</a>
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
