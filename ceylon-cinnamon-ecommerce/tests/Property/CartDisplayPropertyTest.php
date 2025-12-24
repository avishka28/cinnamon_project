<?php
/**
 * Property-Based Tests for Cart Display Completeness
 * 
 * Feature: ceylon-cinnamon-ecommerce
 * Property 12: Cart display completeness
 * 
 * For any cart view, all items should be displayed with quantities, 
 * individual prices, and correct total calculation.
 * 
 * Validates: Requirements 3.2
 */

declare(strict_types=1);

namespace Tests\Property;

use PHPUnit\Framework\TestCase;
use Eris\Generator;
use Eris\TestTrait;

/**
 * Mock SessionManager for testing without actual PHP sessions
 */
class CartDisplayMockSession
{
    private array $data = [];

    public function start(): bool
    {
        return true;
    }

    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function remove(string $key): void
    {
        unset($this->data[$key]);
    }

    public function clear(): void
    {
        $this->data = [];
    }
}

class CartDisplayPropertyTest extends TestCase
{
    use TestTrait;

    private \PDO $db;
    private \Product $productModel;
    private \Category $categoryModel;
    private bool $dbAvailable = false;
    private array $testCategoryIds = [];
    private array $testProductIds = [];
    private array $testProducts = [];

    protected function setUp(): void
    {
        parent::setUp();
        
        try {
            $_ENV['DB_NAME'] = $_ENV['DB_NAME'] ?? 'ceylon_cinnamon_test';
            require_once __DIR__ . '/../../config/database.php';
            require_once __DIR__ . '/../../includes/Model.php';
            require_once __DIR__ . '/../../includes/SessionManager.php';
            require_once __DIR__ . '/../../models/Category.php';
            require_once __DIR__ . '/../../models/Product.php';
            require_once __DIR__ . '/../../models/Cart.php';
            
            $this->db = \Database::getInstance();
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

    private function createMockSession(): CartDisplayMockSession
    {
        return new CartDisplayMockSession();
    }

    private function setupTestData(): void
    {
        // Create test category
        $categoryData = [
            'name' => 'Test Display Category',
            'slug' => 'test-display-category-' . uniqid()
        ];
        $categoryId = $this->categoryModel->createCategory($categoryData);
        $this->testCategoryIds[] = $categoryId;

        // Create test products with various prices
        $products = [
            ['sku' => 'DISP-TEST-001-' . uniqid(), 'name' => 'Display Product 1', 'price' => 10.99, 'category_id' => $categoryId, 'stock_quantity' => 100],
            ['sku' => 'DISP-TEST-002-' . uniqid(), 'name' => 'Display Product 2', 'price' => 25.50, 'category_id' => $categoryId, 'stock_quantity' => 50],
            ['sku' => 'DISP-TEST-003-' . uniqid(), 'name' => 'Display Product 3', 'price' => 5.00, 'category_id' => $categoryId, 'stock_quantity' => 200],
            ['sku' => 'DISP-TEST-004-' . uniqid(), 'name' => 'Display Product 4', 'price' => 99.99, 'category_id' => $categoryId, 'stock_quantity' => 10],
            ['sku' => 'DISP-TEST-005-' . uniqid(), 'name' => 'Display Product 5', 'price' => 15.75, 'category_id' => $categoryId, 'stock_quantity' => 75],
        ];

        foreach ($products as $product) {
            $id = $this->productModel->createProduct($product);
            $this->testProductIds[] = $id;
            $this->testProducts[$id] = array_merge($product, ['id' => $id]);
        }
    }

    private function cleanupTestData(): void
    {
        foreach ($this->testProductIds as $id) {
            $this->productModel->delete($id);
        }
        
        foreach ($this->testCategoryIds as $id) {
            $this->categoryModel->delete($id);
        }
    }

    /**
     * Feature: ceylon-cinnamon-ecommerce, Property 12: Cart display completeness
     * 
     * For any cart view, all items should be displayed with quantities, 
     * individual prices, and correct total calculation.
     * 
     * Validates: Requirements 3.2
     */
    public function testCartDisplayCompleteness(): void
    {
        if (!$this->dbAvailable) {
            $this->markTestSkipped('Database not available for testing');
        }

        if (empty($this->testProductIds)) {
            $this->markTestSkipped('No test products available');
        }

        $this->limitTo(30)
            ->forAll(
                Generator\elements(...$this->testProductIds),
                Generator\choose(1, 10)
            )
            ->then(function (int $productId, int $quantity): void {
                $mockSession = $this->createMockSession();
                $cart = new \Cart($mockSession);
                
                // Add product to cart
                $cart->add($productId, $quantity);
                
                // Get cart summary (what would be displayed)
                $summary = $cart->getSummary();
                
                // Verify items array exists
                $this->assertArrayHasKey('items', $summary, 'Summary should have items array');
                $this->assertNotEmpty($summary['items'], 'Items should not be empty');
                
                // Find the item we added
                $foundItem = null;
                foreach ($summary['items'] as $item) {
                    if ($item['product_id'] === $productId) {
                        $foundItem = $item;
                        break;
                    }
                }
                
                $this->assertNotNull($foundItem, "Product {$productId} should be in cart summary");
                
                // Verify quantity is displayed
                $this->assertArrayHasKey('quantity', $foundItem, 'Item should have quantity');
                $this->assertEquals($quantity, $foundItem['quantity'], 'Quantity should match');
                
                // Verify price is displayed
                $this->assertArrayHasKey('price', $foundItem, 'Item should have price');
                $this->assertIsFloat($foundItem['price'], 'Price should be a float');
                $this->assertGreaterThan(0, $foundItem['price'], 'Price should be positive');
                
                // Verify subtotal is correct
                $this->assertArrayHasKey('subtotal', $foundItem, 'Item should have subtotal');
                $expectedSubtotal = $foundItem['price'] * $quantity;
                $this->assertEquals(
                    $expectedSubtotal, 
                    $foundItem['subtotal'], 
                    'Subtotal should equal price * quantity'
                );
            });
    }

    /**
     * Test that cart total is correctly calculated for multiple items.
     */
    public function testCartTotalCalculation(): void
    {
        if (!$this->dbAvailable) {
            $this->markTestSkipped('Database not available for testing');
        }

        if (count($this->testProductIds) < 2) {
            $this->markTestSkipped('Need at least 2 test products');
        }

        $this->limitTo(20)
            ->forAll(
                Generator\choose(1, 5),  // quantity for first product
                Generator\choose(1, 5)   // quantity for second product
            )
            ->then(function (int $qty1, int $qty2): void {
                $mockSession = $this->createMockSession();
                $cart = new \Cart($mockSession);
                
                // Add two different products
                $productId1 = $this->testProductIds[0];
                $productId2 = $this->testProductIds[1];
                
                $cart->add($productId1, $qty1);
                $cart->add($productId2, $qty2);
                
                // Get cart summary
                $summary = $cart->getSummary();
                
                // Calculate expected total from items
                $expectedTotal = 0.0;
                foreach ($summary['items'] as $item) {
                    $expectedTotal += $item['subtotal'];
                }
                
                // Verify total matches sum of subtotals
                $this->assertEquals(
                    $expectedTotal,
                    $summary['total'],
                    'Cart total should equal sum of all item subtotals'
                );
                
                // Verify subtotal equals total (before shipping/tax)
                $this->assertEquals(
                    $summary['subtotal'],
                    $summary['total'],
                    'Subtotal should equal total before shipping/tax'
                );
            });
    }

    /**
     * Test that cart item count is correct.
     */
    public function testCartItemCount(): void
    {
        if (!$this->dbAvailable) {
            $this->markTestSkipped('Database not available for testing');
        }

        if (count($this->testProductIds) < 3) {
            $this->markTestSkipped('Need at least 3 test products');
        }

        $this->limitTo(20)
            ->forAll(
                Generator\choose(1, 3)  // number of different products to add
            )
            ->then(function (int $numProducts): void {
                $mockSession = $this->createMockSession();
                $cart = new \Cart($mockSession);
                
                // Add specified number of different products
                $productsToAdd = array_slice($this->testProductIds, 0, $numProducts);
                foreach ($productsToAdd as $productId) {
                    $cart->add($productId, 1);
                }
                
                // Get cart summary
                $summary = $cart->getSummary();
                
                // Verify item count matches number of unique products
                $this->assertEquals(
                    $numProducts,
                    $summary['item_count'],
                    "Item count should be {$numProducts}"
                );
                
                // Verify items array has correct count
                $this->assertCount(
                    $numProducts,
                    $summary['items'],
                    "Items array should have {$numProducts} items"
                );
            });
    }

    /**
     * Test that cart total quantity is correct.
     */
    public function testCartTotalQuantity(): void
    {
        if (!$this->dbAvailable) {
            $this->markTestSkipped('Database not available for testing');
        }

        if (count($this->testProductIds) < 2) {
            $this->markTestSkipped('Need at least 2 test products');
        }

        $this->limitTo(20)
            ->forAll(
                Generator\choose(1, 5),
                Generator\choose(1, 5)
            )
            ->then(function (int $qty1, int $qty2): void {
                $mockSession = $this->createMockSession();
                $cart = new \Cart($mockSession);
                
                // Add products with different quantities
                $cart->add($this->testProductIds[0], $qty1);
                $cart->add($this->testProductIds[1], $qty2);
                
                // Get cart summary
                $summary = $cart->getSummary();
                
                // Verify total quantity
                $expectedTotalQty = $qty1 + $qty2;
                $this->assertEquals(
                    $expectedTotalQty,
                    $summary['total_quantity'],
                    "Total quantity should be {$expectedTotalQty}"
                );
            });
    }

    /**
     * Test that toArray method returns complete cart data for JSON response.
     */
    public function testCartToArrayCompleteness(): void
    {
        if (!$this->dbAvailable) {
            $this->markTestSkipped('Database not available for testing');
        }

        if (empty($this->testProductIds)) {
            $this->markTestSkipped('No test products available');
        }

        $this->limitTo(20)
            ->forAll(
                Generator\elements(...$this->testProductIds),
                Generator\choose(1, 5)
            )
            ->then(function (int $productId, int $quantity): void {
                $mockSession = $this->createMockSession();
                $cart = new \Cart($mockSession);
                
                $cart->add($productId, $quantity);
                
                // Get cart as array (for JSON response)
                $cartArray = $cart->toArray();
                
                // Verify required fields exist
                $this->assertArrayHasKey('items', $cartArray);
                $this->assertArrayHasKey('item_count', $cartArray);
                $this->assertArrayHasKey('total_quantity', $cartArray);
                $this->assertArrayHasKey('subtotal', $cartArray);
                $this->assertArrayHasKey('total', $cartArray);
                
                // Verify item has required display fields
                $this->assertNotEmpty($cartArray['items']);
                $item = $cartArray['items'][0];
                
                $this->assertArrayHasKey('product_id', $item);
                $this->assertArrayHasKey('name', $item);
                $this->assertArrayHasKey('price', $item);
                $this->assertArrayHasKey('quantity', $item);
                $this->assertArrayHasKey('subtotal', $item);
            });
    }
}
