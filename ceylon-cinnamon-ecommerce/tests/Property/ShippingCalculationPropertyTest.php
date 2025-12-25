<?php
/**
 * Property-Based Tests for Shipping Calculations
 * 
 * Feature: ceylon-cinnamon-ecommerce
 * Property 31: Shipping rate calculation accuracy
 * 
 * Validates: Requirements 14.2
 * 
 * For any shipping method, weight, and order amount, the calculated shipping cost
 * should be accurate based on the method's pricing rules (base cost, per-kg cost,
 * weight brackets, and free shipping thresholds).
 */

declare(strict_types=1);

namespace Tests\Property;

use PHPUnit\Framework\TestCase;
use Eris\Generator;
use Eris\TestTrait;

class ShippingCalculationPropertyTest extends TestCase
{
    use TestTrait;

    private \PDO $db;
    private \ShippingZone $zoneModel;
    private \ShippingMethod $methodModel;
    private \ShippingCalculator $calculator;
    private bool $dbAvailable = false;
    private array $testZoneIds = [];
    private array $testMethodIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        
        try {
            $_ENV['DB_NAME'] = $_ENV['DB_NAME'] ?? 'ceylon_cinnamon_test';
            require_once __DIR__ . '/../../config/database.php';
            require_once __DIR__ . '/../../includes/Model.php';
            require_once __DIR__ . '/../../models/ShippingZone.php';
            require_once __DIR__ . '/../../models/ShippingMethod.php';
            require_once __DIR__ . '/../../includes/ShippingCalculator.php';
            
            $this->db = \Database::getInstance();
            $this->zoneModel = new \ShippingZone();
            $this->methodModel = new \ShippingMethod();
            $this->calculator = new \ShippingCalculator();
            $this->dbAvailable = true;
            $this->setupTestData();
        } catch (\Exception $e) {
            $this->dbAvailable = false;
        }
    }

    protected function tearDown(): void
    {
        if ($this->dbAvailable) {
            $this->cleanupTestData();
            \Database::closeConnection();
        }
        parent::tearDown();
    }

    private function setupTestData(): void
    {
        // Create test shipping zone
        $zoneId = $this->zoneModel->createZone([
            'name' => 'Test Zone ' . uniqid(),
            'countries' => ['US', 'CA', 'GB'],
            'is_active' => 1
        ]);
        $this->testZoneIds[] = $zoneId;

        // Create test shipping methods with different pricing structures
        
        // Method 1: Base cost + per kg pricing
        $method1Id = $this->methodModel->createMethod([
            'zone_id' => $zoneId,
            'name' => 'Standard Shipping',
            'base_cost' => 5.00,
            'cost_per_kg' => 2.50,
            'estimated_days_min' => 5,
            'estimated_days_max' => 10,
            'is_active' => 1
        ]);
        $this->testMethodIds[] = $method1Id;

        // Method 2: With free shipping threshold
        $method2Id = $this->methodModel->createMethod([
            'zone_id' => $zoneId,
            'name' => 'Express Shipping',
            'base_cost' => 15.00,
            'cost_per_kg' => 5.00,
            'free_shipping_threshold' => 100.00,
            'estimated_days_min' => 2,
            'estimated_days_max' => 4,
            'is_active' => 1
        ]);
        $this->testMethodIds[] = $method2Id;

        // Method 3: With weight brackets
        $method3Id = $this->methodModel->createMethod([
            'zone_id' => $zoneId,
            'name' => 'Weight Bracket Shipping',
            'base_cost' => 0.00,
            'cost_per_kg' => 0.00,
            'estimated_days_min' => 7,
            'estimated_days_max' => 14,
            'is_active' => 1
        ]);
        $this->testMethodIds[] = $method3Id;

        // Add weight brackets to method 3
        $this->methodModel->addWeightBracket($method3Id, [
            'min_weight' => 0,
            'max_weight' => 1.0,
            'cost' => 8.00
        ]);
        $this->methodModel->addWeightBracket($method3Id, [
            'min_weight' => 1.0,
            'max_weight' => 5.0,
            'cost' => 15.00
        ]);
        $this->methodModel->addWeightBracket($method3Id, [
            'min_weight' => 5.0,
            'max_weight' => null,
            'cost' => 25.00
        ]);

        // Method 4: With weight limits
        $method4Id = $this->methodModel->createMethod([
            'zone_id' => $zoneId,
            'name' => 'Limited Weight Shipping',
            'base_cost' => 10.00,
            'cost_per_kg' => 3.00,
            'min_weight' => 0.5,
            'max_weight' => 10.0,
            'is_active' => 1
        ]);
        $this->testMethodIds[] = $method4Id;
    }

    private function cleanupTestData(): void
    {
        // Delete test methods (will cascade delete brackets)
        foreach ($this->testMethodIds as $id) {
            try {
                $this->methodModel->delete($id);
            } catch (\Exception $e) {
                // Ignore cleanup errors
            }
        }
        
        // Delete test zones
        foreach ($this->testZoneIds as $id) {
            try {
                $this->zoneModel->delete($id);
            } catch (\Exception $e) {
                // Ignore cleanup errors
            }
        }
    }

    /**
     * Feature: ceylon-cinnamon-ecommerce, Property 31: Shipping rate calculation accuracy
     * 
     * For any shipping method with base cost and per-kg pricing, the calculated
     * shipping cost should equal base_cost + (weight * cost_per_kg).
     * 
     * Validates: Requirements 14.2
     */
    public function testBaseAndPerKgCalculationAccuracy(): void
    {
        if (!$this->dbAvailable) {
            $this->markTestSkipped('Database not available for testing');
        }

        // Method 1: Standard Shipping (base_cost: 5.00, cost_per_kg: 2.50)
        $methodId = $this->testMethodIds[0];
        $method = $this->methodModel->find($methodId);

        $this->limitTo(100)
            ->forAll(
                Generator\float()->between(0.1, 50.0),  // weight in kg
                Generator\float()->between(10.0, 500.0) // order amount
            )
            ->then(function (float $weight, float $orderAmount) use ($methodId, $method): void {
                $result = $this->methodModel->calculateCost($methodId, $weight, $orderAmount);
                
                $this->assertTrue($result['success'], 'Calculation should succeed');
                
                // Expected cost = base_cost + (weight * cost_per_kg)
                $expectedCost = (float) $method['base_cost'] + ($weight * (float) $method['cost_per_kg']);
                $expectedCost = round($expectedCost, 2);
                
                $this->assertEquals(
                    $expectedCost,
                    $result['cost'],
                    "Shipping cost for {$weight}kg should be \${$expectedCost}"
                );
            });
    }

    /**
     * Feature: ceylon-cinnamon-ecommerce, Property 31: Shipping rate calculation accuracy
     * 
     * For any order amount above the free shipping threshold, the shipping cost
     * should be zero.
     * 
     * Validates: Requirements 14.2
     */
    public function testFreeShippingThresholdAccuracy(): void
    {
        if (!$this->dbAvailable) {
            $this->markTestSkipped('Database not available for testing');
        }

        // Method 2: Express Shipping (free_shipping_threshold: 100.00)
        $methodId = $this->testMethodIds[1];
        $method = $this->methodModel->find($methodId);
        $threshold = (float) $method['free_shipping_threshold'];

        $this->limitTo(100)
            ->forAll(
                Generator\float()->between(0.1, 20.0),  // weight in kg
                Generator\float()->between($threshold, $threshold + 500.0) // order amount above threshold
            )
            ->then(function (float $weight, float $orderAmount) use ($methodId, $threshold): void {
                $result = $this->methodModel->calculateCost($methodId, $weight, $orderAmount);
                
                $this->assertTrue($result['success'], 'Calculation should succeed');
                $this->assertTrue($result['free_shipping'], 'Should qualify for free shipping');
                $this->assertEquals(
                    0.00,
                    $result['cost'],
                    "Orders over \${$threshold} should have free shipping"
                );
            });
    }

    /**
     * Feature: ceylon-cinnamon-ecommerce, Property 31: Shipping rate calculation accuracy
     * 
     * For any order amount below the free shipping threshold, the shipping cost
     * should be calculated normally (not free).
     * 
     * Validates: Requirements 14.2
     */
    public function testBelowFreeShippingThreshold(): void
    {
        if (!$this->dbAvailable) {
            $this->markTestSkipped('Database not available for testing');
        }

        // Method 2: Express Shipping (free_shipping_threshold: 100.00)
        $methodId = $this->testMethodIds[1];
        $method = $this->methodModel->find($methodId);
        $threshold = (float) $method['free_shipping_threshold'];

        $this->limitTo(100)
            ->forAll(
                Generator\float()->between(0.1, 20.0),  // weight in kg
                Generator\float()->between(10.0, $threshold - 0.01) // order amount below threshold
            )
            ->then(function (float $weight, float $orderAmount) use ($methodId, $method): void {
                $result = $this->methodModel->calculateCost($methodId, $weight, $orderAmount);
                
                $this->assertTrue($result['success'], 'Calculation should succeed');
                $this->assertFalse($result['free_shipping'], 'Should not qualify for free shipping');
                
                // Expected cost = base_cost + (weight * cost_per_kg)
                $expectedCost = (float) $method['base_cost'] + ($weight * (float) $method['cost_per_kg']);
                $expectedCost = round($expectedCost, 2);
                
                $this->assertEquals(
                    $expectedCost,
                    $result['cost'],
                    "Shipping cost should be calculated normally below threshold"
                );
            });
    }

    /**
     * Feature: ceylon-cinnamon-ecommerce, Property 31: Shipping rate calculation accuracy
     * 
     * For any weight within a defined bracket, the shipping cost should match
     * the bracket's defined cost.
     * 
     * Validates: Requirements 14.2
     */
    public function testWeightBracketCalculationAccuracy(): void
    {
        if (!$this->dbAvailable) {
            $this->markTestSkipped('Database not available for testing');
        }

        // Method 3: Weight Bracket Shipping
        $methodId = $this->testMethodIds[2];

        // Test bracket 1: 0-1kg = $8.00
        $this->limitTo(50)
            ->forAll(
                Generator\float()->between(0.01, 0.99),
                Generator\float()->between(10.0, 200.0)
            )
            ->then(function (float $weight, float $orderAmount) use ($methodId): void {
                $result = $this->methodModel->calculateCost($methodId, $weight, $orderAmount);
                
                $this->assertTrue($result['success'], 'Calculation should succeed');
                $this->assertEquals(
                    8.00,
                    $result['cost'],
                    "Weight {$weight}kg should cost \$8.00 (bracket 0-1kg)"
                );
            });

        // Test bracket 2: 1-5kg = $15.00
        $this->limitTo(50)
            ->forAll(
                Generator\float()->between(1.0, 4.99),
                Generator\float()->between(10.0, 200.0)
            )
            ->then(function (float $weight, float $orderAmount) use ($methodId): void {
                $result = $this->methodModel->calculateCost($methodId, $weight, $orderAmount);
                
                $this->assertTrue($result['success'], 'Calculation should succeed');
                $this->assertEquals(
                    15.00,
                    $result['cost'],
                    "Weight {$weight}kg should cost \$15.00 (bracket 1-5kg)"
                );
            });

        // Test bracket 3: 5kg+ = $25.00
        $this->limitTo(50)
            ->forAll(
                Generator\float()->between(5.0, 50.0),
                Generator\float()->between(10.0, 200.0)
            )
            ->then(function (float $weight, float $orderAmount) use ($methodId): void {
                $result = $this->methodModel->calculateCost($methodId, $weight, $orderAmount);
                
                $this->assertTrue($result['success'], 'Calculation should succeed');
                $this->assertEquals(
                    25.00,
                    $result['cost'],
                    "Weight {$weight}kg should cost \$25.00 (bracket 5kg+)"
                );
            });
    }

    /**
     * Feature: ceylon-cinnamon-ecommerce, Property 31: Shipping rate calculation accuracy
     * 
     * For any weight outside the method's weight limits, the calculation should fail.
     * 
     * Validates: Requirements 14.2
     */
    public function testWeightLimitsEnforcement(): void
    {
        if (!$this->dbAvailable) {
            $this->markTestSkipped('Database not available for testing');
        }

        // Method 4: Limited Weight Shipping (min: 0.5kg, max: 10.0kg)
        $methodId = $this->testMethodIds[3];

        // Test below minimum weight
        $this->limitTo(50)
            ->forAll(
                Generator\float()->between(0.01, 0.49),
                Generator\float()->between(10.0, 200.0)
            )
            ->then(function (float $weight, float $orderAmount) use ($methodId): void {
                $result = $this->methodModel->calculateCost($methodId, $weight, $orderAmount);
                
                $this->assertFalse(
                    $result['success'],
                    "Weight {$weight}kg below minimum should fail"
                );
            });

        // Test above maximum weight
        $this->limitTo(50)
            ->forAll(
                Generator\float()->between(10.01, 50.0),
                Generator\float()->between(10.0, 200.0)
            )
            ->then(function (float $weight, float $orderAmount) use ($methodId): void {
                $result = $this->methodModel->calculateCost($methodId, $weight, $orderAmount);
                
                $this->assertFalse(
                    $result['success'],
                    "Weight {$weight}kg above maximum should fail"
                );
            });

        // Test within weight limits
        $this->limitTo(50)
            ->forAll(
                Generator\float()->between(0.5, 10.0),
                Generator\float()->between(10.0, 200.0)
            )
            ->then(function (float $weight, float $orderAmount) use ($methodId): void {
                $result = $this->methodModel->calculateCost($methodId, $weight, $orderAmount);
                
                $this->assertTrue(
                    $result['success'],
                    "Weight {$weight}kg within limits should succeed"
                );
            });
    }

    /**
     * Feature: ceylon-cinnamon-ecommerce, Property 31: Shipping rate calculation accuracy
     * 
     * For any valid calculation, the cost should always be non-negative.
     * 
     * Validates: Requirements 14.2
     */
    public function testShippingCostNonNegative(): void
    {
        if (!$this->dbAvailable) {
            $this->markTestSkipped('Database not available for testing');
        }

        // Test with method 1 (Standard Shipping)
        $methodId = $this->testMethodIds[0];

        $this->limitTo(100)
            ->forAll(
                Generator\float()->between(0.1, 100.0),
                Generator\float()->between(1.0, 1000.0)
            )
            ->then(function (float $weight, float $orderAmount) use ($methodId): void {
                $result = $this->methodModel->calculateCost($methodId, $weight, $orderAmount);
                
                if ($result['success']) {
                    $this->assertGreaterThanOrEqual(
                        0,
                        $result['cost'],
                        "Shipping cost should never be negative"
                    );
                }
            });
    }

    /**
     * Test that available methods for a country are correctly filtered.
     * 
     * For any country in a zone, the calculator should return all active
     * methods for that zone.
     */
    public function testAvailableMethodsForCountry(): void
    {
        if (!$this->dbAvailable) {
            $this->markTestSkipped('Database not available for testing');
        }

        $this->limitTo(10)
            ->forAll(
                Generator\elements('US', 'CA', 'GB'),
                Generator\float()->between(0.5, 10.0),
                Generator\float()->between(50.0, 200.0)
            )
            ->then(function (string $country, float $weight, float $orderAmount): void {
                $result = $this->calculator->getAvailableMethods($country, $weight, $orderAmount);
                
                $this->assertTrue($result['success'], "Should find methods for {$country}");
                $this->assertNotEmpty($result['methods'], "Should have available methods");
                
                // Verify methods are sorted by cost (cheapest first)
                $costs = array_column($result['methods'], 'cost');
                $sortedCosts = $costs;
                sort($sortedCosts);
                
                $this->assertEquals(
                    $sortedCosts,
                    $costs,
                    "Methods should be sorted by cost (cheapest first)"
                );
            });
    }

    /**
     * Test that countries not in any zone return no methods.
     */
    public function testUnavailableCountry(): void
    {
        if (!$this->dbAvailable) {
            $this->markTestSkipped('Database not available for testing');
        }

        $this->limitTo(10)
            ->forAll(
                Generator\elements('ZZ', 'XX', 'YY'), // Invalid country codes
                Generator\float()->between(0.5, 10.0),
                Generator\float()->between(50.0, 200.0)
            )
            ->then(function (string $country, float $weight, float $orderAmount): void {
                $result = $this->calculator->getAvailableMethods($country, $weight, $orderAmount);
                
                $this->assertFalse($result['success'], "Should not find methods for invalid country {$country}");
                $this->assertEmpty($result['methods'], "Should have no available methods");
            });
    }
}
