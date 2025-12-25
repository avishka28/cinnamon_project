<?php
/**
 * Property-Based Tests for Payment Failure Handling
 * 
 * Feature: ceylon-cinnamon-ecommerce
 * Property 15: Payment failure cart preservation
 * 
 * For any failed payment, the customer's cart contents should remain 
 * unchanged and available for retry.
 * 
 * Validates: Requirements 4.5
 */

declare(strict_types=1);

namespace Tests\Property;

use PHPUnit\Framework\TestCase;
use Eris\Generator;
use Eris\TestTrait;

/**
 * Mock SessionManager for testing cart preservation
 */
class PaymentFailureMockSession
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

    public function getData(): array
    {
        return $this->data;
    }
}

class PaymentFailurePropertyTest extends TestCase
{
    use TestTrait;

    private \PDO $db;
    private bool $dbAvailable = false;
    private array $testProductIds = [];
    private array $testCategoryIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        
        try {
            $_ENV['DB_NAME'] = $_ENV['DB_NAME'] ?? 'ceylon_cinnamon_test';
            require_once __DIR__ . '/../../config/database.php';
            require_once __DIR__ . '/../../includes/Model.php';
            require_once __DIR__ . '/../../includes/SessionManager.php';
            require_once __DIR__ . '/../../includes/PaymentProcessor.php';
            require_once __DIR__ . '/../../includes/PaymentErrorHandler.php';
            require_once __DIR__ . '/../../models/Category.php';
            require_once __DIR__ . '/../../models/Product.php';
            require_once __DIR__ . '/../../models/Cart.php';
            
            $this->db = \Database::getInstance();
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
        $categoryModel = new \Category();
        $productModel = new \Product();

        // Create test category
        $categoryData = [
            'name' => 'Payment Test Category',
            'slug' => 'payment-test-category-' . uniqid()
        ];
        $categoryId = $categoryModel->createCategory($categoryData);
        $this->testCategoryIds[] = $categoryId;

        // Create test products
        $products = [
            ['sku' => 'PAY-TEST-001-' . uniqid(), 'name' => 'Payment Test Product 1', 'price' => 25.99, 'category_id' => $categoryId, 'stock_quantity' => 100],
            ['sku' => 'PAY-TEST-002-' . uniqid(), 'name' => 'Payment Test Product 2', 'price' => 49.99, 'category_id' => $categoryId, 'stock_quantity' => 50],
            ['sku' => 'PAY-TEST-003-' . uniqid(), 'name' => 'Payment Test Product 3', 'price' => 15.00, 'category_id' => $categoryId, 'stock_quantity' => 200],
        ];

        foreach ($products as $product) {
            $id = $productModel->createProduct($product);
            $this->testProductIds[] = $id;
        }
    }

    private function cleanupTestData(): void
    {
        $productModel = new \Product();
        $categoryModel = new \Category();

        foreach ($this->testProductIds as $id) {
            $productModel->delete($id);
        }
        
        foreach ($this->testCategoryIds as $id) {
            $categoryModel->delete($id);
        }
    }

    /**
     * Feature: ceylon-cinnamon-ecommerce, Property 15: Payment failure cart preservation
     * 
     * For any failed payment, the customer's cart contents should remain 
     * unchanged and available for retry.
     * 
     * Validates: Requirements 4.5
     */
    public function testCartPreservedAfterPaymentFailure(): void
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
                Generator\choose(1, 5)
            )
            ->then(function (int $productId, int $quantity): void {
                // Create a cart with items
                $mockSession = new PaymentFailureMockSession();
                $cart = new \Cart($mockSession);
                
                // Add product to cart
                $result = $cart->add($productId, $quantity);
                $this->assertTrue($result, "Should be able to add product to cart");
                
                // Store cart state before payment attempt
                $cartBefore = $cart->getItems();
                $totalBefore = $cart->getTotal();
                $quantityBefore = $cart->getProductQuantity($productId);
                
                // Simulate a failed payment (PaymentProcessor will fail with invalid credentials)
                $paymentProcessor = new \PaymentProcessor();
                $paymentResult = $paymentProcessor->process(
                    'stripe',
                    $totalBefore,
                    ['token' => 'invalid_token_' . uniqid()],
                    ['order_number' => 'TEST-' . uniqid(), 'email' => 'test@example.com']
                );
                
                // Payment should fail (no valid Stripe credentials)
                $this->assertFalse($paymentResult['success'], "Payment should fail with invalid token");
                
                // Verify cart is unchanged after payment failure
                $cartAfter = $cart->getItems();
                $totalAfter = $cart->getTotal();
                $quantityAfter = $cart->getProductQuantity($productId);
                
                $this->assertEquals(
                    $cartBefore,
                    $cartAfter,
                    "Cart items should be unchanged after payment failure"
                );
                
                $this->assertEquals(
                    $totalBefore,
                    $totalAfter,
                    "Cart total should be unchanged after payment failure"
                );
                
                $this->assertEquals(
                    $quantityBefore,
                    $quantityAfter,
                    "Product quantity should be unchanged after payment failure"
                );
            });
    }

    /**
     * Test that cart with multiple items is preserved after payment failure
     */
    public function testMultipleItemsCartPreservedAfterFailure(): void
    {
        if (!$this->dbAvailable) {
            $this->markTestSkipped('Database not available for testing');
        }

        if (count($this->testProductIds) < 2) {
            $this->markTestSkipped('Need at least 2 test products');
        }

        $this->limitTo(10)
            ->forAll(
                Generator\choose(1, 5),
                Generator\choose(1, 5)
            )
            ->then(function (int $qty1, int $qty2): void {
                $mockSession = new PaymentFailureMockSession();
                $cart = new \Cart($mockSession);
                
                // Add multiple products
                $productId1 = $this->testProductIds[0];
                $productId2 = $this->testProductIds[1];
                
                $cart->add($productId1, $qty1);
                $cart->add($productId2, $qty2);
                
                // Store cart state
                $itemCountBefore = $cart->getItemCount();
                $totalQuantityBefore = $cart->getTotalQuantity();
                $totalBefore = $cart->getTotal();
                
                // Simulate failed payment
                $paymentProcessor = new \PaymentProcessor();
                $paymentResult = $paymentProcessor->process(
                    'paypal',
                    $totalBefore,
                    ['order_id' => 'invalid_order_' . uniqid()],
                    ['order_number' => 'TEST-' . uniqid(), 'email' => 'test@example.com']
                );
                
                $this->assertFalse($paymentResult['success']);
                
                // Verify all items preserved
                $this->assertEquals($itemCountBefore, $cart->getItemCount());
                $this->assertEquals($totalQuantityBefore, $cart->getTotalQuantity());
                $this->assertEquals($totalBefore, $cart->getTotal());
                $this->assertTrue($cart->hasProduct($productId1));
                $this->assertTrue($cart->hasProduct($productId2));
                $this->assertEquals($qty1, $cart->getProductQuantity($productId1));
                $this->assertEquals($qty2, $cart->getProductQuantity($productId2));
            });
    }

    /**
     * Test that PaymentErrorHandler provides user-friendly messages
     */
    public function testPaymentErrorHandlerProvidesUserFriendlyMessages(): void
    {
        $this->limitTo(10)
            ->forAll(
                Generator\elements(
                    'card_declined',
                    'insufficient_funds',
                    'expired_card',
                    'incorrect_cvc',
                    'processing_error',
                    'unknown_error'
                ),
                Generator\elements('stripe', 'paypal', 'bank_transfer')
            )
            ->then(function (string $errorCode, string $paymentMethod): void {
                $errorHandler = new \PaymentErrorHandler();
                
                $result = $errorHandler->handleError(
                    $errorCode,
                    "Raw error message for {$errorCode}",
                    $paymentMethod
                );
                
                // Verify error handler returns required fields
                $this->assertArrayHasKey('success', $result);
                $this->assertArrayHasKey('message', $result);
                $this->assertArrayHasKey('recoverable', $result);
                $this->assertArrayHasKey('suggestion', $result);
                
                // Success should be false for errors
                $this->assertFalse($result['success']);
                
                // Message should be non-empty and user-friendly (no technical jargon)
                $this->assertNotEmpty($result['message']);
                $this->assertIsString($result['message']);
                
                // Message should not contain raw error codes or technical terms
                $this->assertStringNotContainsString('Exception', $result['message']);
                $this->assertStringNotContainsString('Error:', $result['message']);
                
                // Suggestion should be provided
                $this->assertNotEmpty($result['suggestion']);
            });
    }

    /**
     * Test that cart can be used for retry after payment failure
     */
    public function testCartAvailableForRetryAfterFailure(): void
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
                Generator\choose(1, 3),
                Generator\choose(1, 3) // Number of retry attempts
            )
            ->then(function (int $productId, int $quantity, int $retryAttempts): void {
                $mockSession = new PaymentFailureMockSession();
                $cart = new \Cart($mockSession);
                
                // Add product to cart
                $cart->add($productId, $quantity);
                $originalTotal = $cart->getTotal();
                
                // Simulate multiple failed payment attempts
                $paymentProcessor = new \PaymentProcessor();
                
                for ($i = 0; $i < $retryAttempts; $i++) {
                    $paymentResult = $paymentProcessor->process(
                        'stripe',
                        $originalTotal,
                        ['token' => 'invalid_token_attempt_' . $i],
                        ['order_number' => 'TEST-' . uniqid(), 'email' => 'test@example.com']
                    );
                    
                    $this->assertFalse($paymentResult['success']);
                    
                    // Cart should still be available after each failure
                    $this->assertFalse($cart->isEmpty(), "Cart should not be empty after attempt {$i}");
                    $this->assertEquals(
                        $originalTotal,
                        $cart->getTotal(),
                        "Cart total should be unchanged after attempt {$i}"
                    );
                }
                
                // Cart should still be fully functional for a successful retry
                $this->assertTrue($cart->hasProduct($productId));
                $this->assertEquals($quantity, $cart->getProductQuantity($productId));
                
                // Should be able to modify cart after failures
                $cart->update($productId, $quantity + 1);
                $this->assertEquals($quantity + 1, $cart->getProductQuantity($productId));
            });
    }
}
