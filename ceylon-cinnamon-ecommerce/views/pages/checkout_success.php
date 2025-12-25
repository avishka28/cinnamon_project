<?php
/**
 * Checkout Success Page View
 * Requirement 4.6: Generate order confirmation
 */
$pageTitle = 'Order Confirmed - Ceylon Cinnamon';
include VIEWS_PATH . '/layouts/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="text-center mb-5">
                <div class="mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" fill="currentColor" class="bi bi-check-circle-fill text-success" viewBox="0 0 16 16">
                        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                    </svg>
                </div>
                <h1 class="display-5 mb-3">Thank You for Your Order!</h1>
                <p class="lead text-muted">
                    Your order has been placed successfully.
                    <?php if ($order): ?>
                        Order number: <strong><?= htmlspecialchars($order['order_number']) ?></strong>
                    <?php endif; ?>
                </p>
            </div>

            <?php if ($order): ?>
                <!-- Order Details Card -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Order Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-sm-6">
                                <strong>Order Number:</strong><br>
                                <?= htmlspecialchars($order['order_number']) ?>
                            </div>
                            <div class="col-sm-6">
                                <strong>Order Date:</strong><br>
                                <?= date('F j, Y g:i A', strtotime($order['created_at'])) ?>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-sm-6">
                                <strong>Payment Method:</strong><br>
                                <?= ucfirst(str_replace('_', ' ', $order['payment_method'])) ?>
                            </div>
                            <div class="col-sm-6">
                                <strong>Payment Status:</strong><br>
                                <span class="badge bg-<?= $order['payment_status'] === 'paid' ? 'success' : 'warning' ?>">
                                    <?= ucfirst($order['payment_status']) ?>
                                </span>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-6">
                                <strong>Shipping Address:</strong><br>
                                <?= nl2br(htmlspecialchars($order['shipping_address'])) ?>
                            </div>
                            <div class="col-sm-6">
                                <strong>Email:</strong><br>
                                <?= htmlspecialchars($order['email']) ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Items -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Items Ordered</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Price</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order['items'] as $item): ?>
                                    <tr>
                                        <td>
                                            <?= htmlspecialchars($item['product_name']) ?>
                                            <br><small class="text-muted">SKU: <?= htmlspecialchars($item['product_sku']) ?></small>
                                        </td>
                                        <td class="text-center"><?= $item['quantity'] ?></td>
                                        <td class="text-end">$<?= number_format($item['price'], 2) ?></td>
                                        <td class="text-end">$<?= number_format($item['total'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                                    <td class="text-end">$<?= number_format($order['subtotal'], 2) ?></td>
                                </tr>
                                <?php if ($order['shipping_cost'] > 0): ?>
                                    <tr>
                                        <td colspan="3" class="text-end">Shipping:</td>
                                        <td class="text-end">$<?= number_format($order['shipping_cost'], 2) ?></td>
                                    </tr>
                                <?php endif; ?>
                                <?php if ($order['tax_amount'] > 0): ?>
                                    <tr>
                                        <td colspan="3" class="text-end">Tax:</td>
                                        <td class="text-end">$<?= number_format($order['tax_amount'], 2) ?></td>
                                    </tr>
                                <?php endif; ?>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                    <td class="text-end"><strong>$<?= number_format($order['total_amount'], 2) ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <?php if (!empty($bankDetails)): ?>
                    <!-- Bank Transfer Instructions -->
                    <div class="card mb-4 border-warning">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0">Bank Transfer Instructions</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info mb-3">
                                <strong>Important:</strong> Please complete your bank transfer within 48 hours to avoid order cancellation.
                            </div>
                            
                            <p><strong>Transfer Reference:</strong> <?= htmlspecialchars($bankDetails['reference']) ?></p>
                            <p><strong>Amount to Transfer:</strong> $<?= number_format($order['total_amount'], 2) ?> USD</p>
                            
                            <hr>
                            
                            <h6>Bank Account Details:</h6>
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Bank Name:</strong></td>
                                    <td><?= htmlspecialchars($bankDetails['bank_details']['bank_name']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Account Name:</strong></td>
                                    <td><?= htmlspecialchars($bankDetails['bank_details']['account_name']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Account Number:</strong></td>
                                    <td><?= htmlspecialchars($bankDetails['bank_details']['account_number']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Routing Number:</strong></td>
                                    <td><?= htmlspecialchars($bankDetails['bank_details']['routing_number']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>SWIFT Code:</strong></td>
                                    <td><?= htmlspecialchars($bankDetails['bank_details']['swift_code']) ?></td>
                                </tr>
                            </table>
                            
                            <div class="alert alert-warning mt-3 mb-0">
                                <strong>Please include the reference number</strong> (<?= htmlspecialchars($bankDetails['reference']) ?>) in your transfer description so we can identify your payment.
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- What's Next -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">What's Next?</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <i class="bi bi-envelope text-primary me-2"></i>
                                A confirmation email has been sent to <strong><?= htmlspecialchars($order['email']) ?></strong>
                            </li>
                            <li class="mb-2">
                                <i class="bi bi-box-seam text-primary me-2"></i>
                                We'll notify you when your order ships
                            </li>
                            <li>
                                <i class="bi bi-search text-primary me-2"></i>
                                Track your order anytime using your order number
                            </li>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Action Buttons -->
            <div class="text-center">
                <a href="<?= url('/products') ?>" class="btn btn-primary btn-lg me-2">
                    Continue Shopping
                </a>
                <a href="<?= url('/order/track') ?>" class="btn btn-outline-primary btn-lg">
                    Track Order
                </a>
            </div>
        </div>
    </div>
</div>

<?php include VIEWS_PATH . '/layouts/footer.php'; ?>
