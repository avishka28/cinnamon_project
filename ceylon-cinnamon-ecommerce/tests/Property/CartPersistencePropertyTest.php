<?php
/**
 * Property-Based Tests for Cart Persistence
 * 
 * Feature: ceylon-cinnamon-ecommerce
 * Property 11: Cart persistence
 * 
 * For any product added to cart, the item should be retrievable from 
 * the user's cart with correct quantity and details.
 * 
 * Validates: Requirements 3.1
 */

declare(strict_types=1);

namespace Tests\Property;

use PHPUnit\Framework\TestCase;
use Eris\Generator;
use Eris\TestTrait;

/**
 * Mock SessionManager for testing without actual PHP sessions
 */
class MockSessionManager
{
    private array $data = [];
    private bool $started = false;

    public function start(): bool
    {
        $this->started = true;
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

class CartPersistencePropertyTest extends TestCase
{
    use TestTrait;

    private \PDO $db;
    private \Product $productModel;
    private \Category $categoryModel;
    private bool $dbAvailable = false;
    private array $testCategoryIds = [];
    private array $testProductIds = [];

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

    private function createMockSession(): MockSessionManager
    {
        return new MockSessionManager();
    }

    private function setupTestData(): void
    {
        // Create test category
        $categoryData = [
            'name' => 'Test Cart Category',
            'slug' => 'test-cart-category-' . uniqid()
        ];
        $categoryId = $this->categoryModel->createCategory($categoryData);
        $this->testCategoryIds[] = $categoryId;

        // Create test products with stock
        $products = [
            ['sku' => 'CART-TEST-001-' . uniqid(), 'name' => 'Test Product 1', 'price' => 10.99, 'category_id' => $categoryId, 'stock_quantity' => 100],
            ['sku' => 'CART-TEST-002-' . uniqid(), 'name' => 'Test Product 2', 'price' => 25.50, 'category_id' => $categoryId, 'stock_quantity' => 50],
            ['sku' => 'CART-TEST-003-' . uniqid(), 'name' => 'Test Product 3', 'price' => 5.00, 'category_id' => $categoryId, 'stock_quantity' => 200],
            ['sku' => 'CART-TEST-004-' . uniqid(), 'name' => 'Test Product 4', 'price' => 99.99, 'category_id' => $categoryId, 'stock_quantity' => 10],
            ['sku' => 'CART-TEST-005-' . uniqid(), 'name' => 'Test Product 5', 'price' => 15.75, 'category_id' => $categoryId, 'stock_quantity' => 75],
        ];

        foreach ($products as $product) {
            $id = $this->productModel->createProduct($product);
            $this->testProductIds[] = $id;
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
     * Feature: ceylon-cinnamon-ecommerce, Property 11: Cart persistence
     * 
     * For any product added to cart, the item should be retrievable from 
     * the user's cart with correct quantity and details.
     * 
     * Validates: Requirements 3.1
     */
    public function testCartPersistence(): void
    {
        if (!$this->dbAvailable) {
            $this->markTestSkipped('Database not available for testing');
        }

        if (empty($this->testProductIds)) {
            $this->markTestSkipped('No test products available');
        }

        $this->limitTo(50)
            ->forAll(
                Generator\elements(...$this->testProductIds),
                Generator\choose(1, 10)
            )
            ->then(function (int $productId, int $quantity): void {
                $mockSession = $this->createMockSession();
                $cart = new \Cart($mockSession);
                
                // Add product to cart
                $result = $cart->add($productId, $quantity);
                $this->assertTrue($result, "Should be able to add product {$productId} to cart");
                
                // Verify product is retrievable with correct quantity
                $this->assertTrue(
                    $cart->hasProduct($productId),
                    "Cart should contain product {$productId}"
                );
                
                $this->assertEquals(
                    $quantity,
                    $cart->getProductQuantity($productId),
                    "Cart should have quantity {$quantity} for product {$productId}"
                );
                
                // Verify product details are correct
                $items = $cart->getItemsWithDetails();
                $found = false;
                
                foreach ($items as $item) {
                    if ($item['product_id'] === $productId) {
                        $found = true;
                        $this->assertEquals($quantity, $item['quantity']);
                        $this->assertArrayHasKey('product', $item);
                        $this->assertEquals($productId, $item['product']['id']);
                        break;
                    }
                }
                
                $this->assertTrue($found, "Product {$productId} should be found in cart items");
            });
    }

    /**
     * Test that adding the same product multiple times accumulates quantity.
     */
    public function testCartQuantityAccumulation(): void
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
                Generator\choose(1, 5),
                Generator\choose(1, 5)
            )
            ->then(function (int $productId, int $qty1, int $qty2): void {
                $mockSession = $this->createMockSession();
                $cart = new \Cart($mockSession);
                
                // Add product twice
                $cart->add($productId, $qty1);
                $cart->add($productId, $qty2);
                
                // Verify quantity is accumulated
                $expectedTotal = $qty1 + $qty2;
                $actualQuantity = $cart->getProductQuantity($productId);
                
                $this->assertEquals(
                    $expectedTotal,
                    $actualQuantity,
                    "Cart quantity should be {$expectedTotal} after adding {$qty1} + {$qty2}"
                );
            });
    }

    /**
     * Test that cart update correctly changes quantity.
     */
    public function testCartUpdatePersistence(): void
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
                Generator\choose(1, 5),
                Generator\choose(1, 10)
            )
            ->then(function (int $productId, int $initialQty, int $newQty): void {
                $mockSession = $this->createMockSession();
                $cart = new \Cart($mockSession);
                
                // Add product
                $cart->add($productId, $initialQty);
                
                // Update quantity
                $cart->update($productId, $newQty);
                
                // Verify new quantity
                $this->assertEquals(
                    $newQty,
                    $cart->getProductQuantity($productId),
                    "Cart quantity should be updated to {$newQty}"
                );
            });
    }

    /**
     * Test that removing a product removes it from cart.
     */
    public function testCartRemovePersistence(): void
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
                
                // Add product
                $cart->add($productId, $quantity);
                $this->assertTrue($cart->hasProduct($productId));
                
                // Remove product
                $cart->remove($productId);
                
                // Verify product is removed
                $this->assertFalse(
                    $cart->hasProduct($productId),
                    "Product {$productId} should be removed from cart"
                );
                
                $this->assertEquals(
                    0,
                    $cart->getProductQuantity($productId),
                    "Removed product quantity should be 0"
                );
            });
    }
}
