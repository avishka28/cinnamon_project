<?php
/**
 * Property-Based Tests for Unique Order Number Generation
 * 
 * Feature: ceylon-cinnamon-ecommerce
 * Property 17: Unique order number generation
 * 
 * For any two orders, they should have different order numbers.
 * 
 * Validates: Requirements 5.1
 */

declare(strict_types=1);

namespace Tests\Property;

use PHPUnit\Framework\TestCase;
use Eris\Generator;
use Eris\TestTrait;

class OrderNumberPropertyTest extends TestCase
{
    use TestTrait;

    private \PDO $db;
    private \Order $orderModel;
    private \Product $productModel;
    private \Category $categoryModel;
    private bool $dbAvailable = false;
    private array $testCategoryIds = [];
    private array $testProductIds = [];
    private array $testOrderNumbers = [];

    protected function setUp(): void
    {
        parent::setUp();
        
        try {
            $_ENV['DB_NAME'] = $_ENV['DB_NAME'] ?? 'ceylon_cinnamon_test';
            require_once __DIR__ . '/../../config/database.php';
            require_once __DIR__ . '/../../includes/Model.php';
            require_once __DIR__ . '/../../models/Category.php';
            require_once __DIR__ . '/../../models/Product.php';
            require_once __DIR__ . '/../../models/OrderItem.php';
            require_once __DIR__ . '/../../models/Order.php';
            
            $this->db = \Database::getInstance();
            $this->orderModel = new \Order();
            $this->productModel = new \Product();
            $this->categoryModel = new \Category();
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
        // Create test category
        $categoryData = [
            'name' => 'Test Order Category',
            'slug' => 'test-order-category-' . uniqid()
        ];
        $categoryId = $this->categoryModel->createCategory($categoryData);
        $this->testCategoryIds[] = $categoryId;

        // Create test product with stock
        $productData = [
            'sku' => 'ORDER-TEST-001-' . uniqid(),
            'name' => 'Test Order Product',
            'price' => 29.99,
            'category_id' => $categoryId,
            'stock_quantity' => 10000 // High stock for multiple order tests
        ];
        $productId = $this->productModel->createProduct($productData);
        $this->testProductIds[] = $productId;
    }

    private function cleanupTestData(): void
    {
        // Clean up orders first (due to foreign key constraints)
        foreach ($this->testOrderNumbers as $orderNumber) {
            $order = $this->orderModel->findByOrderNumber($orderNumber);
            if ($order) {
                // Delete order items first
                $sql = "DELETE FROM order_items WHERE order_id = :order_id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute(['order_id' => $order['id']]);
                
                // Delete order
                $this->orderModel->delete((int) $order['id']);
            }
        }

        // Clean up products
        foreach ($this->testProductIds as $id) {
            $this->productModel->delete($id);
        }
        
        // Clean up categories
        foreach ($this->testCategoryIds as $id) {
            $this->categoryModel->delete($id);
        }
    }

    /**
     * Feature: ceylon-cinnamon-ecommerce, Property 17: Unique order number generation
     * 
     * For any two orders, they should have different order numbers.
     * 
     * Validates: Requirements 5.1
     */
    public function testUniqueOrderNumberGeneration(): void
    {
        if (!$this->dbAvailable) {
            $this->markTestSkipped('Database not available for testing');
        }

        if (empty($this->testProductIds)) {
            $this->markTestSkipped('No test products available');
        }

        $productId = $this->testProductIds[0];
        $generatedOrderNumbers = [];

        // Generate multiple order numbers and verify uniqueness
        $this->limitTo(10)
            ->forAll(
                Generator\choose(1, 1000000) // Random seed for variation
            )
            ->then(function (int $seed) use ($productId, &$generatedOrderNumbers): void {
                // Generate a new order number
                $orderNumber = $this->orderModel->generateOrderNumber();
                
                // Verify format: CC + Year + 6 digits
                $this->assertMatchesRegularExpression(
                    '/^CC\d{10}$/',
                    $orderNumber,
                    "Order number should match format CC + Year(4) + Random(6)"
                );
                
                // Verify uniqueness against all previously generated numbers
                $this->assertNotContains(
                    $orderNumber,
                    $generatedOrderNumbers,
                    "Order number {$orderNumber} should be unique"
                );
                
                $generatedOrderNumbers[] = $orderNumber;
            });
    }

    /**
     * Test that created orders have unique order numbers.
     */
    public function testCreatedOrdersHaveUniqueNumbers(): void
    {
        if (!$this->dbAvailable) {
            $this->markTestSkipped('Database not available for testing');
        }

        if (empty($this->testProductIds)) {
            $this->markTestSkipped('No test products available');
        }

        $productId = $this->testProductIds[0];
        $createdOrderNumbers = [];

        $this->limitTo(20) // Limit to avoid too many database records
            ->forAll(
                Generator\string(), // Random first name
                Generator\string()  // Random last name
            )
            ->then(function (string $firstName, string $lastName) use ($productId, &$createdOrderNumbers): void {
                // Sanitize names for database
                $firstName = substr(preg_replace('/[^a-zA-Z]/', '', $firstName) ?: 'Test', 0, 50) ?: 'Test';
                $lastName = substr(preg_replace('/[^a-zA-Z]/', '', $lastName) ?: 'User', 0, 50) ?: 'User';
                
                $orderData = [
                    'email' => 'test' . uniqid() . '@example.com',
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'shipping_address' => '123 Test Street, Test City',
                    'payment_method' => 'stripe'
                ];

                $items = [
                    [
                        'product_id' => $productId,
                        'quantity' => 1,
                        'price' => 29.99
                    ]
                ];

                try {
                    $orderNumber = $this->orderModel->createOrder($orderData, $items);
                    $this->testOrderNumbers[] = $orderNumber;
                    
                    // Verify uniqueness
                    $this->assertNotContains(
                        $orderNumber,
                        $createdOrderNumbers,
                        "Created order number {$orderNumber} should be unique"
                    );
                    
                    $createdOrderNumbers[] = $orderNumber;
                    
                    // Verify order exists in database
                    $order = $this->orderModel->findByOrderNumber($orderNumber);
                    $this->assertNotNull($order, "Order should exist in database");
                    $this->assertEquals($orderNumber, $order['order_number']);
                    
                } catch (\Exception $e) {
                    $this->fail("Order creation failed: " . $e->getMessage());
                }
            });
    }

    /**
     * Test order number format consistency.
     */
    public function testOrderNumberFormatConsistency(): void
    {
        if (!$this->dbAvailable) {
            $this->markTestSkipped('Database not available for testing');
        }

        $currentYear = date('Y');

        $this->limitTo(10)
            ->forAll(
                Generator\choose(1, 1000000)
            )
            ->then(function (int $seed) use ($currentYear): void {
                $orderNumber = $this->orderModel->generateOrderNumber();
                
                // Verify starts with CC
                $this->assertStringStartsWith('CC', $orderNumber);
                
                // Verify contains current year
                $this->assertStringContainsString($currentYear, $orderNumber);
                
                // Verify total length is 12 (CC + 4 year + 6 random)
                $this->assertEquals(12, strlen($orderNumber));
                
                // Verify numeric portion after CC
                $numericPart = substr($orderNumber, 2);
                $this->assertTrue(
                    ctype_digit($numericPart),
                    "Numeric part should contain only digits"
                );
            });
    }
}
