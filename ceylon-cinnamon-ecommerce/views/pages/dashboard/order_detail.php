<?php
/**
 * Customer Dashboard - Order Detail
 * Requirements: 5.6 (detailed order information and invoice)
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
                <!-- Back Button -->
                <a href="<?= url('/dashboard/orders') ?>" class="btn btn-outline-secondary mb-3">
                    <i class="bi bi-arrow-left me-2"></i><?= t('action.back') ?? 'Back to Orders' ?>
                </a>
                
                <!-- Order Header -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h4 class="mb-1"><?= t('dashboard.order') ?? 'Order' ?> #<?= htmlspecialchars($order['order_number']) ?></h4>
                                <p class="text-muted mb-0">
                                    <?= t('dashboard.placed_on') ?? 'Placed on' ?> <?= date('F d, Y \a\t g:i A', strtotime($order['created_at'])) ?>
                                </p>
                            </div>
                            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                                <?php
                                $statusClasses = [
                                    'pending' => 'bg-warning',
                                    'processing' => 'bg-info',
                                    'shipped' => 'bg-primary',
                                    'delivered' => 'bg-success',
                                    'cancelled' => 'bg-danger',
                                    'returned' => 'bg-secondary'
                                ];
                                $statusClass = $statusClasses[$order['order_status']] ?? 'bg-secondary';
                                ?>
                                <span class="badge <?= $statusClass ?> fs-6 px-3 py-2">
                                    <?= ucfirst($order['order_status']) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row g-4">
                    <!-- Order Items -->
                    <div class="col-lg-8">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white py-3">
                                <h5 class="mb-0"><?= t('dashboard.order_items') ?? 'Order Items' ?></h5>
                            </div>
                            <div class="card-body p-0">
                                <?php foreach ($orderItems as $item): ?>
                                <div class="d-flex p-3 border-bottom">
                                    <div class="flex-shrink-0">
                                        <img src="<?= url('/uploads/products/' . ($item['product_slug'] ?? 'placeholder') . '.jpg') ?>" 
                                             alt="<?= htmlspecialchars($item['product_name'] ?? 'Product') ?>"
                                             class="rounded"
                                             style="width: 80px; height: 80px; object-fit: cover;"
                                             onerror="this.src='https://via.placeholder.com/80x80/FFF8DC/8B4513?text=Product'">
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h6 class="mb-1"><?= htmlspecialchars($item['product_name'] ?? 'Product') ?></h6>
                                        <p class="text-muted small mb-1">
                                            <?= t('dashboard.qty') ?? 'Qty' ?>: <?= $item['quantity'] ?>
                                        </p>
                                        <p class="text-muted small mb-0">
                                            $<?= number_format($item['price'], 2) ?> each
                                        </p>
                                    </div>
                                    <div class="text-end">
                                        <span class="fw-bold">$<?= number_format($item['total'], 2) ?></span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Order Summary -->
                    <div class="col-lg-4">
                        <!-- Summary Card -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white py-3">
                                <h5 class="mb-0"><?= t('dashboard.order_summary') ?? 'Order Summary' ?></h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <span><?= t('dashboard.subtotal') ?? 'Subtotal' ?></span>
                                    <span>$<?= number_format($order['subtotal'], 2) ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span><?= t('dashboard.shipping') ?? 'Shipping' ?></span>
                                    <span>$<?= number_format($order['shipping_cost'], 2) ?></span>
                                </div>
                                <?php if ($order['tax_amount'] > 0): ?>
                                <div class="d-flex justify-content-between mb-2">
                                    <span><?= t('dashboard.tax') ?? 'Tax' ?></span>
                                    <span>$<?= number_format($order['tax_amount'], 2) ?></span>
                                </div>
                                <?php endif; ?>
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <strong><?= t('dashboard.total') ?? 'Total' ?></strong>
                                    <strong>$<?= number_format($order['total_amount'], 2) ?></strong>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Shipping Address -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white py-3">
                                <h6 class="mb-0"><?= t('dashboard.shipping_address') ?? 'Shipping Address' ?></h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-0">
                                    <?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?><br>
                                    <?= nl2br(htmlspecialchars($order['shipping_address'])) ?>
                                </p>
                            </div>
                        </div>
                        
                        <!-- Payment Info -->
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white py-3">
                                <h6 class="mb-0"><?= t('dashboard.payment_info') ?? 'Payment Information' ?></h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-2">
                                    <strong><?= t('dashboard.method') ?? 'Method' ?>:</strong>
                                    <?= ucfirst(str_replace('_', ' ', $order['payment_method'])) ?>
                                </p>
                                <p class="mb-0">
                                    <strong><?= t('dashboard.status') ?? 'Status' ?>:</strong>
                                    <?php
                                    $paymentStatusClasses = [
                                        'pending' => 'text-warning',
                                        'paid' => 'text-success',
                                        'failed' => 'text-danger',
                                        'refunded' => 'text-info'
                                    ];
                                    $paymentClass = $paymentStatusClasses[$order['payment_status']] ?? 'text-secondary';
                                    ?>
                                    <span class="<?= $paymentClass ?>">
                                        <?= ucfirst($order['payment_status']) ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Order Timeline -->
                <?php if ($order['order_status'] !== 'cancelled'): ?>
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0"><?= t('dashboard.order_progress') ?? 'Order Progress' ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="order-timeline">
                            <?php
                            $statuses = ['pending', 'processing', 'shipped', 'delivered'];
                            $currentIndex = array_search($order['order_status'], $statuses);
                            if ($currentIndex === false) $currentIndex = -1;
                            ?>
                            <div class="d-flex justify-content-between position-relative">
                                <div class="progress position-absolute" style="height: 4px; top: 20px; left: 10%; right: 10%; z-index: 0;">
                                    <div class="progress-bar bg-primary" style="width: <?= min(100, ($currentIndex / 3) * 100) ?>%"></div>
                                </div>
                                <?php foreach ($statuses as $index => $status): ?>
                                <div class="text-center position-relative" style="z-index: 1;">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2 <?= $index <= $currentIndex ? 'bg-primary text-white' : 'bg-light text-muted' ?>" style="width: 40px; height: 40px;">
                                        <?php if ($index <= $currentIndex): ?>
                                        <i class="bi bi-check"></i>
                                        <?php else: ?>
                                        <span><?= $index + 1 ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <small class="<?= $index <= $currentIndex ? 'text-primary fw-bold' : 'text-muted' ?>">
                                        <?= ucfirst($status) ?>
                                    </small>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Actions -->
                <div class="mt-4">
                    <a href="<?= url('/dashboard/orders') ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i><?= t('action.back') ?? 'Back to Orders' ?>
                    </a>
                    <?php if ($order['order_status'] === 'delivered'): ?>
                    <a href="<?= url('/products/' . ($orderItems[0]['product_slug'] ?? '')) ?>" class="btn btn-primary">
                        <i class="bi bi-arrow-repeat me-2"></i><?= t('dashboard.reorder') ?? 'Order Again' ?>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include VIEWS_PATH . '/layouts/footer.php'; ?>
