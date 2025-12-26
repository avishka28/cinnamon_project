<?php
/**
 * Customer Dashboard - Address Book
 */
include VIEWS_PATH . '/layouts/header.php';
?>

<section class="py-5 bg-light">
    <div class="container">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3 mb-4">
                <?php include __DIR__ . '/_sidebar.php'; ?>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-9">
                <?php if (!empty($_SESSION['flash']['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['flash']['success']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['flash']['success']); endif; ?>
                
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0"><?= t('dashboard.addresses') ?? 'My Addresses' ?></h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($addresses)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-geo-alt display-4 text-muted"></i>
                            <p class="text-muted mt-3"><?= t('dashboard.no_addresses') ?? 'No saved addresses yet' ?></p>
                            <p class="text-muted small">
                                <?= t('dashboard.addresses_from_orders') ?? 'Your shipping addresses from previous orders will appear here.' ?>
                            </p>
                            <a href="<?= url('/products') ?>" class="btn btn-primary">
                                <i class="bi bi-cart me-2"></i><?= t('dashboard.start_shopping') ?? 'Start Shopping' ?>
                            </a>
                        </div>
                        <?php else: ?>
                        <p class="text-muted mb-4">
                            <?= t('dashboard.addresses_info') ?? 'These are shipping addresses from your previous orders.' ?>
                        </p>
                        <div class="row g-4">
                            <?php foreach ($addresses as $index => $address): ?>
                            <div class="col-md-6">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <?php if ($index === 0): ?>
                                        <span class="badge bg-primary mb-2"><?= t('dashboard.most_recent') ?? 'Most Recent' ?></span>
                                        <?php endif; ?>
                                        <h6 class="card-title">
                                            <i class="bi bi-geo-alt me-1"></i>
                                            <?= htmlspecialchars(($address['first_name'] ?? '') . ' ' . ($address['last_name'] ?? '')) ?>
                                        </h6>
                                        <p class="card-text text-muted small mb-2">
                                            <?= nl2br(htmlspecialchars($address['address'] ?? '')) ?>
                                        </p>
                                        <?php if (!empty($address['phone'])): ?>
                                        <p class="card-text text-muted small mb-0">
                                            <i class="bi bi-telephone me-1"></i>
                                            <?= htmlspecialchars($address['phone']) ?>
                                        </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include VIEWS_PATH . '/layouts/footer.php'; ?>
