<?php
/**
 * Customer Dashboard - Orders List
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
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0"><?= t('dashboard.my_orders') ?? 'My Orders' ?></h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($orders)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-box-seam display-4 text-muted"></i>
                            <p class="text-muted mt-3"><?= t('dashboard.no_orders') ?? 'No orders yet' ?></p>
                            <a href="<?= url('/products') ?>" class="btn btn-primary">
                                <?= t('dashboard.start_shopping') ?? 'Start Shopping' ?>
                            </a>
                        </div>
                        <?php else: ?>
                        
                        <!-- Mobile View -->
                        <div class="d-md-none">
                            <?php foreach ($orders as $order): ?>
                            <div class="border-bottom p-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <a href="<?= url('/dashboard/orders/' . $order['id']) ?>" class="fw-bold text-decoration-none">
                                            #<?= htmlspecialchars($order['order_number']) ?>
                                        </a>
                                        <br>
                                        <small class="text-muted"><?= date('M d, Y', strtotime($order['created_at'])) ?></small>
                                    </div>
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
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold">$<?= number_format($order['total_amount'], 2) ?></span>
                                    <a href="<?= url('/dashboard/orders/' . $order['id']) ?>" class="btn btn-sm btn-outline-primary">
                                        <?= t('action.view') ?? 'View Details' ?>
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Desktop View -->
                        <div class="table-responsive d-none d-md-block">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th><?= t('dashboard.order_number') ?? 'Order #' ?></th>
                                        <th><?= t('dashboard.date') ?? 'Date' ?></th>
                                        <th><?= t('dashboard.items') ?? 'Items' ?></th>
                                        <th><?= t('dashboard.status') ?? 'Status' ?></th>
                                        <th><?= t('dashboard.total') ?? 'Total' ?></th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>
                                            <a href="<?= url('/dashboard/orders/' . $order['id']) ?>" class="fw-bold text-decoration-none">
                                                <?= htmlspecialchars($order['order_number']) ?>
                                            </a>
                                        </td>
                                        <td><?= date('M d, Y', strtotime($order['created_at'])) ?></td>
                                        <td><?= $order['item_count'] ?? '-' ?> items</td>
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
                        
                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                        <div class="card-footer bg-white">
                            <nav aria-label="Orders pagination">
                                <ul class="pagination justify-content-center mb-0">
                                    <?php if ($currentPage > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $currentPage - 1 ?>">
                                            <i class="bi bi-chevron-left"></i>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                                    <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                    </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($currentPage < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $currentPage + 1 ?>">
                                            <i class="bi bi-chevron-right"></i>
                                        </a>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                        <?php endif; ?>
                        
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include VIEWS_PATH . '/layouts/footer.php'; ?>
