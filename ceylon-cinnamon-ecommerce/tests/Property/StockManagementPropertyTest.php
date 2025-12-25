<?php
/**
 * Property-Based Tests for Stock Management
 * 
 * Feature: ceylon-cinnamon-ecommerce
 * Property 13: Stock reduction accuracy
 * Property 20: Stock restoration on cancellation
 * 
 * For any completed order, product stock quantities should be reduced by exactly the ordered amounts.
 * For any cancelled order, product stock quantities should be restored by the exact amounts that were originally deducted.
 * 
 * Validates: Requirements 3.7, 7.6
 */

declare(strict_types=1);

namespace Tests\Property;

use PHPUnit\Framework\TestCase;
use Eris\Generator;
use Eris\TestTrait;

class StockManagementPropertyTest extends TestCase
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
            'name' => 'Test Stock Category',
            'slug' => 'test-stock-category-' . uniqid()
        ];
        $categoryId = $this->categoryModel->createCategory($categoryData);
        $this->testCategoryIds[] = $categoryId;

        // Create test products with varying stock levels
        $products = [
            ['sku' => 'STOCK-TEST-001-' . uniqid(), 'name' => 'Stock Test Product 1', 'price' => 10.99, 'category_id' => $categoryId, 'stock_quantity' => 1000],
            ['sku' => 'STOCK-TEST-002-' . uniqid(), 'name' => 'Stock Test Product 2', 'price' => 25.50, 'category_id' => $categoryId, 'stock_quantity' => 500],
            ['sku' => 'STOCK-TEST-003-' . uniqid(), 'name' => 'Stock Test Product 3', 'price' => 5.00, 'category_id' => $categoryId, 'stock_quantity' => 2000],
        ];

        foreach ($products as $product) {
            $id = $this->productModel->createProduct($product);
            $this->testProductIds[] = $id;
        }
    }

    private function cleanupTestData(): void
    {
        // Clean up orders first (due to foreign key constraints)
        foreach ($this->testOrderNumbers as $orderNumber) {
            $order = $this->orderModel->findByOrderNumber($orderNumber);
            if ($order) {
                $sql = "DELETE FROM order_items WHERE order_id = :order_id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute(['order_id' => $order['id']]);
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
     * Feature: ceylon-cinnamon-ecommerce, Property 13: Stock reduction accuracy
     * 
     * For any completed order, product stock quantities should be reduced 
     * by exactly the ordered amounts.
     * 
     * Validates: Requirements 3.7
     */
    public function testStockReductionAccuracy(): void
    {
        if (!$this->dbAvailable) {
            $this->markTestSkipped('Database not available for testing');
        }

        if (empty($this->testProductIds)) {
            $this->markTestSkipped('No test products available');
        }

        $this->limitTo(10)
            ->forAll(
                Generator\elements(...$this->testProductIds),
                Generator\choose(1, 10) // Order quantity
            )
            ->then(function (int $productId, int $quantity): void {
                // Get initial stock
                $productBefore = $this->productModel->find($productId);
                $initialStock = (int) $productBefore['stock_quantity'];
                
                // Skip if not enough stock
                if ($initialStock < $quantity) {
                    return;
                }

                $orderData = [
                    'email' => 'stocktest' . uniqid() . '@example.com',
                    'first_name' => 'Stock',
                    'last_name' => 'Test',
                    'shipping_address' => '123 Stock Test Street',
                    'payment_method' => 'stripe'
                ];

                $items = [
                    [
                        'product_id' => $productId,
                        'quantity' => $quantity,
                        'price' => (float) $productBefore['price']
                    ]
                ];

                try {
                    $orderNumber = $this->orderModel->createOrder($orderData, $items);
                    $this->testOrderNumbers[] = $orderNumber;
                    
                    // Get stock after order
                    $productAfter = $this->productModel->find($productId);
                    $finalStock = (int) $productAfter['stock_quantity'];
                    
                    // Verify stock was reduced by exactly the ordered quantity
                    $expectedStock = $initialStock - $quantity;
                    $this->assertEquals(
                        $expectedStock,
                        $finalStock,
                        "Stock should be reduced by exactly {$quantity}. Expected: {$expectedStock}, Got: {$finalStock}"
                    );
                    
                } catch (\Exception $e) {
                    $this->fail("Order creation failed: " . $e->getMessage());
                }
            });
    }

    /**
     * Feature: ceylon-cinnamon-ecommerce, Property 20: Stock restoration on cancellation
     * 
     * For any cancelled order, product stock quantities should be restored 
     * by the exact amounts that were originally deducted.
     * 
     * Validates: Requirements 7.6
     */
    public function testStockRestorationOnCancellation(): void
    {
        if (!$this->dbAvailable) {
            $this->markTestSkipped('Database not available for testing');
        }

        if (empty($this->testProductIds)) {
            $this->markTestSkipped('No test products available');
        }

        $this->limitTo(10)
            ->forAll(
                Generator\elements(...$this->testProductIds),
                Generator\choose(1, 10) // Order quantity
            )
            ->then(function (int $productId, int $quantity): void {
                // Get initial stock
                $productBefore = $this->productModel->find($productId);
                $initialStock = (int) $productBefore['stock_quantity'];
                
                // Skip if not enough stock
                if ($initialStock < $quantity) {
                    return;
                }

                $orderData = [
                    'email' => 'canceltest' . uniqid() . '@example.com',
                    'first_name' => 'Cancel',
                    'last_name' => 'Test',
                    'shipping_address' => '123 Cancel Test Street',
                    'payment_method' => 'stripe'
                ];

                $items = [
                    [
                        'product_id' => $productId,
                        'quantity' => $quantity,
                        'price' => (float) $productBefore['price']
                    ]
                ];

                try {
                    // Create order (stock is reduced)
                    $orderNumber = $this->orderModel->createOrder($orderData, $items);
                    $this->testOrderNumbers[] = $orderNumber;
                    
                    // Verify stock was reduced
                    $productAfterOrder = $this->productModel->find($productId);
                    $stockAfterOrder = (int) $productAfterOrder['stock_quantity'];
                    $this->assertEquals($initialStock - $quantity, $stockAfterOrder);
                    
                    // Cancel the order
                    $order = $this->orderModel->findByOrderNumber($orderNumber);
                    $this->orderModel->cancelOrder((int) $order['id']);
                    
                    // Verify stock was restored
                    $productAfterCancel = $this->productModel->find($productId);
                    $stockAfterCancel = (int) $productAfterCancel['stock_quantity'];
                    
                    $this->assertEquals(
                        $initialStock,
                        $stockAfterCancel,
                        "Stock should be restored to initial value {$initialStock} after cancellation. Got: {$stockAfterCancel}"
                    );
                    
                } catch (\Exception $e) {
                    $this->fail("Test failed: " . $e->getMessage());
                }
            });
    }

    /**
     * Test that multiple items in an order all have their stock reduced correctly.
     */
    public function testMultipleItemStockReduction(): void
    {
        if (!$this->dbAvailable) {
            $this->markTestSkipped('Database not available for testing');
        }

        if (count($this->testProductIds) < 2) {
            $this->markTestSkipped('Need at least 2 test products');
        }

        $this->limitTo(10)
            ->forAll(
                Generator\choose(1, 5), // Quantity for product 1
                Generator\choose(1, 5)  // Quantity for product 2
            )
            ->then(function (int $qty1, int $qty2): void {
                $productId1 = $this->testProductIds[0];
                $productId2 = $this->testProductIds[1];
                
                // Get initial stocks
                $product1Before = $this->productModel->find($productId1);
                $product2Before = $this->productModel->find($productId2);
                $initialStock1 = (int) $product1Before['stock_quantity'];
                $initialStock2 = (int) $product2Before['stock_quantity'];
                
                // Skip if not enough stock
                if ($initialStock1 < $qty1 || $initialStock2 < $qty2) {
                    return;
                }

                $orderData = [
                    'email' => 'multitest' . uniqid() . '@example.com',
                    'first_name' => 'Multi',
                    'last_name' => 'Test',
                    'shipping_address' => '123 Multi Test Street',
                    'payment_method' => 'stripe'
                ];

                $items = [
                    [
                        'product_id' => $productId1,
                        'quantity' => $qty1,
                        'price' => (float) $product1Before['price']
                    ],
                    [
                        'product_id' => $productId2,
                        'quantity' => $qty2,
                        'price' => (float) $product2Before['price']
                    ]
                ];

                try {
                    $orderNumber = $this->orderModel->createOrder($orderData, $items);
                    $this->testOrderNumbers[] = $orderNumber;
                    
                    // Verify both products had stock reduced correctly
                    $product1After = $this->productModel->find($productId1);
                    $product2After = $this->productModel->find($productId2);
                    
                    $this->assertEquals(
                        $initialStock1 - $qty1,
                        (int) $product1After['stock_quantity'],
                        "Product 1 stock should be reduced by {$qty1}"
                    );
                    
                    $this->assertEquals(
                        $initialStock2 - $qty2,
                        (int) $product2After['stock_quantity'],
                        "Product 2 stock should be reduced by {$qty2}"
                    );
                    
                } catch (\Exception $e) {
                    $this->fail("Order creation failed: " . $e->getMessage());
                }
            });
    }

    /**
     * Test that insufficient stock prevents order creation.
     */
    public function testInsufficientStockPreventsOrder(): void
    {
        if (!$this->dbAvailable) {
            $this->markTestSkipped('Database not available for testing');
        }

        if (empty($this->testProductIds)) {
            $this->markTestSkipped('No test products available');
        }

        $productId = $this->testProductIds[0];
        $product = $this->productModel->find($productId);
        $currentStock = (int) $product['stock_quantity'];
        
        // Try to order more than available stock
        $orderData = [
            'email' => 'insufficienttest@example.com',
            'first_name' => 'Insufficient',
            'last_name' => 'Test',
            'shipping_address' => '123 Test Street',
            'payment_method' => 'stripe'
        ];

        $items = [
            [
                'product_id' => $productId,
                'quantity' => $currentStock + 100, // More than available
                'price' => (float) $product['price']
            ]
        ];

        $this->expectException(\Exception::class);
        $this->orderModel->createOrder($orderData, $items);
    }
}
