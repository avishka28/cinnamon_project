<?php
/**
 * Analytics Model
 * Handles order statistics, revenue analytics, customer analytics, and product performance
 * 
 * Requirements:
 * - 15.1: Display daily order and revenue statistics
 * - 15.2: Show top-selling products with sales quantities
 * - 15.3: Provide customer analytics including new registrations and repeat customers
 * - 15.4: Generate sales reports by date range, product, and category
 * - 15.5: Display inventory levels and low-stock alerts
 */

declare(strict_types=1);

class Analytics extends Model
{
    protected string $table = 'orders';

    /**
     * Get daily order and revenue statistics
     * Requirement 15.1: Display daily order and revenue statistics
     * 
     * @param string|null $dateFrom Start date (Y-m-d format)
     * @param string|null $dateTo End date (Y-m-d format)
     * @return array Daily statistics
     */
    public function getDailyStats(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $dateFrom = $dateFrom ?? date('Y-m-d', strtotime('-30 days'));
        $dateTo = $dateTo ?? date('Y-m-d');

        $sql = "SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as order_count,
                    COALESCE(SUM(total_amount), 0) as revenue,
                    COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END), 0) as paid_revenue,
                    COUNT(CASE WHEN payment_status = 'paid' THEN 1 END) as paid_orders
                FROM orders 
                WHERE DATE(created_at) BETWEEN :date_from AND :date_to
                GROUP BY DATE(created_at)
                ORDER BY date ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'date_from' => $dateFrom,
            'date_to' => $dateTo
        ]);

        return $stmt->fetchAll();
    }

    /**
     * Get today's statistics
     * Requirement 15.1: Display daily order and revenue statistics
     * 
     * @return array Today's stats
     */
    public function getTodayStats(): array
    {
        $sql = "SELECT 
                    COUNT(*) as order_count,
                    COALESCE(SUM(total_amount), 0) as total_revenue,
                    COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END), 0) as paid_revenue,
                    COUNT(CASE WHEN payment_status = 'paid' THEN 1 END) as paid_orders,
                    COUNT(CASE WHEN order_status = 'pending' THEN 1 END) as pending_orders
                FROM orders 
                WHERE DATE(created_at) = CURDATE()";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetch() ?: [
            'order_count' => 0,
            'total_revenue' => 0,
            'paid_revenue' => 0,
            'paid_orders' => 0,
            'pending_orders' => 0
        ];
    }

    /**
     * Get this week's statistics
     * Requirement 15.1: Display daily order and revenue statistics
     * 
     * @return array This week's stats
     */
    public function getWeekStats(): array
    {
        $sql = "SELECT 
                    COUNT(*) as order_count,
                    COALESCE(SUM(total_amount), 0) as total_revenue,
                    COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END), 0) as paid_revenue,
                    COUNT(CASE WHEN payment_status = 'paid' THEN 1 END) as paid_orders
                FROM orders 
                WHERE YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetch() ?: [
            'order_count' => 0,
            'total_revenue' => 0,
            'paid_revenue' => 0,
            'paid_orders' => 0
        ];
    }

    /**
     * Get this month's statistics
     * Requirement 15.1: Display daily order and revenue statistics
     * 
     * @return array This month's stats
     */
    public function getMonthStats(): array
    {
        $sql = "SELECT 
                    COUNT(*) as order_count,
                    COALESCE(SUM(total_amount), 0) as total_revenue,
                    COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END), 0) as paid_revenue,
                    COUNT(CASE WHEN payment_status = 'paid' THEN 1 END) as paid_orders
                FROM orders 
                WHERE YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetch() ?: [
            'order_count' => 0,
            'total_revenue' => 0,
            'paid_revenue' => 0,
            'paid_orders' => 0
        ];
    }


    /**
     * Get top-selling products
     * Requirement 15.2: Show top-selling products with sales quantities
     * 
     * @param int $limit Number of products to return
     * @param string|null $dateFrom Start date filter
     * @param string|null $dateTo End date filter
     * @return array Top-selling products
     */
    public function getTopSellingProducts(int $limit = 10, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $sql = "SELECT 
                    oi.product_id,
                    oi.product_name,
                    oi.product_sku,
                    p.price as current_price,
                    p.stock_quantity,
                    SUM(oi.quantity) as total_quantity,
                    SUM(oi.total) as total_revenue,
                    COUNT(DISTINCT oi.order_id) as order_count
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.id
                LEFT JOIN products p ON oi.product_id = p.id
                WHERE o.payment_status = 'paid'";

        $params = [];
        
        if ($dateFrom) {
            $sql .= " AND DATE(o.created_at) >= :date_from";
            $params['date_from'] = $dateFrom;
        }
        
        if ($dateTo) {
            $sql .= " AND DATE(o.created_at) <= :date_to";
            $params['date_to'] = $dateTo;
        }

        $sql .= " GROUP BY oi.product_id, oi.product_name, oi.product_sku, p.price, p.stock_quantity
                  ORDER BY total_quantity DESC
                  LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Get customer analytics
     * Requirement 15.3: Provide customer analytics including new registrations and repeat customers
     * 
     * @param string|null $dateFrom Start date filter
     * @param string|null $dateTo End date filter
     * @return array Customer analytics
     */
    public function getCustomerAnalytics(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $dateFrom = $dateFrom ?? date('Y-m-d', strtotime('-30 days'));
        $dateTo = $dateTo ?? date('Y-m-d');

        // Total customers
        $sql = "SELECT COUNT(*) FROM users WHERE role = 'customer'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $totalCustomers = (int) $stmt->fetchColumn();

        // New registrations in period
        $sql = "SELECT COUNT(*) FROM users 
                WHERE role = 'customer' 
                AND DATE(created_at) BETWEEN :date_from AND :date_to";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['date_from' => $dateFrom, 'date_to' => $dateTo]);
        $newCustomers = (int) $stmt->fetchColumn();

        // Repeat customers (customers with more than 1 order)
        $sql = "SELECT COUNT(DISTINCT user_id) FROM orders 
                WHERE user_id IS NOT NULL 
                AND user_id IN (
                    SELECT user_id FROM orders 
                    WHERE user_id IS NOT NULL 
                    GROUP BY user_id 
                    HAVING COUNT(*) > 1
                )";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $repeatCustomers = (int) $stmt->fetchColumn();

        // Customers who ordered in period
        $sql = "SELECT COUNT(DISTINCT user_id) FROM orders 
                WHERE user_id IS NOT NULL 
                AND DATE(created_at) BETWEEN :date_from AND :date_to";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['date_from' => $dateFrom, 'date_to' => $dateTo]);
        $activeCustomers = (int) $stmt->fetchColumn();

        // Average order value
        $sql = "SELECT COALESCE(AVG(total_amount), 0) FROM orders WHERE payment_status = 'paid'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $avgOrderValue = (float) $stmt->fetchColumn();

        // Wholesale customers
        $sql = "SELECT COUNT(*) FROM users WHERE is_wholesale = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $wholesaleCustomers = (int) $stmt->fetchColumn();

        return [
            'total_customers' => $totalCustomers,
            'new_customers' => $newCustomers,
            'repeat_customers' => $repeatCustomers,
            'active_customers' => $activeCustomers,
            'avg_order_value' => round($avgOrderValue, 2),
            'wholesale_customers' => $wholesaleCustomers,
            'date_from' => $dateFrom,
            'date_to' => $dateTo
        ];
    }

    /**
     * Get new customer registrations by day
     * Requirement 15.3: Customer analytics
     * 
     * @param string|null $dateFrom Start date
     * @param string|null $dateTo End date
     * @return array Daily registration counts
     */
    public function getNewCustomersByDay(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $dateFrom = $dateFrom ?? date('Y-m-d', strtotime('-30 days'));
        $dateTo = $dateTo ?? date('Y-m-d');

        $sql = "SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as count
                FROM users 
                WHERE role = 'customer'
                AND DATE(created_at) BETWEEN :date_from AND :date_to
                GROUP BY DATE(created_at)
                ORDER BY date ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['date_from' => $dateFrom, 'date_to' => $dateTo]);

        return $stmt->fetchAll();
    }


    /**
     * Get sales report by date range
     * Requirement 15.4: Generate sales reports by date range, product, and category
     * 
     * @param string $dateFrom Start date
     * @param string $dateTo End date
     * @param string $groupBy Group by: 'day', 'week', 'month'
     * @return array Sales report data
     */
    public function getSalesReport(string $dateFrom, string $dateTo, string $groupBy = 'day'): array
    {
        $dateFormat = match($groupBy) {
            'week' => "DATE_FORMAT(created_at, '%Y-%u')",
            'month' => "DATE_FORMAT(created_at, '%Y-%m')",
            default => "DATE(created_at)"
        };

        $sql = "SELECT 
                    {$dateFormat} as period,
                    COUNT(*) as order_count,
                    COALESCE(SUM(total_amount), 0) as total_revenue,
                    COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END), 0) as paid_revenue,
                    COALESCE(AVG(total_amount), 0) as avg_order_value,
                    COUNT(CASE WHEN payment_status = 'paid' THEN 1 END) as paid_orders,
                    COUNT(CASE WHEN order_status = 'cancelled' THEN 1 END) as cancelled_orders
                FROM orders 
                WHERE DATE(created_at) BETWEEN :date_from AND :date_to
                GROUP BY {$dateFormat}
                ORDER BY period ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['date_from' => $dateFrom, 'date_to' => $dateTo]);

        return $stmt->fetchAll();
    }

    /**
     * Get sales report by product
     * Requirement 15.4: Generate sales reports by product
     * 
     * @param string|null $dateFrom Start date
     * @param string|null $dateTo End date
     * @param int $limit Number of products
     * @return array Product sales report
     */
    public function getSalesByProduct(?string $dateFrom = null, ?string $dateTo = null, int $limit = 20): array
    {
        $sql = "SELECT 
                    oi.product_id,
                    oi.product_name,
                    oi.product_sku,
                    p.category_id,
                    c.name as category_name,
                    SUM(oi.quantity) as total_quantity,
                    SUM(oi.total) as total_revenue,
                    COUNT(DISTINCT oi.order_id) as order_count,
                    AVG(oi.price) as avg_price
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.id
                LEFT JOIN products p ON oi.product_id = p.id
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE o.payment_status = 'paid'";

        $params = [];
        
        if ($dateFrom) {
            $sql .= " AND DATE(o.created_at) >= :date_from";
            $params['date_from'] = $dateFrom;
        }
        
        if ($dateTo) {
            $sql .= " AND DATE(o.created_at) <= :date_to";
            $params['date_to'] = $dateTo;
        }

        $sql .= " GROUP BY oi.product_id, oi.product_name, oi.product_sku, p.category_id, c.name
                  ORDER BY total_revenue DESC
                  LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Get sales report by category
     * Requirement 15.4: Generate sales reports by category
     * 
     * @param string|null $dateFrom Start date
     * @param string|null $dateTo End date
     * @return array Category sales report
     */
    public function getSalesByCategory(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $sql = "SELECT 
                    c.id as category_id,
                    c.name as category_name,
                    COUNT(DISTINCT oi.order_id) as order_count,
                    SUM(oi.quantity) as total_quantity,
                    SUM(oi.total) as total_revenue
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.id
                JOIN products p ON oi.product_id = p.id
                JOIN categories c ON p.category_id = c.id
                WHERE o.payment_status = 'paid'";

        $params = [];
        
        if ($dateFrom) {
            $sql .= " AND DATE(o.created_at) >= :date_from";
            $params['date_from'] = $dateFrom;
        }
        
        if ($dateTo) {
            $sql .= " AND DATE(o.created_at) <= :date_to";
            $params['date_to'] = $dateTo;
        }

        $sql .= " GROUP BY c.id, c.name
                  ORDER BY total_revenue DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }


    /**
     * Get inventory levels and low-stock alerts
     * Requirement 15.5: Display inventory levels and low-stock alerts
     * 
     * @param int $lowStockThreshold Threshold for low stock alert
     * @return array Inventory data
     */
    public function getInventoryStatus(int $lowStockThreshold = 10): array
    {
        // Total inventory value
        $sql = "SELECT 
                    COUNT(*) as total_products,
                    SUM(stock_quantity) as total_stock,
                    SUM(stock_quantity * price) as inventory_value
                FROM products 
                WHERE is_active = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $totals = $stmt->fetch();

        // Low stock products
        $sql = "SELECT 
                    p.id,
                    p.sku,
                    p.name,
                    p.stock_quantity,
                    p.price,
                    c.name as category_name
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.is_active = 1 
                AND p.stock_quantity <= :threshold
                ORDER BY p.stock_quantity ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['threshold' => $lowStockThreshold]);
        $lowStockProducts = $stmt->fetchAll();

        // Out of stock products
        $sql = "SELECT 
                    p.id,
                    p.sku,
                    p.name,
                    c.name as category_name
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.is_active = 1 
                AND p.stock_quantity = 0";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $outOfStockProducts = $stmt->fetchAll();

        return [
            'total_products' => (int) ($totals['total_products'] ?? 0),
            'total_stock' => (int) ($totals['total_stock'] ?? 0),
            'inventory_value' => (float) ($totals['inventory_value'] ?? 0),
            'low_stock_count' => count($lowStockProducts),
            'out_of_stock_count' => count($outOfStockProducts),
            'low_stock_products' => $lowStockProducts,
            'out_of_stock_products' => $outOfStockProducts,
            'low_stock_threshold' => $lowStockThreshold
        ];
    }

    /**
     * Get low stock products
     * Requirement 15.5: Display low-stock alerts
     * 
     * @param int $threshold Stock threshold
     * @param int $limit Number of products
     * @return array Low stock products
     */
    public function getLowStockProducts(int $threshold = 10, int $limit = 20): array
    {
        $sql = "SELECT 
                    p.id,
                    p.sku,
                    p.name,
                    p.stock_quantity,
                    p.price,
                    c.name as category_name
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.is_active = 1 
                AND p.stock_quantity <= :threshold
                AND p.stock_quantity > 0
                ORDER BY p.stock_quantity ASC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':threshold', $threshold, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Get order status distribution
     * 
     * @param string|null $dateFrom Start date
     * @param string|null $dateTo End date
     * @return array Order status counts
     */
    public function getOrderStatusDistribution(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $sql = "SELECT 
                    order_status,
                    COUNT(*) as count
                FROM orders
                WHERE 1=1";

        $params = [];
        
        if ($dateFrom) {
            $sql .= " AND DATE(created_at) >= :date_from";
            $params['date_from'] = $dateFrom;
        }
        
        if ($dateTo) {
            $sql .= " AND DATE(created_at) <= :date_to";
            $params['date_to'] = $dateTo;
        }

        $sql .= " GROUP BY order_status";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $results = $stmt->fetchAll();
        
        // Convert to associative array
        $distribution = [];
        foreach ($results as $row) {
            $distribution[$row['order_status']] = (int) $row['count'];
        }

        return $distribution;
    }

    /**
     * Get payment method distribution
     * 
     * @param string|null $dateFrom Start date
     * @param string|null $dateTo End date
     * @return array Payment method counts
     */
    public function getPaymentMethodDistribution(?string $dateFrom = null, ?string $dateTo = null): array
    {
        $sql = "SELECT 
                    payment_method,
                    COUNT(*) as count,
                    SUM(total_amount) as total_amount
                FROM orders
                WHERE payment_status = 'paid'";

        $params = [];
        
        if ($dateFrom) {
            $sql .= " AND DATE(created_at) >= :date_from";
            $params['date_from'] = $dateFrom;
        }
        
        if ($dateTo) {
            $sql .= " AND DATE(created_at) <= :date_to";
            $params['date_to'] = $dateTo;
        }

        $sql .= " GROUP BY payment_method";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }


    /**
     * Get comprehensive dashboard summary
     * Combines multiple analytics for dashboard display
     * 
     * @return array Dashboard summary data
     */
    public function getDashboardSummary(): array
    {
        return [
            'today' => $this->getTodayStats(),
            'week' => $this->getWeekStats(),
            'month' => $this->getMonthStats(),
            'top_products' => $this->getTopSellingProducts(5),
            'customer_analytics' => $this->getCustomerAnalytics(),
            'inventory' => $this->getInventoryStatus(),
            'order_status' => $this->getOrderStatusDistribution(),
            'payment_methods' => $this->getPaymentMethodDistribution()
        ];
    }

    /**
     * Get revenue comparison (current vs previous period)
     * 
     * @param string $period 'day', 'week', 'month'
     * @return array Comparison data
     */
    public function getRevenueComparison(string $period = 'month'): array
    {
        $currentStart = match($period) {
            'day' => date('Y-m-d'),
            'week' => date('Y-m-d', strtotime('monday this week')),
            'month' => date('Y-m-01'),
            default => date('Y-m-01')
        };

        $previousStart = match($period) {
            'day' => date('Y-m-d', strtotime('-1 day')),
            'week' => date('Y-m-d', strtotime('monday last week')),
            'month' => date('Y-m-01', strtotime('-1 month')),
            default => date('Y-m-01', strtotime('-1 month'))
        };

        $previousEnd = match($period) {
            'day' => date('Y-m-d', strtotime('-1 day')),
            'week' => date('Y-m-d', strtotime('sunday last week')),
            'month' => date('Y-m-t', strtotime('-1 month')),
            default => date('Y-m-t', strtotime('-1 month'))
        };

        // Current period revenue
        $sql = "SELECT COALESCE(SUM(total_amount), 0) as revenue, COUNT(*) as orders
                FROM orders 
                WHERE payment_status = 'paid' 
                AND DATE(created_at) >= :start_date";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['start_date' => $currentStart]);
        $current = $stmt->fetch();

        // Previous period revenue
        $sql = "SELECT COALESCE(SUM(total_amount), 0) as revenue, COUNT(*) as orders
                FROM orders 
                WHERE payment_status = 'paid' 
                AND DATE(created_at) BETWEEN :start_date AND :end_date";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['start_date' => $previousStart, 'end_date' => $previousEnd]);
        $previous = $stmt->fetch();

        $currentRevenue = (float) $current['revenue'];
        $previousRevenue = (float) $previous['revenue'];
        
        $revenueChange = $previousRevenue > 0 
            ? (($currentRevenue - $previousRevenue) / $previousRevenue) * 100 
            : ($currentRevenue > 0 ? 100 : 0);

        $currentOrders = (int) $current['orders'];
        $previousOrders = (int) $previous['orders'];
        
        $ordersChange = $previousOrders > 0 
            ? (($currentOrders - $previousOrders) / $previousOrders) * 100 
            : ($currentOrders > 0 ? 100 : 0);

        return [
            'current_revenue' => $currentRevenue,
            'previous_revenue' => $previousRevenue,
            'revenue_change' => round($revenueChange, 1),
            'current_orders' => $currentOrders,
            'previous_orders' => $previousOrders,
            'orders_change' => round($ordersChange, 1),
            'period' => $period
        ];
    }

    /**
     * Get recent orders for dashboard
     * 
     * @param int $limit Number of orders
     * @return array Recent orders
     */
    public function getRecentOrders(int $limit = 10): array
    {
        $sql = "SELECT 
                    o.*,
                    u.first_name as user_first_name,
                    u.last_name as user_last_name
                FROM orders o
                LEFT JOIN users u ON o.user_id = u.id
                ORDER BY o.created_at DESC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Get chart data for revenue over time
     * 
     * @param int $days Number of days
     * @return array Chart data with labels and values
     */
    public function getRevenueChartData(int $days = 30): array
    {
        $dateFrom = date('Y-m-d', strtotime("-{$days} days"));
        $dateTo = date('Y-m-d');

        $dailyStats = $this->getDailyStats($dateFrom, $dateTo);

        $labels = [];
        $revenue = [];
        $orders = [];

        // Fill in all dates (including those with no orders)
        $currentDate = new DateTime($dateFrom);
        $endDate = new DateTime($dateTo);
        
        $statsMap = [];
        foreach ($dailyStats as $stat) {
            $statsMap[$stat['date']] = $stat;
        }

        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $labels[] = $currentDate->format('M j');
            
            if (isset($statsMap[$dateStr])) {
                $revenue[] = (float) $statsMap[$dateStr]['paid_revenue'];
                $orders[] = (int) $statsMap[$dateStr]['paid_orders'];
            } else {
                $revenue[] = 0;
                $orders[] = 0;
            }
            
            $currentDate->modify('+1 day');
        }

        return [
            'labels' => $labels,
            'revenue' => $revenue,
            'orders' => $orders
        ];
    }
}
