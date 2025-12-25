<?php
/**
 * Order Model
 * Handles order creation, tracking, and management with transaction support
 * 
 * Requirements:
 * - 5.1: Assign unique order number
 * - 5.2: Order tracking by order number and email
 * - 5.4: Order statuses (Pending, Processing, Shipped, Delivered, Cancelled, Returned)
 * - 3.5: Create order record on successful payment
 * - 3.7: Reduce product stock on order placement
 * - 7.6: Restore stock on order cancellation
 */

declare(strict_types=1);

class Order extends Model
{
    protected string $table = 'orders';

    /** @var array Valid order statuses */
    public const STATUSES = [
        'pending',
        'processing', 
        'shipped',
        'delivered',
        'cancelled',
        'returned'
    ];

    /** @var array Valid payment statuses */
    public const PAYMENT_STATUSES = [
        'pending',
        'paid',
        'failed',
        'refunded'
    ];

    /** @var array Valid payment methods */
    public const PAYMENT_METHODS = [
        'stripe',
        'paypal',
        'bank_transfer'
    ];

    /**
     * Create a new order with items using transaction support
     * Requirements: 5.1, 3.5, 3.7
     * 
     * @param array $orderData Order details
     * @param array $items Order items with product_id, quantity, price
     * @return string Order number on success
     * @throws Exception On failure
     */
    public function createOrder(array $orderData, array $items): string
    {
        $this->validateOrderData($orderData);
        $this->validateOrderItems($items);

        $this->beginTransaction();

        try {
            // Generate unique order number (Requirement 5.1)
            $orderNumber = $this->generateOrderNumber();

            // Calculate totals
            $subtotal = 0.0;
            foreach ($items as $item) {
                $subtotal += (float) $item['price'] * (int) $item['quantity'];
            }

            $shippingCost = (float) ($orderData['shipping_cost'] ?? 0.00);
            $taxAmount = (float) ($orderData['tax_amount'] ?? 0.00);
            $totalAmount = $subtotal + $shippingCost + $taxAmount;

            // Create order record - build data array with only non-null values
            $orderRecord = [
                'order_number' => $orderNumber,
                'email' => $orderData['email'],
                'first_name' => $orderData['first_name'],
                'last_name' => $orderData['last_name'],
                'shipping_address' => $orderData['shipping_address'],
                'subtotal' => $subtotal,
                'shipping_cost' => $shippingCost,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'payment_method' => $orderData['payment_method'],
                'payment_status' => $orderData['payment_status'] ?? 'pending',
                'order_status' => 'pending'
            ];

            // Add optional fields only if they have values
            if (!empty($orderData['user_id'])) {
                $orderRecord['user_id'] = $orderData['user_id'];
            }
            if (!empty($orderData['phone'])) {
                $orderRecord['phone'] = $orderData['phone'];
            }
            if (!empty($orderData['billing_address'])) {
                $orderRecord['billing_address'] = $orderData['billing_address'];
            }
            if (!empty($orderData['notes'])) {
                $orderRecord['notes'] = $orderData['notes'];
            }

            $orderId = $this->create($orderRecord);

            // Create order items and reduce stock (Requirement 3.7)
            $orderItemModel = new OrderItem();
            $productModel = new Product();

            foreach ($items as $item) {
                // Get product details for order item record
                $product = $productModel->find((int) $item['product_id']);
                if (!$product) {
                    throw new Exception("Product not found: {$item['product_id']}");
                }

                // Check stock availability
                if ($product['stock_quantity'] < $item['quantity']) {
                    throw new Exception("Insufficient stock for product: {$product['name']}");
                }

                // Create order item
                $orderItemModel->create([
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'product_name' => $product['name'],
                    'product_sku' => $product['sku'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'total' => (float) $item['price'] * (int) $item['quantity']
                ]);

                // Reduce stock (Requirement 3.7)
                $this->reduceStock((int) $item['product_id'], (int) $item['quantity']);
            }

            $this->commit();
            return $orderNumber;

        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * Generate a unique order number
     * Requirement 5.1: Assign unique order number
     * 
     * Format: CC + Year + Random 6 digits (e.g., CC2025123456)
     * 
     * @return string Unique order number
     */
    public function generateOrderNumber(): string
    {
        $maxAttempts = 100;
        $attempt = 0;

        do {
            $orderNumber = 'CC' . date('Y') . str_pad((string) mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
            $attempt++;

            // Check if order number already exists
            $existing = $this->findByOrderNumber($orderNumber);
            
            if (!$existing) {
                return $orderNumber;
            }
        } while ($attempt < $maxAttempts);

        // Fallback with timestamp for guaranteed uniqueness
        return 'CC' . date('Y') . substr((string) time(), -6);
    }

    /**
     * Find order by order number
     * 
     * @param string $orderNumber Order number
     * @return array|null Order data or null
     */
    public function findByOrderNumber(string $orderNumber): ?array
    {
        return $this->findBy('order_number', $orderNumber);
    }

    /**
     * Track order by order number and email
     * Requirement 5.2: Order tracking
     * 
     * @param string $orderNumber Order number
     * @param string $email Customer email
     * @return array|null Order with items or null
     */
    public function trackOrder(string $orderNumber, string $email): ?array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE order_number = :order_number AND email = :email";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'order_number' => $orderNumber,
            'email' => $email
        ]);
        
        $order = $stmt->fetch();
        
        if (!$order) {
            return null;
        }

        // Get order items
        $order['items'] = $this->getOrderItems((int) $order['id']);
        
        return $order;
    }

    /**
     * Get order items for an order
     * 
     * @param int $orderId Order ID
     * @return array Order items
     */
    public function getOrderItems(int $orderId): array
    {
        $sql = "SELECT oi.*, p.slug as product_slug 
                FROM order_items oi 
                LEFT JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = :order_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['order_id' => $orderId]);
        
        return $stmt->fetchAll();
    }

    /**
     * Get full order details with items
     * 
     * @param int $orderId Order ID
     * @return array|null Order with items or null
     */
    public function getFullDetails(int $orderId): ?array
    {
        $order = $this->find($orderId);
        
        if (!$order) {
            return null;
        }

        $order['items'] = $this->getOrderItems($orderId);
        
        return $order;
    }

    /**
     * Update order status
     * Requirement 5.4: Order statuses
     * Requirement 12.2: Send status update email when order status changes
     * 
     * @param int $orderId Order ID
     * @param string $status New status
     * @param bool $sendNotification Whether to send email notification
     * @return bool Success
     * @throws InvalidArgumentException If status is invalid
     */
    public function updateStatus(int $orderId, string $status, bool $sendNotification = true): bool
    {
        if (!in_array($status, self::STATUSES)) {
            throw new InvalidArgumentException("Invalid order status: {$status}");
        }

        // Get current status for notification
        $order = $this->find($orderId);
        $oldStatus = $order ? $order['order_status'] : null;

        $result = $this->update($orderId, ['order_status' => $status]);

        // Send notification if status changed and notifications enabled
        if ($result && $sendNotification && $oldStatus !== $status) {
            $this->sendStatusNotification($orderId, $oldStatus, $status);
        }

        return $result;
    }

    /**
     * Send status change notification
     * Requirement 12.2: Send status update email when order status changes
     * 
     * @param int $orderId Order ID
     * @param string|null $oldStatus Previous status
     * @param string $newStatus New status
     * @param array $additionalData Additional data (tracking info, etc.)
     */
    private function sendStatusNotification(int $orderId, ?string $oldStatus, string $newStatus, array $additionalData = []): void
    {
        try {
            $notificationService = new OrderNotificationService();
            $notificationService->notifyStatusChange($orderId, $oldStatus ?? '', $newStatus, $additionalData);
        } catch (Exception $e) {
            // Log error but don't fail the status update
            error_log("Failed to send status notification for order {$orderId}: " . $e->getMessage());
        }
    }

    /**
     * Update payment status
     * 
     * @param int $orderId Order ID
     * @param string $status New payment status
     * @return bool Success
     * @throws InvalidArgumentException If status is invalid
     */
    public function updatePaymentStatus(int $orderId, string $status): bool
    {
        if (!in_array($status, self::PAYMENT_STATUSES)) {
            throw new InvalidArgumentException("Invalid payment status: {$status}");
        }

        return $this->update($orderId, ['payment_status' => $status]);
    }

    /**
     * Mark order as shipped with tracking information
     * Requirement 12.3: Send shipping notification with tracking information
     * 
     * @param int $orderId Order ID
     * @param string|null $trackingNumber Tracking number
     * @param string|null $carrier Shipping carrier
     * @param string|null $trackingUrl Tracking URL
     * @return bool Success
     */
    public function markAsShipped(
        int $orderId, 
        ?string $trackingNumber = null, 
        ?string $carrier = null, 
        ?string $trackingUrl = null
    ): bool {
        $order = $this->find($orderId);
        
        if (!$order) {
            return false;
        }

        $oldStatus = $order['order_status'];
        $result = $this->update($orderId, ['order_status' => 'shipped']);

        if ($result) {
            // Send shipping notification with tracking info
            try {
                $notificationService = new OrderNotificationService();
                $notificationService->sendShippingNotification(
                    $order['order_number'],
                    $trackingNumber,
                    $carrier,
                    $trackingUrl
                );
            } catch (Exception $e) {
                error_log("Failed to send shipping notification for order {$orderId}: " . $e->getMessage());
            }
        }

        return $result;
    }

    /**
     * Cancel an order and restore stock
     * Requirement 7.6: Restore stock on cancellation
     * Requirement 12.2: Send cancellation notification
     * 
     * @param int $orderId Order ID
     * @param string|null $reason Cancellation reason
     * @param bool $sendNotification Whether to send email notification
     * @return bool Success
     * @throws Exception On failure
     */
    public function cancelOrder(int $orderId, ?string $reason = null, bool $sendNotification = true): bool
    {
        $order = $this->find($orderId);
        
        if (!$order) {
            throw new Exception("Order not found");
        }

        if ($order['order_status'] === 'cancelled') {
            return true; // Already cancelled
        }

        if (in_array($order['order_status'], ['shipped', 'delivered'])) {
            throw new Exception("Cannot cancel order that has been shipped or delivered");
        }

        $this->beginTransaction();

        try {
            // Restore stock for all items (Requirement 7.6)
            $items = $this->getOrderItems($orderId);
            
            foreach ($items as $item) {
                $this->restoreStock((int) $item['product_id'], (int) $item['quantity']);
            }

            // Update order status
            $this->update($orderId, ['order_status' => 'cancelled']);

            $this->commit();

            // Send cancellation notification (Requirement 12.2)
            if ($sendNotification) {
                $this->sendStatusNotification($orderId, $order['order_status'], 'cancelled', ['reason' => $reason]);
            }

            return true;

        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * Reduce product stock
     * Requirement 3.7: Reduce stock on order placement
     * 
     * @param int $productId Product ID
     * @param int $quantity Quantity to reduce
     * @return bool Success
     */
    public function reduceStock(int $productId, int $quantity): bool
    {
        $sql = "UPDATE products 
                SET stock_quantity = stock_quantity - :quantity 
                WHERE id = :id AND stock_quantity >= :quantity_check";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            'id' => $productId,
            'quantity' => $quantity,
            'quantity_check' => $quantity
        ]);

        if ($stmt->rowCount() === 0) {
            throw new Exception("Failed to reduce stock - insufficient quantity");
        }

        return $result;
    }

    /**
     * Restore product stock
     * Requirement 7.6: Restore stock on cancellation
     * 
     * @param int $productId Product ID
     * @param int $quantity Quantity to restore
     * @return bool Success
     */
    public function restoreStock(int $productId, int $quantity): bool
    {
        $sql = "UPDATE products 
                SET stock_quantity = stock_quantity + :quantity 
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $productId,
            'quantity' => $quantity
        ]);
    }

    /**
     * Get orders by user ID
     * Requirement 5.5: Order history
     * 
     * @param int $userId User ID
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array Orders
     */
    public function getByUserId(int $userId, int $limit = 20, int $offset = 0): array
    {
        $sql = "SELECT o.*, 
                       (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
                FROM {$this->table} o
                WHERE user_id = :user_id 
                ORDER BY created_at DESC 
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Count orders by user ID
     * 
     * @param int $userId User ID
     * @return int Order count
     */
    public function countByUserId(int $userId): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Get user order statistics
     * 
     * @param int $userId User ID
     * @return array Statistics
     */
    public function getUserStats(int $userId): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN order_status = 'delivered' THEN 1 ELSE 0 END) as completed_orders,
                    SUM(CASE WHEN order_status IN ('pending', 'processing', 'shipped') THEN 1 ELSE 0 END) as pending_orders,
                    COALESCE(SUM(total_amount), 0) as total_spent
                FROM {$this->table} 
                WHERE user_id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetch() ?: [
            'total_orders' => 0,
            'completed_orders' => 0,
            'pending_orders' => 0,
            'total_spent' => 0
        ];
    }

    /**
     * Get orders with filtering and pagination (for admin)
     * Requirement 7.1: Order listing with filtering
     * 
     * @param array $filters Filter options
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array Orders with pagination info
     */
    public function getFiltered(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $sql = "SELECT o.*, u.email as user_email 
                FROM {$this->table} o 
                LEFT JOIN users u ON o.user_id = u.id 
                WHERE 1=1";
        
        $params = [];

        if (!empty($filters['order_status'])) {
            $sql .= " AND o.order_status = :order_status";
            $params['order_status'] = $filters['order_status'];
        }

        if (!empty($filters['payment_status'])) {
            $sql .= " AND o.payment_status = :payment_status";
            $params['payment_status'] = $filters['payment_status'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND o.created_at >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND o.created_at <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (o.order_number LIKE :search OR o.email LIKE :search OR o.first_name LIKE :search OR o.last_name LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        // Get total count
        $countSql = preg_replace('/SELECT .* FROM/', 'SELECT COUNT(*) FROM', $sql);
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Add sorting and pagination
        $sql .= " ORDER BY o.created_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'orders' => $stmt->fetchAll(),
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'pages' => (int) ceil($total / $limit)
        ];
    }

    /**
     * Add note to order
     * Requirement 7.5: Order notes
     * 
     * @param int $orderId Order ID
     * @param string $note Note text
     * @return bool Success
     */
    public function addNote(int $orderId, string $note): bool
    {
        $order = $this->find($orderId);
        
        if (!$order) {
            return false;
        }

        $existingNotes = $order['notes'] ?? '';
        $timestamp = date('Y-m-d H:i:s');
        $newNote = "[{$timestamp}] {$note}";
        
        $updatedNotes = $existingNotes 
            ? $existingNotes . "\n" . $newNote 
            : $newNote;

        return $this->update($orderId, ['notes' => $updatedNotes]);
    }

    /**
     * Validate order data
     * 
     * @param array $data Order data
     * @throws InvalidArgumentException If validation fails
     */
    private function validateOrderData(array $data): void
    {
        $required = ['email', 'first_name', 'last_name', 'shipping_address', 'payment_method'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new InvalidArgumentException("Field '{$field}' is required");
            }
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email address");
        }

        if (!in_array($data['payment_method'], self::PAYMENT_METHODS)) {
            throw new InvalidArgumentException("Invalid payment method");
        }
    }

    /**
     * Validate order items
     * 
     * @param array $items Order items
     * @throws InvalidArgumentException If validation fails
     */
    private function validateOrderItems(array $items): void
    {
        if (empty($items)) {
            throw new InvalidArgumentException("Order must have at least one item");
        }

        foreach ($items as $index => $item) {
            if (empty($item['product_id'])) {
                throw new InvalidArgumentException("Item {$index}: product_id is required");
            }
            if (empty($item['quantity']) || $item['quantity'] < 1) {
                throw new InvalidArgumentException("Item {$index}: quantity must be at least 1");
            }
            if (!isset($item['price']) || $item['price'] < 0) {
                throw new InvalidArgumentException("Item {$index}: price must be non-negative");
            }
        }
    }
}
