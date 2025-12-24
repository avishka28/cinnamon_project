<?php
/**
 * OrderItem Model
 * Handles order item operations
 * 
 * Requirements:
 * - 5.1: Order items linked to orders
 * - 5.6: Detailed order information
 */

declare(strict_types=1);

class OrderItem extends Model
{
    protected string $table = 'order_items';

    /**
     * Get items for a specific order
     * 
     * @param int $orderId Order ID
     * @return array Order items
     */
    public function getByOrderId(int $orderId): array
    {
        $sql = "SELECT oi.*, p.slug as product_slug, p.is_active as product_active 
                FROM {$this->table} oi 
                LEFT JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = :order_id 
                ORDER BY oi.id ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['order_id' => $orderId]);
        
        return $stmt->fetchAll();
    }

    /**
     * Create order item with validation
     * 
     * @param array $data Item data
     * @return int New item ID
     * @throws InvalidArgumentException If validation fails
     */
    public function createItem(array $data): int
    {
        $this->validateItemData($data);
        
        return $this->create([
            'order_id' => $data['order_id'],
            'product_id' => $data['product_id'],
            'product_name' => $data['product_name'],
            'product_sku' => $data['product_sku'],
            'quantity' => $data['quantity'],
            'price' => $data['price'],
            'total' => (float) $data['price'] * (int) $data['quantity']
        ]);
    }

    /**
     * Get total quantity of a product sold
     * 
     * @param int $productId Product ID
     * @return int Total quantity sold
     */
    public function getTotalSoldByProduct(int $productId): int
    {
        $sql = "SELECT COALESCE(SUM(oi.quantity), 0) as total_sold 
                FROM {$this->table} oi 
                JOIN orders o ON oi.order_id = o.id 
                WHERE oi.product_id = :product_id 
                AND o.order_status NOT IN ('cancelled', 'returned')";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['product_id' => $productId]);
        
        return (int) $stmt->fetchColumn();
    }

    /**
     * Get top selling products
     * 
     * @param int $limit Number of products
     * @return array Top selling products with quantities
     */
    public function getTopSellingProducts(int $limit = 10): array
    {
        $sql = "SELECT oi.product_id, oi.product_name, oi.product_sku, 
                       SUM(oi.quantity) as total_quantity, 
                       SUM(oi.total) as total_revenue 
                FROM {$this->table} oi 
                JOIN orders o ON oi.order_id = o.id 
                WHERE o.order_status NOT IN ('cancelled', 'returned') 
                GROUP BY oi.product_id, oi.product_name, oi.product_sku 
                ORDER BY total_quantity DESC 
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Validate item data
     * 
     * @param array $data Item data
     * @throws InvalidArgumentException If validation fails
     */
    private function validateItemData(array $data): void
    {
        $required = ['order_id', 'product_id', 'product_name', 'product_sku', 'quantity', 'price'];
        
        foreach ($required as $field) {
            if (!isset($data[$field]) || ($data[$field] === '' && $field !== 'price')) {
                throw new InvalidArgumentException("Field '{$field}' is required");
            }
        }

        if ($data['quantity'] < 1) {
            throw new InvalidArgumentException("Quantity must be at least 1");
        }

        if ($data['price'] < 0) {
            throw new InvalidArgumentException("Price must be non-negative");
        }
    }
}
