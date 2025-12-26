<?php
/**
 * Order Tracking Result Page
 */
include VIEWS_PATH . '/layouts/header.php';

$statusClasses = [
    'pending' => 'bg-warning text-dark',
    'processing' => 'bg-info',
    'shipped' => 'bg-primary',
    'delivered' => 'bg-success',
    'cancelled' => 'bg-danger',
    'returned' => 'bg-secondary'
];
$statusClass = $statusClasses[$order['order_status']] ?? 'bg-secondary';
?>

<section class="py-5 bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Back Button -->
                <a href="<?= url('/order/track') ?>" class="btn btn-outline-secondary mb-3">
                    <i class="bi bi-arrow-left me-2"></i>Track Another Order
                </a>

                <!-- Order Header -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h4 class="mb-1">Order #<?= htmlspecialchars($order['order_number']) ?></h4>
                                <p class="text-muted mb-0">
                                    Placed on <?= date('F d, Y \a\t g:i A', strtotime($order['created_at'])) ?>
                                </p>
                            </div>
                            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                <span class="badge <?= $statusClass ?> fs-6 px-3 py-2">
                                    <?= ucfirst($order['order_status']) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Progress -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0"><i class="bi bi-truck me-2"></i>Order Progress</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $steps = ['pending', 'processing', 'shipped', 'delivered'];
                        $currentStep = array_search($order['order_status'], $steps);
                        if ($currentStep === false) $currentStep = -1;
                        ?>
                        <div class="d-flex justify-content-between position-relative mb-4">
                            <div class="progress position-absolute" style="height: 4px; top: 15px; left: 10%; right: 10%; z-index: 0;">
                                <div class="progress-bar bg-success" style="width: <?= min(100, ($currentStep / 3) * 100) ?>%"></div>
                            </div>
                            <?php foreach ($steps as $index => $step): ?>
                            <div class="text-center position-relative" style="z-index: 1;">
                                <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2 
                                    <?= $index <= $currentStep ? 'bg-success text-white' : 'bg-light text-muted border' ?>"
                                    style="width: 36px; height: 36px;">
                                    <?php if ($index < $currentStep): ?>
                                        <i class="bi bi-check"></i>
                                    <?php elseif ($index == $currentStep): ?>
                                        <i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i>
                                    <?php else: ?>
                                        <span><?= $index + 1 ?></span>
                                    <?php endif; ?>
                                </div>
                                <small class="<?= $index <= $currentStep ? 'text-success fw-bold' : 'text-muted' ?>">
                                    <?= ucfirst($step) ?>
                                </small>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0"><i class="bi bi-bag me-2"></i>Order Items</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Product</th>
                                        <th class="text-center">Qty</th>
                                        <th class="text-end">Price</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orderItems as $item): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($item['product_name']) ?></strong>
                                            <?php if (!empty($item['product_sku'])): ?>
                                            <br><small class="text-muted">SKU: <?= htmlspecialchars($item['product_sku']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center"><?= $item['quantity'] ?></td>
                                        <td class="text-end">$<?= number_format($item['price'], 2) ?></td>
                                        <td class="text-end">$<?= number_format($item['total'], 2) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Order Summary -->
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white py-3">
                                <h6 class="mb-0">Order Summary</h6>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Subtotal</span>
                                    <span>$<?= number_format($order['subtotal'], 2) ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Shipping</span>
                                    <span>$<?= number_format($order['shipping_cost'], 2) ?></span>
                                </div>
                                <?php if ($order['tax_amount'] > 0): ?>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Tax</span>
                                    <span>$<?= number_format($order['tax_amount'], 2) ?></span>
                                </div>
                                <?php endif; ?>
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <strong>Total</strong>
                                    <strong>$<?= number_format($order['total_amount'], 2) ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Shipping Address -->
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white py-3">
                                <h6 class="mb-0">Shipping Address</h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-1">
                                    <strong><?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?></strong>
                                </p>
                                <p class="text-muted mb-0">
                                    <?= nl2br(htmlspecialchars($order['shipping_address'])) ?>
                                </p>
                                <?php if (!empty($order['phone'])): ?>
                                <p class="text-muted mb-0 mt-2">
                                    <i class="bi bi-telephone me-1"></i><?= htmlspecialchars($order['phone']) ?>
                                </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Need Help -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-4">
                        <h6>Need Help?</h6>
                        <p class="text-muted mb-3">If you have any questions about your order, please contact us.</p>
                        <a href="<?= url('/contact') ?>" class="btn btn-outline-primary">
                            <i class="bi bi-envelope me-2"></i>Contact Support
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include VIEWS_PATH . '/layouts/footer.php'; ?>
