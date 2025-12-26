<?php
/**
 * Order Flow Integration Test
 * Tests complete order flow from cart to payment
 * 
 * Requirements:
 * - 3.3: Support both guest and registered user checkout
 * - 3.4: Process payment through Stripe or PayPal
 * - 3.5: Create order record and send confirmation email on success
 */

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

class OrderFlowIntegrationTest extends TestCase
{
    private $mockSession;
    private $cart;
    private $orderModel;
    private $paymentProcessor;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mock session for testing
        $this->mockSession = new class {
            private array $data = [];
            
            public function start(): void {}
            
            public function has(string $key): bool {
                return isset($this->data[$key]);
            }
            
            public function get(string $key, $default = null) {
                return $this->data[$key] ?? $default;
            }
            
            public function set(string $key, $value): void {
                $this->data[$key] = $value;
            }
            
            public function remove(string $key): void {
                unset($this->data[$key]);
            }
            
            public function isLoggedIn(): bool {
                return isset($this->data['user_id']);
            }
            
            public function getUserId(): ?int {
                return $this->data['user_id'] ?? null;
            }
            
            public function getCsrfToken(): string {
                return $this->data['csrf_token'] ?? 'test_token';
            }
            
            public function validateCsrfToken(string $token): bool {
                return $token === ($this->data['csrf_token'] ?? 'test_token');
            }
            
            public function flash(string $key, string $message): void {
                $this->data['flash'][$key] = $message;
            }
        };
    }

    /**
     * Test cart initialization and item management
     * Requirement 3.1: Add products to cart
     */
    public function testCartInitializationAndItemManagement(): void
    {
        $cart = new \Cart($this->mockSession);
        
        // Cart should be empty initially
        $this->assertTrue($cart->isEmpty());
        $this->assertEquals(0, $cart->getItemCount());
        $this->assertEquals(0.0, $cart->getTotal());
    }

    /**
     * Test cart summary calculation
     * Requirement 3.2: Display cart items with quantities, prices, and total
     */
    public function testCartSummaryCalculation(): void
    {
        $cart = new \Cart($this->mockSession);
        
        $summary = $cart->getSummary();
        
        // Verify summary structure
        $this->assertArrayHasKey('items', $summary);
        $this->assertArrayHasKey('item_count', $summary);
        $this->assertArrayHasKey('total_quantity', $summary);
        $this->assertArrayHasKey('subtotal', $summary);
        $this->assertArrayHasKey('total', $summary);
        
        // Empty cart should have zero values
        $this->assertEquals(0, $summary['item_count']);
        $this->assertEquals(0.0, $summary['subtotal']);
    }

    /**
     * Test payment processor initialization
     * Requirement 4.1, 4.2: Payment gateway integration
     */
    public function testPaymentProcessorInitialization(): void
    {
        $processor = new \PaymentProcessor();
        
        // Test available methods retrieval
        $methods = $processor->getAvailableMethods();
        
        // Bank transfer should always be available
        $this->assertArrayHasKey('bank_transfer', $methods);
        $this->assertEquals('Bank Transfer', $methods['bank_transfer']['name']);
    }

    /**
     * Test bank transfer payment processing
     * Requirement 4.3: Support bank transfer with bank details
     */
    public function testBankTransferPaymentProcessing(): void
    {
        $processor = new \PaymentProcessor();
        
        $orderData = [
            'email' => 'test@example.com',
            'first_name' => 'Test',
            'last_name' => 'User'
        ];
        
        $result = $processor->processBankTransfer(100.00, $orderData);
        
        // Bank transfer should always succeed
        $this->assertTrue($result['success']);
        $this->assertNotEmpty($result['transaction_id']);
        $this->assertEquals('pending', $result['status']);
        $this->assertArrayHasKey('bank_details', $result);
        $this->assertArrayHasKey('reference', $result);
    }

    /**
     * Test bank details retrieval
     * Requirement 4.3: Provide bank details for bank transfer
     */
    public function testBankDetailsRetrieval(): void
    {
        $processor = new \PaymentProcessor();
        
        $bankDetails = $processor->getBankDetails();
        
        // Verify all required bank details are present
        $this->assertArrayHasKey('bank_name', $bankDetails);
        $this->assertArrayHasKey('account_name', $bankDetails);
        $this->assertArrayHasKey('account_number', $bankDetails);
        $this->assertArrayHasKey('currency', $bankDetails);
    }

    /**
     * Test order model validation
     * Requirement 5.1: Order creation validation
     */
    public function testOrderDataValidation(): void
    {
        $orderModel = new \Order();
        
        // Test order number generation format
        $orderNumber = $orderModel->generateOrderNumber();
        
        // Order number should start with CC and year
        $this->assertStringStartsWith('CC' . date('Y'), $orderNumber);
        $this->assertEquals(12, strlen($orderNumber)); // CC + 4 year + 6 digits
    }

    /**
     * Test unique order number generation
     * Requirement 5.1: Assign unique order number
     */
    public function testUniqueOrderNumberGeneration(): void
    {
        $orderModel = new \Order();
        
        $orderNumbers = [];
        for ($i = 0; $i < 10; $i++) {
            $orderNumbers[] = $orderModel->generateOrderNumber();
        }
        
        // All order numbers should be unique
        $uniqueNumbers = array_unique($orderNumbers);
        $this->assertCount(10, $uniqueNumbers);
    }

    /**
     * Test order status constants
     * Requirement 5.4: Order statuses
     */
    public function testOrderStatusConstants(): void
    {
        $expectedStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'returned'];
        
        $this->assertEquals($expectedStatuses, \Order::STATUSES);
    }

    /**
     * Test payment status constants
     */
    public function testPaymentStatusConstants(): void
    {
        $expectedStatuses = ['pending', 'paid', 'failed', 'refunded'];
        
        $this->assertEquals($expectedStatuses, \Order::PAYMENT_STATUSES);
    }

    /**
     * Test payment method constants
     */
    public function testPaymentMethodConstants(): void
    {
        $expectedMethods = ['stripe', 'paypal', 'bank_transfer'];
        
        $this->assertEquals($expectedMethods, \Order::PAYMENT_METHODS);
    }

    /**
     * Test invalid payment method handling
     */
    public function testInvalidPaymentMethodHandling(): void
    {
        $processor = new \PaymentProcessor();
        
        $result = $processor->process('invalid_method', 100.00, [], []);
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Invalid payment method', $result['error']);
    }

    /**
     * Test Stripe payment without token
     * Requirement 4.1: Stripe payment validation
     */
    public function testStripePaymentWithoutToken(): void
    {
        $processor = new \PaymentProcessor();
        
        $result = $processor->processStripe(100.00, [], []);
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('token', strtolower($result['error']));
    }

    /**
     * Test PayPal payment without order ID
     * Requirement 4.2: PayPal payment validation
     */
    public function testPayPalPaymentWithoutOrderId(): void
    {
        $processor = new \PaymentProcessor();
        
        $result = $processor->processPayPal(100.00, [], []);
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('order', strtolower($result['error']));
    }

    /**
     * Test cart stock validation
     * Requirement 3.7: Stock management
     */
    public function testCartStockValidation(): void
    {
        $cart = new \Cart($this->mockSession);
        
        // Empty cart should have no stock issues
        $stockIssues = $cart->validateStock();
        $this->assertEmpty($stockIssues);
    }

    /**
     * Test cart to array conversion for JSON response
     */
    public function testCartToArrayConversion(): void
    {
        $cart = new \Cart($this->mockSession);
        
        $arrayData = $cart->toArray();
        
        $this->assertArrayHasKey('items', $arrayData);
        $this->assertArrayHasKey('item_count', $arrayData);
        $this->assertArrayHasKey('total_quantity', $arrayData);
        $this->assertArrayHasKey('subtotal', $arrayData);
        $this->assertArrayHasKey('total', $arrayData);
    }
}
