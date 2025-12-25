<?php
/**
 * Shipping Method Model
 * Handles shipping methods with weight-based pricing
 * 
 * Requirements:
 * - 14.2: Calculate shipping costs based on destination and weight
 * - 14.3: Support multiple shipping methods with different costs and delivery times
 * - 14.4: Display estimated delivery dates
 * - 14.5: Admin shipping rule management
 */

declare(strict_types=1);

class ShippingMethod extends Model
{
    protected string $table = 'shipping_methods';

    /**
     * Get all active methods for a zone
     * 
     * @param int $zoneId Zone ID
     * @return array Active methods
     */
    public function getByZone(int $zoneId): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE zone_id = :zone_id AND is_active = 1 
                ORDER BY sort_order ASC, name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['zone_id' => $zoneId]);
        return $stmt->fetchAll();
    }

    /**
     * Get method with weight brackets
     * 
     * @param int $methodId Method ID
     * @return array|null Method with brackets
     */
    public function getWithBrackets(int $methodId): ?array
    {
        $method = $this->find($methodId);
        
        if (!$method) {
            return null;
        }
        
        $method['weight_brackets'] = $this->getWeightBrackets($methodId);
        return $method;
    }

    /**
     * Get weight brackets for a method
     * 
     * @param int $methodId Method ID
     * @return array Weight brackets
     */
    public function getWeightBrackets(int $methodId): array
    {
        $sql = "SELECT * FROM shipping_weight_brackets 
                WHERE method_id = :method_id 
                ORDER BY min_weight ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['method_id' => $methodId]);
        return $stmt->fetchAll();
    }

    /**
     * Calculate shipping cost for a method
     * Requirement 14.2: Calculate shipping costs based on weight
     * 
     * @param int $methodId Method ID
     * @param float $weight Total weight in kg
     * @param float $orderAmount Order subtotal
     * @return array Cost calculation result
     */
    public function calculateCost(int $methodId, float $weight, float $orderAmount): array
    {
        $method = $this->getWithBrackets($methodId);
        
        if (!$method) {
            return [
                'success' => false,
                'error' => 'Shipping method not found'
            ];
        }
        
        // Check if method is active
        if (!$method['is_active']) {
            return [
                'success' => false,
                'error' => 'Shipping method is not available'
            ];
        }
        
        // Check minimum order amount
        if ($method['min_order_amount'] !== null && $orderAmount < (float) $method['min_order_amount']) {
            return [
                'success' => false,
                'error' => "Minimum order amount of {$method['min_order_amount']} required for this shipping method"
            ];
        }
        
        // Check weight limits
        if ($method['min_weight'] !== null && $weight < (float) $method['min_weight']) {
            return [
                'success' => false,
                'error' => "Minimum weight of {$method['min_weight']}kg required"
            ];
        }
        
        if ($method['max_weight'] !== null && $weight > (float) $method['max_weight']) {
            return [
                'success' => false,
                'error' => "Maximum weight of {$method['max_weight']}kg exceeded"
            ];
        }
        
        // Check for free shipping threshold
        if ($method['free_shipping_threshold'] !== null && $orderAmount >= (float) $method['free_shipping_threshold']) {
            return [
                'success' => true,
                'cost' => 0.00,
                'method_id' => $methodId,
                'method_name' => $method['name'],
                'free_shipping' => true,
                'estimated_days_min' => $method['estimated_days_min'],
                'estimated_days_max' => $method['estimated_days_max']
            ];
        }
        
        // Calculate cost using weight brackets if available
        $cost = $this->calculateCostFromBrackets($method, $weight);
        
        return [
            'success' => true,
            'cost' => round($cost, 2),
            'method_id' => $methodId,
            'method_name' => $method['name'],
            'free_shipping' => false,
            'estimated_days_min' => $method['estimated_days_min'],
            'estimated_days_max' => $method['estimated_days_max']
        ];
    }

    /**
     * Calculate cost from weight brackets or base formula
     * 
     * @param array $method Method data with brackets
     * @param float $weight Weight in kg
     * @return float Calculated cost
     */
    private function calculateCostFromBrackets(array $method, float $weight): float
    {
        $brackets = $method['weight_brackets'] ?? [];
        
        // If weight brackets exist, use them
        if (!empty($brackets)) {
            foreach ($brackets as $bracket) {
                $minWeight = (float) $bracket['min_weight'];
                $maxWeight = $bracket['max_weight'] !== null ? (float) $bracket['max_weight'] : PHP_FLOAT_MAX;
                
                if ($weight >= $minWeight && $weight <= $maxWeight) {
                    return (float) $bracket['cost'];
                }
            }
            
            // If weight exceeds all brackets, use the last bracket's cost
            $lastBracket = end($brackets);
            if ($lastBracket && $weight > (float) $lastBracket['min_weight']) {
                return (float) $lastBracket['cost'];
            }
        }
        
        // Fall back to base cost + per kg calculation
        $baseCost = (float) $method['base_cost'];
        $costPerKg = (float) $method['cost_per_kg'];
        
        return $baseCost + ($costPerKg * $weight);
    }

    /**
     * Get estimated delivery date range
     * Requirement 14.4: Display estimated delivery dates
     * 
     * @param int $methodId Method ID
     * @return array|null Delivery date range
     */
    public function getEstimatedDelivery(int $methodId): ?array
    {
        $method = $this->find($methodId);
        
        if (!$method || $method['estimated_days_min'] === null) {
            return null;
        }
        
        $minDays = (int) $method['estimated_days_min'];
        $maxDays = $method['estimated_days_max'] !== null 
            ? (int) $method['estimated_days_max'] 
            : $minDays;
        
        $today = new DateTime();
        
        return [
            'min_days' => $minDays,
            'max_days' => $maxDays,
            'min_date' => (clone $today)->modify("+{$minDays} days")->format('Y-m-d'),
            'max_date' => (clone $today)->modify("+{$maxDays} days")->format('Y-m-d'),
            'min_date_formatted' => (clone $today)->modify("+{$minDays} days")->format('M j, Y'),
            'max_date_formatted' => (clone $today)->modify("+{$maxDays} days")->format('M j, Y')
        ];
    }

    /**
     * Create a new shipping method
     * Requirement 14.5: Admin shipping rule management
     * 
     * @param array $data Method data
     * @return int New method ID
     */
    public function createMethod(array $data): int
    {
        $this->validateMethodData($data);
        
        return $this->create([
            'zone_id' => $data['zone_id'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'base_cost' => $data['base_cost'] ?? 0.00,
            'cost_per_kg' => $data['cost_per_kg'] ?? 0.00,
            'min_weight' => $data['min_weight'] ?? null,
            'max_weight' => $data['max_weight'] ?? null,
            'min_order_amount' => $data['min_order_amount'] ?? null,
            'free_shipping_threshold' => $data['free_shipping_threshold'] ?? null,
            'estimated_days_min' => $data['estimated_days_min'] ?? null,
            'estimated_days_max' => $data['estimated_days_max'] ?? null,
            'is_active' => $data['is_active'] ?? 1,
            'sort_order' => $data['sort_order'] ?? 0
        ]);
    }

    /**
     * Update a shipping method
     * 
     * @param int $id Method ID
     * @param array $data Method data
     * @return bool Success
     */
    public function updateMethod(int $id, array $data): bool
    {
        $updateData = [];
        
        $fields = [
            'name', 'description', 'base_cost', 'cost_per_kg',
            'min_weight', 'max_weight', 'min_order_amount',
            'free_shipping_threshold', 'estimated_days_min',
            'estimated_days_max', 'is_active', 'sort_order'
        ];
        
        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field];
            }
        }
        
        return $this->update($id, $updateData);
    }

    /**
     * Add weight bracket to a method
     * 
     * @param int $methodId Method ID
     * @param array $bracket Bracket data
     * @return int New bracket ID
     */
    public function addWeightBracket(int $methodId, array $bracket): int
    {
        $sql = "INSERT INTO shipping_weight_brackets (method_id, min_weight, max_weight, cost) 
                VALUES (:method_id, :min_weight, :max_weight, :cost)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'method_id' => $methodId,
            'min_weight' => $bracket['min_weight'],
            'max_weight' => $bracket['max_weight'] ?? null,
            'cost' => $bracket['cost']
        ]);
        
        return (int) $this->db->lastInsertId();
    }

    /**
     * Update weight bracket
     * 
     * @param int $bracketId Bracket ID
     * @param array $data Bracket data
     * @return bool Success
     */
    public function updateWeightBracket(int $bracketId, array $data): bool
    {
        $sql = "UPDATE shipping_weight_brackets 
                SET min_weight = :min_weight, max_weight = :max_weight, cost = :cost 
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $bracketId,
            'min_weight' => $data['min_weight'],
            'max_weight' => $data['max_weight'] ?? null,
            'cost' => $data['cost']
        ]);
    }

    /**
     * Delete weight bracket
     * 
     * @param int $bracketId Bracket ID
     * @return bool Success
     */
    public function deleteWeightBracket(int $bracketId): bool
    {
        $sql = "DELETE FROM shipping_weight_brackets WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['id' => $bracketId]);
    }

    /**
     * Clear all weight brackets for a method
     * 
     * @param int $methodId Method ID
     * @return bool Success
     */
    public function clearWeightBrackets(int $methodId): bool
    {
        $sql = "DELETE FROM shipping_weight_brackets WHERE method_id = :method_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['method_id' => $methodId]);
    }

    /**
     * Validate method data
     * 
     * @param array $data Method data
     * @throws InvalidArgumentException If validation fails
     */
    private function validateMethodData(array $data): void
    {
        if (empty($data['zone_id'])) {
            throw new InvalidArgumentException("Zone ID is required");
        }
        
        if (empty($data['name'])) {
            throw new InvalidArgumentException("Method name is required");
        }
        
        if (isset($data['base_cost']) && $data['base_cost'] < 0) {
            throw new InvalidArgumentException("Base cost cannot be negative");
        }
        
        if (isset($data['cost_per_kg']) && $data['cost_per_kg'] < 0) {
            throw new InvalidArgumentException("Cost per kg cannot be negative");
        }
    }
}
