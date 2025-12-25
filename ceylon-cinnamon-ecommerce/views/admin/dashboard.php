<?php
/**
 * Admin Dashboard with Analytics
 * Requirements: 
 * - 2.6: Admin access to all administrative functions
 * - 15.1: Display daily order and revenue statistics
 * - 15.2: Show top-selling products with sales quantities
 * - 15.3: Provide customer analytics including new registrations and repeat customers
 * - 15.4: Generate sales reports by date range, product, and category
 * - 15.5: Display inventory levels and low-stock alerts
 */

include VIEWS_PATH . '/admin/layouts/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Dashboard</h1>
    <span class="text-muted"><?= date('l, F j, Y') ?></span>
</div>

<!-- Statistics Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon bg-primary bg-opacity-10 text-primary me-3">
                    <i class="bi bi-cart3"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-1">Total Orders</h6>
                    <h3 class="mb-0"><?= number_format($stats['total_orders'] ?? 0) ?></h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon bg-warning bg-opacity-10 text-warning me-3">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-1">Pending Orders</h6>
                    <h3 class="mb-0"><?= number_format($stats['pending_orders'] ?? 0) ?></h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon bg-success bg-opacity-10 text-success me-3">
                    <i class="bi bi-currency-dollar"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-1">Today's Revenue</h6>
                    <h3 class="mb-0">$<?= number_format($stats['today_revenue'] ?? 0, 2) ?></h3>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body d-flex align-items-center">
                <div class="stat-icon bg-info bg-opacity-10 text-info me-3">
                    <i class="bi bi-box-seam"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-1">Products</h6>
                    <h3 class="mb-0"><?= number_format($stats['total_products'] ?? 0) ?></h3>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Revenue Comparison Cards (Requirement 15.1) -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <h6 class="text-muted mb-2">This Week</h6>
                <h4 class="mb-1">$<?= number_format($stats['week_stats']['paid_revenue'] ?? 0, 2) ?></h4>
                <small class="text-muted"><?= number_format($stats['week_stats']['paid_orders'] ?? 0) ?> orders</small>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <h6 class="text-muted mb-2">This Month</h6>
                <h4 class="mb-1">$<?= number_format($stats['month_stats']['paid_revenue'] ?? 0, 2) ?></h4>
                <small class="text-muted"><?= number_format($stats['month_stats']['paid_orders'] ?? 0) ?> orders</small>
                <?php if (isset($stats['revenue_comparison'])): ?>
                    <?php $change = $stats['revenue_comparison']['revenue_change']; ?>
                    <span class="badge bg-<?= $change >= 0 ? 'success' : 'danger' ?> ms-2">
                        <?= $change >= 0 ? '+' : '' ?><?= $change ?>%
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <h6 class="text-muted mb-2">Avg Order Value</h6>
                <h4 class="mb-1">$<?= number_format($stats['customer_analytics']['avg_order_value'] ?? 0, 2) ?></h4>
                <small class="text-muted"><?= number_format($stats['customer_analytics']['total_customers'] ?? 0) ?> customers</small>
            </div>
        </div>
    </div>
</div>

<!-- Revenue Chart (Requirement 15.1) -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Revenue Overview (Last 30 Days)</h5>
            </div>
            <div class="card-body">
                <canvas id="revenueChart" height="100"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Recent Orders -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Orders</h5>
                <a href="<?= url('/admin/orders') ?>" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($stats['recent_orders'])): ?>
                                <?php foreach ($stats['recent_orders'] as $order): ?>
                                    <tr>
                                        <td>
                                            <a href="<?= url('/admin/orders/' . $order['id']) ?>" class="text-decoration-none">
                                                <?= htmlspecialchars($order['order_number']) ?>
                                            </a>
                                        </td>
                                        <td><?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?></td>
                                        <td>$<?= number_format($order['total_amount'], 2) ?></td>
                                        <td>
                                            <?php
                                            $statusColors = [
                                                'pending' => 'warning',
                                                'processing' => 'info',
                                                'shipped' => 'primary',
                                                'delivered' => 'success',
                                                'cancelled' => 'danger',
                                                'returned' => 'secondary'
                                            ];
                                            $color = $statusColors[$order['order_status']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?= $color ?>"><?= ucfirst($order['order_status']) ?></span>
                                        </td>
                                        <td><?= date('M j, Y', strtotime($order['created_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No orders yet</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats & Actions -->
    <div class="col-lg-4">
        <!-- Customer Analytics (Requirement 15.3) -->
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Customer Analytics</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted">Total Customers</span>
                    <strong><?= number_format($stats['customer_analytics']['total_customers'] ?? 0) ?></strong>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted">New (30 days)</span>
                    <strong class="text-success"><?= number_format($stats['customer_analytics']['new_customers'] ?? 0) ?></strong>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted">Repeat Customers</span>
                    <strong><?= number_format($stats['customer_analytics']['repeat_customers'] ?? 0) ?></strong>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted">Wholesale Customers</span>
                    <strong><?= number_format($stats['customer_analytics']['wholesale_customers'] ?? 0) ?></strong>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="<?= url('/admin/products/create') ?>" class="btn btn-outline-primary">
                        <i class="bi bi-plus-lg me-2"></i>Add New Product
                    </a>
                    <a href="<?= url('/admin/orders?status=pending') ?>" class="btn btn-outline-warning">
                        <i class="bi bi-clock me-2"></i>View Pending Orders
                    </a>
                    <a href="<?= url('/admin/products?low_stock=1') ?>" class="btn btn-outline-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>Low Stock Products
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Top Selling Products & Inventory Alerts Row -->
<div class="row g-4 mt-2">
    <!-- Top Selling Products (Requirement 15.2) -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Top Selling Products</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th class="text-end">Qty Sold</th>
                                <th class="text-end">Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($stats['top_products'])): ?>
                                <?php foreach ($stats['top_products'] as $product): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-medium"><?= htmlspecialchars($product['product_name']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($product['product_sku']) ?></small>
                                        </td>
                                        <td class="text-end"><?= number_format($product['total_quantity']) ?></td>
                                        <td class="text-end">$<?= number_format($product['total_revenue'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">No sales data yet</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Inventory Alerts (Requirement 15.5) -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Inventory Alerts</h5>
                <?php if (($stats['low_stock'] ?? 0) > 0): ?>
                    <span class="badge bg-danger"><?= $stats['low_stock'] ?> items low</span>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th class="text-end">Stock</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $alertProducts = array_merge(
                                $stats['out_of_stock_products'] ?? [],
                                array_slice($stats['low_stock_products'] ?? [], 0, 5)
                            );
                            ?>
                            <?php if (!empty($alertProducts)): ?>
                                <?php foreach (array_slice($alertProducts, 0, 5) as $product): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-medium"><?= htmlspecialchars($product['name']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($product['sku']) ?></small>
                                        </td>
                                        <td class="text-end"><?= number_format($product['stock_quantity'] ?? 0) ?></td>
                                        <td>
                                            <?php if (($product['stock_quantity'] ?? 0) == 0): ?>
                                                <span class="badge bg-danger">Out of Stock</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Low Stock</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">
                                        <i class="bi bi-check-circle text-success me-2"></i>All products well stocked
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Order Status & Payment Method Distribution -->
<div class="row g-4 mt-2">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Order Status Distribution</h5>
            </div>
            <div class="card-body">
                <canvas id="orderStatusChart" height="200"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Payment Methods</h5>
            </div>
            <div class="card-body">
                <canvas id="paymentMethodChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Revenue Chart (Requirement 15.1)
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
const chartData = <?= json_encode($stats['chart_data'] ?? ['labels' => [], 'revenue' => [], 'orders' => []]) ?>;

new Chart(revenueCtx, {
    type: 'line',
    data: {
        labels: chartData.labels,
        datasets: [{
            label: 'Revenue ($)',
            data: chartData.revenue,
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.1)',
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '$' + value.toLocaleString();
                    }
                }
            }
        }
    }
});

// Order Status Chart
const orderStatusCtx = document.getElementById('orderStatusChart').getContext('2d');
const orderStatusData = <?= json_encode($stats['order_status_distribution'] ?? []) ?>;

new Chart(orderStatusCtx, {
    type: 'doughnut',
    data: {
        labels: Object.keys(orderStatusData).map(s => s.charAt(0).toUpperCase() + s.slice(1)),
        datasets: [{
            data: Object.values(orderStatusData),
            backgroundColor: [
                '#ffc107', // pending - warning
                '#17a2b8', // processing - info
                '#007bff', // shipped - primary
                '#28a745', // delivered - success
                '#dc3545', // cancelled - danger
                '#6c757d'  // returned - secondary
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Payment Method Chart
const paymentMethodCtx = document.getElementById('paymentMethodChart').getContext('2d');
const paymentMethodData = <?= json_encode($stats['payment_method_distribution'] ?? []) ?>;

new Chart(paymentMethodCtx, {
    type: 'pie',
    data: {
        labels: paymentMethodData.map(p => p.payment_method.charAt(0).toUpperCase() + p.payment_method.slice(1).replace('_', ' ')),
        datasets: [{
            data: paymentMethodData.map(p => parseFloat(p.total_amount)),
            backgroundColor: [
                '#6772e5', // stripe - purple
                '#003087', // paypal - blue
                '#28a745'  // bank_transfer - green
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.label + ': $' + context.raw.toLocaleString();
                    }
                }
            }
        }
    }
});
</script>

<?php include VIEWS_PATH . '/admin/layouts/admin_footer.php'; ?>
