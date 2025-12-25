<?php
/**
 * Shipping Calculator Service
 * Calculates shipping costs based on destination, weight, and order amount
 * 
 * Requirements:
 * - 14.1: Display shipping rates by country and weight brackets
 * - 14.2: Calculate shipping costs based on destination and weight
 * - 14.3: Support multiple shipping methods with different costs and delivery times
 * - 14.4: Display estimated delivery dates based on shipping method and destination
 */

declare(strict_types=1);

class ShippingCalculator
{
    private ShippingZone $zoneModel;
    private ShippingMethod $methodModel;

    public function __construct()
    {
        $this->zoneModel = new ShippingZone();
        $this->methodModel = new ShippingMethod();
    }

    /**
     * Get available shipping methods for a country
     * Requirement 14.1: Display shipping rates by country
     * 
     * @param string $countryCode ISO country code
     * @param float $weight Total weight in kg
     * @param float $orderAmount Order subtotal
     * @return array Available methods with calculated costs
     */
    public function getAvailableMethods(string $countryCode, float $weight, float $orderAmount): array
    {
        // Find the shipping zone for this country
        $zone = $this->zoneModel->findByCountry($countryCode);
        
        if (!$zone) {
            return [
                'success' => false,
                'error' => 'Shipping is not available to your country',
                'methods' => []
            ];
        }
        
        // Get all active methods for this zone
        $methods = $this->methodModel->getByZone((int) $zone['id']);
        
        if (empty($methods)) {
            return [
                'success' => false,
                'error' => 'No shipping methods available for your location',
                'methods' => []
            ];
        }
        
        // Calculate costs for each method
        $availableMethods = [];
        
        foreach ($methods as $method) {
            $costResult = $this->methodModel->calculateCost(
                (int) $method['id'],
                $weight,
                $orderAmount
            );
            
            if ($costResult['success']) {
                $delivery = $this->methodModel->getEstimatedDelivery((int) $method['id']);
                
                $availableMethods[] = [
                    'id' => $method['id'],
                    'name' => $method['name'],
                    'description' => $method['description'],
                    'cost' => $costResult['cost'],
                    'cost_formatted' => '$' . number_format($costResult['cost'], 2),
                    'free_shipping' => $costResult['free_shipping'],
                    'estimated_delivery' => $delivery,
                    'delivery_text' => $this->formatDeliveryText($delivery)
                ];
            }
        }
        
        // Sort by cost (cheapest first)
        usort($availableMethods, fn($a, $b) => $a['cost'] <=> $b['cost']);
        
        return [
            'success' => true,
            'zone' => $zone['name'],
            'methods' => $availableMethods
        ];
    }

    /**
     * Calculate shipping cost for a specific method
     * Requirement 14.2: Calculate shipping costs based on destination and weight
     * 
     * @param int $methodId Shipping method ID
     * @param float $weight Total weight in kg
     * @param float $orderAmount Order subtotal
     * @return array Calculation result
     */
    public function calculateShipping(int $methodId, float $weight, float $orderAmount): array
    {
        return $this->methodModel->calculateCost($methodId, $weight, $orderAmount);
    }

    /**
     * Calculate total weight from cart items
     * 
     * @param array $items Cart items with product data
     * @return float Total weight in kg
     */
    public function calculateTotalWeight(array $items): float
    {
        $totalWeight = 0.0;
        
        foreach ($items as $item) {
            $weight = (float) ($item['weight'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 1);
            $totalWeight += $weight * $quantity;
        }
        
        return $totalWeight;
    }

    /**
     * Get estimated delivery date for a method
     * Requirement 14.4: Display estimated delivery dates
     * 
     * @param int $methodId Shipping method ID
     * @return array|null Delivery estimate
     */
    public function getDeliveryEstimate(int $methodId): ?array
    {
        return $this->methodModel->getEstimatedDelivery($methodId);
    }

    /**
     * Get shipping rates display for a country
     * Requirement 14.1: Display shipping rates by country and weight brackets
     * 
     * @param string $countryCode ISO country code
     * @return array Shipping rates information
     */
    public function getShippingRatesDisplay(string $countryCode): array
    {
        $zone = $this->zoneModel->findByCountry($countryCode);
        
        if (!$zone) {
            return [
                'available' => false,
                'message' => 'Shipping is not available to this country'
            ];
        }
        
        $methods = $this->methodModel->getByZone((int) $zone['id']);
        $ratesDisplay = [];
        
        foreach ($methods as $method) {
            $methodData = $this->methodModel->getWithBrackets((int) $method['id']);
            
            $rateInfo = [
                'name' => $method['name'],
                'description' => $method['description'],
                'base_cost' => (float) $method['base_cost'],
                'cost_per_kg' => (float) $method['cost_per_kg'],
                'delivery_time' => $this->formatDeliveryText(
                    $this->methodModel->getEstimatedDelivery((int) $method['id'])
                ),
                'weight_brackets' => []
            ];
            
            // Add weight brackets if available
            if (!empty($methodData['weight_brackets'])) {
                foreach ($methodData['weight_brackets'] as $bracket) {
                    $rateInfo['weight_brackets'][] = [
                        'min_weight' => (float) $bracket['min_weight'],
                        'max_weight' => $bracket['max_weight'] !== null 
                            ? (float) $bracket['max_weight'] 
                            : null,
                        'cost' => (float) $bracket['cost'],
                        'range_text' => $this->formatWeightRange(
                            (float) $bracket['min_weight'],
                            $bracket['max_weight'] !== null ? (float) $bracket['max_weight'] : null
                        )
                    ];
                }
            }
            
            // Add free shipping info if applicable
            if ($method['free_shipping_threshold'] !== null) {
                $rateInfo['free_shipping_threshold'] = (float) $method['free_shipping_threshold'];
                $rateInfo['free_shipping_text'] = 'Free shipping on orders over $' . 
                    number_format((float) $method['free_shipping_threshold'], 2);
            }
            
            $ratesDisplay[] = $rateInfo;
        }
        
        return [
            'available' => true,
            'zone_name' => $zone['name'],
            'methods' => $ratesDisplay
        ];
    }

    /**
     * Validate shipping method for order
     * 
     * @param int $methodId Method ID
     * @param string $countryCode Country code
     * @param float $weight Order weight
     * @param float $orderAmount Order amount
     * @return array Validation result
     */
    public function validateShippingMethod(
        int $methodId, 
        string $countryCode, 
        float $weight, 
        float $orderAmount
    ): array {
        // Check if method exists
        $method = $this->methodModel->find($methodId);
        
        if (!$method) {
            return [
                'valid' => false,
                'error' => 'Invalid shipping method'
            ];
        }
        
        // Check if method is active
        if (!$method['is_active']) {
            return [
                'valid' => false,
                'error' => 'Shipping method is not available'
            ];
        }
        
        // Check if method is available for the country
        $zone = $this->zoneModel->find((int) $method['zone_id']);
        
        if (!$zone) {
            return [
                'valid' => false,
                'error' => 'Shipping zone not found'
            ];
        }
        
        $countries = $this->zoneModel->parseCountries($zone['countries']);
        
        if (!in_array(strtoupper($countryCode), $countries)) {
            return [
                'valid' => false,
                'error' => 'Shipping method not available for your country'
            ];
        }
        
        // Calculate cost to verify it's valid
        $costResult = $this->methodModel->calculateCost($methodId, $weight, $orderAmount);
        
        if (!$costResult['success']) {
            return [
                'valid' => false,
                'error' => $costResult['error']
            ];
        }
        
        return [
            'valid' => true,
            'cost' => $costResult['cost'],
            'method_name' => $method['name']
        ];
    }

    /**
     * Format delivery time text
     * 
     * @param array|null $delivery Delivery estimate
     * @return string Formatted text
     */
    private function formatDeliveryText(?array $delivery): string
    {
        if (!$delivery) {
            return 'Delivery time varies';
        }
        
        if ($delivery['min_days'] === $delivery['max_days']) {
            return "{$delivery['min_days']} business days";
        }
        
        return "{$delivery['min_days']}-{$delivery['max_days']} business days";
    }

    /**
     * Format weight range text
     * 
     * @param float $minWeight Minimum weight
     * @param float|null $maxWeight Maximum weight
     * @return string Formatted text
     */
    private function formatWeightRange(float $minWeight, ?float $maxWeight): string
    {
        if ($maxWeight === null) {
            return "Over {$minWeight}kg";
        }
        
        if ($minWeight === 0.0) {
            return "Up to {$maxWeight}kg";
        }
        
        return "{$minWeight}kg - {$maxWeight}kg";
    }

    /**
     * Get all shipping zones with methods (for admin)
     * 
     * @return array All zones with methods
     */
    public function getAllZonesWithMethods(): array
    {
        return $this->zoneModel->getAllWithMethods();
    }

    /**
     * Get cheapest shipping option for a country
     * 
     * @param string $countryCode Country code
     * @param float $weight Order weight
     * @param float $orderAmount Order amount
     * @return array|null Cheapest method or null
     */
    public function getCheapestMethod(string $countryCode, float $weight, float $orderAmount): ?array
    {
        $result = $this->getAvailableMethods($countryCode, $weight, $orderAmount);
        
        if (!$result['success'] || empty($result['methods'])) {
            return null;
        }
        
        // Methods are already sorted by cost
        return $result['methods'][0];
    }
}
