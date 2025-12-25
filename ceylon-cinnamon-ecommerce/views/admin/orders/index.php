<?php
/**
 * Admin Orders List
 * Requirements: 7.1 - Order listing with filtering and sorting options
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
    <h1 class="h3 mb-0">Orders</h1>
    <div>
        <span class="text-muted">Total: <?= number_format($total) ?> orders</span>
    </div>
</div>

<?php if ($flash): ?>
    <div class="alert alert-<?= isset($sessionManager->getFlash('error')) ? 'danger' : 'success' ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?= url('/admin/orders') ?>" class="row g-3">
            <div class="col-md-3">
                <input type="text" class="form-control" name="search" 
                       placeholder="Search order #, email, name..." 
                       value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <select class="form-select" name="status">
                    <option value="">All Statuses</option>
                    <?php foreach ($orderStatuses as $status): ?>
                        <option value="<?= $status ?>" 
                                <?= ($filters['order_status'] ?? '') === $status ? 'selected' : '' ?>>
                            <?= ucfirst($status) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="payment">
                    <option value="">All Payments</option>
                    <?php foreach ($paymentStatuses as $status): ?>
                        <option value="<?= $status ?>" 
                                <?= ($filters['payment_status'] ?? '') === $status ? 'selected' : '' ?>>
                            <?= ucfirst($status) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" class="form-control" name="date_from" 
                       placeholder="From date"
                       value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <input type="date" class="form-control" name="date_to" 
                       placeholder="To date"
                       value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>">
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-outline-primary w-100">
                    <i class="bi bi-search"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Orders Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th style="width: 100px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($orders)): ?>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>
                                    <a href="<?= url('/admin/orders/' . $order['id']) ?>" class="fw-bold text-decoration-none">
                                        <?= htmlspecialchars($order['order_number']) ?>
                                    </a>
                                </td>
                                <td>
                                    <div><?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($order['email']) ?></small>
                                </td>
                                <td>
                                    <div><?= date('M j, Y', strtotime($order['created_at'])) ?></div>
                                    <small class="text-muted"><?= date('g:i A', strtotime($order['created_at'])) ?></small>
                                </td>
                                <td>
                                    <strong>$<?= number_format((float)$order['total_amount'], 2) ?></strong>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $paymentColors[$order['payment_status']] ?? 'secondary' ?>">
                                        <?= ucfirst($order['payment_status']) ?>
                                    </span>
                                    <br>
                                    <small class="text-muted"><?= ucfirst(str_replace('_', ' ', $order['payment_method'])) ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $statusColors[$order['order_status']] ?? 'secondary' ?>">
                                        <?= ucfirst($order['order_status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= url('/admin/orders/' . $order['id']) ?>" 
                                           class="btn btn-outline-primary" title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="<?= url('/admin/orders/' . $order['id'] . '/invoice') ?>" 
                                           class="btn btn-outline-secondary" title="Invoice" target="_blank">
                                            <i class="bi bi-file-text"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-cart-x fs-1 d-block mb-3"></i>
                                No orders found
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <?php if ($pages > 1): ?>
        <div class="card-footer">
            <nav>
                <ul class="pagination mb-0 justify-content-center">
                    <?php 
                    $queryParams = http_build_query(array_filter([
                        'search' => $filters['search'] ?? '',
                        'status' => $filters['order_status'] ?? '',
                        'payment' => $filters['payment_status'] ?? '',
                        'date_from' => $filters['date_from'] ?? '',
                        'date_to' => $filters['date_to'] ?? ''
                    ]));
                    ?>
                    <?php for ($i = 1; $i <= $pages; $i++): ?>
                        <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                            <a class="page-link" href="<?= url('/admin/orders?page=' . $i . ($queryParams ? '&' . $queryParams : '')) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<?php include VIEWS_PATH . '/admin/layouts/admin_footer.php'; ?>
