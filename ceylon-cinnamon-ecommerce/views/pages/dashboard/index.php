<?php
/**
 * Customer Dashboard - Main Page
 * Requirements: 5.5 (order history)
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
                <!-- Welcome Section -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center">
                            <div class="avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 64px; height: 64px; font-size: 1.5rem;">
                                <?= strtoupper(substr($user['first_name'] ?? 'U', 0, 1)) ?>
                            </div>
                            <div>
                                <h4 class="mb-1"><?= t('dashboard.welcome') ?? 'Welcome back' ?>, <?= htmlspecialchars($user['first_name'] ?? 'Customer') ?>!</h4>
                                <p class="text-muted mb-0"><?= t('dashboard.member_since') ?? 'Member since' ?> <?= date('F Y', strtotime($user['created_at'] ?? 'now')) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Stats Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-sm-6 col-md-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body text-center">
                                <div class="text-primary mb-2">
                                    <i class="bi bi-box-seam display-6"></i>
                                </div>
                                <h3 class="mb-1"><?= $orderStats['total_orders'] ?? 0 ?></h3>
                                <p class="text-muted small mb-0"><?= t('dashboard.total_orders') ?? 'Total Orders' ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body text-center">
                                <div class="text-success mb-2">
                                    <i class="bi bi-check-circle display-6"></i>
                                </div>
                                <h3 class="mb-1"><?= $orderStats['completed_orders'] ?? 0 ?></h3>
                                <p class="text-muted small mb-0"><?= t('dashboard.completed') ?? 'Completed' ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body text-center">
                                <div class="text-warning mb-2">
                                    <i class="bi bi-clock display-6"></i>
                                </div>
                                <h3 class="mb-1"><?= $orderStats['pending_orders'] ?? 0 ?></h3>
                                <p class="text-muted small mb-0"><?= t('dashboard.pending') ?? 'Pending' ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body text-center">
                                <div class="text-info mb-2">
                                    <i class="bi bi-currency-dollar display-6"></i>
                                </div>
                                <h3 class="mb-1">$<?= number_format($orderStats['total_spent'] ?? 0, 2) ?></h3>
                                <p class="text-muted small mb-0"><?= t('dashboard.total_spent') ?? 'Total Spent' ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Orders -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                        <h5 class="mb-0"><?= t('dashboard.recent_orders') ?? 'Recent Orders' ?></h5>
                        <a href="<?= url('/dashboard/orders') ?>" class="btn btn-sm btn-outline-primary">
                            <?= t('dashboard.view_all') ?? 'View All' ?>
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($recentOrders)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-box-seam display-4 text-muted"></i>
                            <p class="text-muted mt-3"><?= t('dashboard.no_orders') ?? 'No orders yet' ?></p>
                            <a href="<?= url('/products') ?>" class="btn btn-primary">
                                <?= t('dashboard.start_shopping') ?? 'Start Shopping' ?>
                            </a>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th><?= t('dashboard.order_number') ?? 'Order #' ?></th>
                                        <th><?= t('dashboard.date') ?? 'Date' ?></th>
                                        <th><?= t('dashboard.status') ?? 'Status' ?></th>
                                        <th><?= t('dashboard.total') ?? 'Total' ?></th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentOrders as $order): ?>
                                    <tr>
                                        <td>
                                            <a href="<?= url('/dashboard/orders/' . $order['id']) ?>" class="fw-bold text-decoration-none">
                                                <?= htmlspecialchars($order['order_number']) ?>
                                            </a>
                                        </td>
                                        <td><?= date('M d, Y', strtotime($order['created_at'])) ?></td>
                                        <td>
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
                                            <span class="badge <?= $statusClass ?>">
                                                <?= ucfirst($order['order_status']) ?>
                                            </span>
                                        </td>
                                        <td>$<?= number_format($order['total_amount'], 2) ?></td>
                                        <td>
                                            <a href="<?= url('/dashboard/orders/' . $order['id']) ?>" class="btn btn-sm btn-outline-primary">
                                                <?= t('action.view') ?? 'View' ?>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include VIEWS_PATH . '/layouts/footer.php'; ?>
