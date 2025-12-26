<?php
/**
 * Order Tracking Form Page
 */
include VIEWS_PATH . '/layouts/header.php';
?>

<section class="py-5 bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-4">
                            <i class="bi bi-box-seam display-4 text-primary"></i>
                            <h2 class="mt-3">Track Your Order</h2>
                            <p class="text-muted">Enter your order number and email to track your order status.</p>
                        </div>

                        <?php if (!empty($_SESSION['flash']['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($_SESSION['flash']['error']) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['flash']['error']); endif; ?>

                        <?php if (!empty($_SESSION['flash']['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($_SESSION['flash']['success']) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['flash']['success']); endif; ?>

                        <form action="<?= url('/order/track') ?>" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                            
                            <div class="mb-3">
                                <label for="order_number" class="form-label">Order Number *</label>
                                <input type="text" class="form-control form-control-lg" id="order_number" 
                                       name="order_number" placeholder="e.g., CC2025123456"
                                       value="<?= htmlspecialchars($orderNumber ?? '') ?>" required>
                                <div class="form-text">Your order number starts with "CC" followed by numbers.</div>
                            </div>

                            <div class="mb-4">
                                <label for="email" class="form-label">Email Address *</label>
                                <input type="email" class="form-control form-control-lg" id="email" 
                                       name="email" placeholder="Enter your email"
                                       value="<?= htmlspecialchars($email ?? '') ?>" required>
                                <div class="form-text">The email address used when placing the order.</div>
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg w-100">
                                <i class="bi bi-search me-2"></i>Track Order
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include VIEWS_PATH . '/layouts/footer.php'; ?>
