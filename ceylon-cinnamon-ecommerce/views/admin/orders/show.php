<?php
/**
 * Admin Order Detail View
 * Requirements: 7.4 - Order detail view showing customer info, products, payment status
 * Requirements: 7.2 - Order status updates with notifications
 * Requirements: 7.5 - Order notes functionality
 */

include VIEWS_PATH . '/admin/layouts/admin_header.php';

$flash = $sessionManager->getFlash('success') ?? $sessionManager->getFlash('error');

// Status badge colors
$statusColors = [
    'pending' => 'warning',
    'processing' => 'info',
    'shipped' => 'primary',
    'delivered' => 'success',
    'cancelled' => 'danger',
    'returned' => 'secondary'
];

$paymentColors = [
    'pending' => 'warning',
    'paid' => 'success',
    'failed' => 'danger',
    'refunded' => 'secondary'
];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?= url('/admin/orders') ?>" class="text-decoration-none text-muted">
            <i class="bi bi-arrow-left me-2"></i>Back to Orders
        </a>
        <h1 class="h3 mb-0 mt-2">Order #<?= htmlspecialchars($order['order_number']) ?></h1>
        <small class="text-muted">Placed on <?= date('F j, Y \a\t g:i A', strtotime($order['created_at'])) ?></small>
    </div>
    <div>
        <a href="<?= url('/admin/orders/' . $order['id'] . '/invoice') ?>" 
           class="btn btn-outline-secondary" target="_blank">
            <i class="bi bi-file-text me-2"></i>View Invoice
        </a>
    </div>
</div>

<?php if ($flash): ?>
    <div class="alert alert-<?= isset($sessionManager->getFlash('error')) ? 'danger' : 'success' ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Main Content -->
    <div class="col-lg-8">
        <!-- Order Items -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Order Items</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="table-light">
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
                                        <div class="d-flex align-items-center">
                                            <div>
                                                <strong><?= htmlspecialchars($item['product_name']) ?></strong>
                                                <br>
                                                <small class="text-muted">SKU: <?= htmlspecialchars($item['product_sku']) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center"><?= (int)$item['quantity'] ?></td>
                                    <td class="text-end">$<?= number_format((float)$item['price'], 2) ?></td>
                                    <td class="text-end">$<?= number_format((float)$item['total'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="3" class="text-end">Subtotal:</td>
                                <td class="text-end">$<?= number_format((float)$order['subtotal'], 2) ?></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end">Shipping:</td>
                                <td class="text-end">$<?= number_format((float)$order['shipping_cost'], 2) ?></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end">Tax:</td>
                                <td class="text-end">$<?= number_format((float)$order['tax_amount'], 2) ?></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                <td class="text-end"><strong>$<?= number_format((float)$order['total_amount'], 2) ?></strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Customer Information -->
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Customer Information</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-1"><strong><?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?></strong></p>
                        <p class="mb-1"><?= htmlspecialchars($order['email']) ?></p>
                        <?php if ($order['phone']): ?>
                            <p class="mb-0"><?= htmlspecialchars($order['phone']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Shipping Address</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-0"><?= nl2br(htmlspecialchars($order['shipping_address'])) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Notes -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Order Notes</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($order['notes'])): ?>
                    <div class="notes-list mb-3">
                        <?php 
                        $notes = explode("\n", $order['notes']);
                        foreach ($notes as $note): 
                            if (trim($note)):
                        ?>
                            <div class="note-item p-2 mb-2 bg-light rounded">
                                <small><?= htmlspecialchars($note) ?></small>
                            </div>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-3">No notes yet.</p>
                <?php endif; ?>

                <!-- Add Note Form -->
                <form method="POST" action="<?= url('/admin/orders/' . $order['id'] . '/note') ?>">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <div class="input-group">
                        <input type="text" class="form-control" name="note" placeholder="Add a note..." required>
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="bi bi-plus-lg"></i> Add Note
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
        <!-- Order Status -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Order Status</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <span class="badge bg-<?= $statusColors[$order['order_status']] ?? 'secondary' ?> fs-6">
                        <?= ucfirst($order['order_status']) ?>
                    </span>
                </div>

                <form method="POST" action="<?= url('/admin/orders/' . $order['id'] . '/status') ?>" id="statusForm">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Update Status</label>
                        <select class="form-select" name="status" id="statusSelect">
                            <?php foreach ($orderStatuses as $status): ?>
                                <option value="<?= $status ?>" <?= $order['order_status'] === $status ? 'selected' : '' ?>>
                                    <?= ucfirst($status) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Shipping fields (shown when status is 'shipped') -->
                    <div id="shippingFields" style="display: none;">
                        <div class="mb-3">
                            <label class="form-label">Tracking Number</label>
                            <input type="text" class="form-control" name="tracking_number" placeholder="Enter tracking number">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Carrier</label>
                            <select class="form-select" name="carrier">
                                <option value="">Select carrier</option>
                                <option value="DHL">DHL</option>
                                <option value="FedEx">FedEx</option>
                                <option value="UPS">UPS</option>
                                <option value="USPS">USPS</option>
                                <option value="Sri Lanka Post">Sri Lanka Post</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="send_notification" value="1" id="sendNotification" checked>
                        <label class="form-check-label" for="sendNotification">
                            Send email notification to customer
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-check-lg me-2"></i>Update Status
                    </button>
                </form>
            </div>
        </div>

        <!-- Payment Information -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Payment Information</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Method:</span>
                    <span><?= ucfirst(str_replace('_', ' ', $order['payment_method'])) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Status:</span>
                    <span class="badge bg-<?= $paymentColors[$order['payment_status']] ?? 'secondary' ?>">
                        <?= ucfirst($order['payment_status']) ?>
                    </span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Amount:</span>
                    <strong>$<?= number_format((float)$order['total_amount'], 2) ?></strong>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="<?= url('/admin/orders/' . $order['id'] . '/invoice') ?>" 
                       class="btn btn-outline-primary" target="_blank">
                        <i class="bi bi-file-text me-2"></i>View Invoice
                    </a>
                    <a href="mailto:<?= htmlspecialchars($order['email']) ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-envelope me-2"></i>Email Customer
                    </a>
                    <?php if (!in_array($order['order_status'], ['cancelled', 'delivered', 'returned'])): ?>
                        <button type="button" class="btn btn-outline-danger" onclick="cancelOrder()">
                            <i class="bi bi-x-circle me-2"></i>Cancel Order
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Show/hide shipping fields based on status
document.getElementById('statusSelect').addEventListener('change', function() {
    const shippingFields = document.getElementById('shippingFields');
    if (this.value === 'shipped') {
        shippingFields.style.display = 'block';
    } else {
        shippingFields.style.display = 'none';
    }
});

// Cancel order confirmation
function cancelOrder() {
    if (confirm('Are you sure you want to cancel this order? This will restore the product stock.')) {
        document.getElementById('statusSelect').value = 'cancelled';
        document.getElementById('statusForm').submit();
    }
}
</script>

<?php include VIEWS_PATH . '/admin/layouts/admin_footer.php'; ?>
