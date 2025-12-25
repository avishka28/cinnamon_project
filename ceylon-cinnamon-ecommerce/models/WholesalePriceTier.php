<?php
/**
 * Wholesale Price Tier Model
 * Handles wholesale pricing tiers based on quantity brackets
 * 
 * Requirements:
 * - 13.3: Display wholesale price tiers based on quantity brackets
 * - 13.4: Show wholesale pricing for wholesale customers
 * - 13.5: Support wholesale-specific product catalogs and minimum order quantities
 */

declare(strict_types=1);

class WholesalePriceTier extends Model
{
    protected string $table = 'wholesale_price_tiers';

    /**
     * Get price tiers for a product
     * Requirement 13.3: Display wholesale price tiers
     * 
     * @param int $productId Product ID
     * @return array Price tiers sorted by min_quantity
     */
    public function getProductTiers(int $productId): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE product_id = :product_id AND is_active = 1 
                ORDER BY min_quantity ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['product_id' => $productId]);
        return $stmt->fetchAll();
    }

    /**
     * Get wholesale price for a product based on quantity
     * Requirement 13.4: Show wholesale pricing
     * 
     * @param int $productId Product ID
     * @param int $quantity Order quantity
     * @return float|null Wholesale price or null if no tier matches
     */
    public function getWholesalePrice(int $productId, int $quantity): ?float
    {
        $sql = "SELECT price FROM {$this->table} 
                WHERE product_id = :product_id 
                AND is_active = 1 
                AND min_quantity <= :quantity 
                ORDER BY min_quantity DESC 
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'product_id' => $productId,
            'quantity' => $quantity
        ]);
        
        $result = $stmt->fetchColumn();
        return $result !== false ? (float) $result : null;
    }

    /**
     * Get minimum order quantity for wholesale
     * Requirement 13.5: Minimum order quantities
     * 
     * @param int $productId Product ID
     * @return int|null Minimum quantity or null
     */
    public function getMinimumQuantity(int $productId): ?int
    {
        $sql = "SELECT MIN(min_quantity) FROM {$this->table} 
                WHERE product_id = :product_id AND is_active = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['product_id' => $productId]);
        
        $result = $stmt->fetchColumn();
        return $result !== false ? (int) $result : null;
    }

    /**
     * Create a price tier for a product
     * 
     * @param array $data Tier data
     * @return int New tier ID
     */
    public function createTier(array $data): int
    {
        $this->validateTierData($data);

        $tierData = [
            'product_id' => $data['product_id'],
            'min_quantity' => $data['min_quantity'],
            'max_quantity' => $data['max_quantity'] ?? null,
            'price' => $data['price'],
            'discount_percentage' => $data['discount_percentage'] ?? null,
            'is_active' => $data['is_active'] ?? 1
        ];

        return $this->create($tierData);
    }

    /**
     * Validate tier data
     * 
     * @param array $data Tier data to validate
     * @throws InvalidArgumentException If validation fails
     */
    private function validateTierData(array $data): void
    {
        $required = ['product_id', 'min_quantity', 'price'];
        
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new InvalidArgumentException("Field '{$field}' is required");
            }
        }

        if (!is_numeric($data['min_quantity']) || $data['min_quantity'] < 1) {
            throw new InvalidArgumentException("Minimum quantity must be at least 1");
        }

        if (!is_numeric($data['price']) || $data['price'] < 0) {
            throw new InvalidArgumentException("Price must be a positive number");
        }
    }

    /**
     * Get all products with wholesale pricing
     * Requirement 13.5: Wholesale-specific product catalogs
     * 
     * @param int $limit Number of results
     * @param int $offset Pagination offset
     * @return array Products with wholesale pricing
     */
    public function getWholesaleProducts(int $limit = 20, int $offset = 0): array
    {
        $sql = "SELECT DISTINCT p.*, c.name as category_name,
                       (SELECT MIN(wpt.min_quantity) FROM {$this->table} wpt 
                        WHERE wpt.product_id = p.id AND wpt.is_active = 1) as min_wholesale_qty,
                       (SELECT MIN(wpt.price) FROM {$this->table} wpt 
                        WHERE wpt.product_id = p.id AND wpt.is_active = 1) as min_wholesale_price
                FROM products p
                INNER JOIN {$this->table} wpt ON p.id = wpt.product_id
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.is_active = 1 AND wpt.is_active = 1
                GROUP BY p.id
                ORDER BY p.name ASC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Delete all tiers for a product
     * 
     * @param int $productId Product ID
     * @return bool Success
     */
    public function deleteProductTiers(int $productId): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE product_id = :product_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['product_id' => $productId]);
    }

    /**
     * Get price tier summary for display
     * 
     * @param int $productId Product ID
     * @return array Formatted tier summary
     */
    public function getTierSummary(int $productId): array
    {
        $tiers = $this->getProductTiers($productId);
        $summary = [];

        foreach ($tiers as $tier) {
            $label = $tier['min_quantity'] . '+';
            if ($tier['max_quantity']) {
                $label = $tier['min_quantity'] . '-' . $tier['max_quantity'];
            }
            
            $summary[] = [
                'label' => $label . ' units',
                'price' => (float) $tier['price'],
                'discount' => $tier['discount_percentage'] ? (float) $tier['discount_percentage'] . '%' : null
            ];
        }

        return $summary;
    }
}
