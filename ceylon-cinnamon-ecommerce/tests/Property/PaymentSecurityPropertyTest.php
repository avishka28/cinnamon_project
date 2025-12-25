<?php
/**
 * Property-Based Tests for Payment Security
 * 
 * Feature: ceylon-cinnamon-ecommerce
 * Property 14: Credit card data protection
 * 
 * For any payment transaction, no credit card information should remain 
 * stored in the system database after processing.
 * 
 * Validates: Requirements 4.4
 */

declare(strict_types=1);

namespace Tests\Property;

use PHPUnit\Framework\TestCase;
use Eris\Generator;
use Eris\TestTrait;

class PaymentSecurityPropertyTest extends TestCase
{
    use TestTrait;

    private \PDO $db;
    private bool $dbAvailable = false;
    private array $testOrderIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        
        try {
            $_ENV['DB_NAME'] = $_ENV['DB_NAME'] ?? 'ceylon_cinnamon_test';
            require_once __DIR__ . '/../../config/database.php';
            require_once __DIR__ . '/../../includes/Model.php';
            require_once __DIR__ . '/../../includes/PaymentProcessor.php';
            require_once __DIR__ . '/../../models/Order.php';
            require_once __DIR__ . '/../../models/OrderItem.php';
            require_once __DIR__ . '/../../models/Product.php';
            require_once __DIR__ . '/../../models/Category.php';
            
            $this->db = \Database::getInstance();
            $this->dbAvailable = true;
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

    private function cleanupTestData(): void
    {
        foreach ($this->testOrderIds as $orderId) {
            try {
                $this->db->exec("DELETE FROM order_items WHERE order_id = {$orderId}");
                $this->db->exec("DELETE FROM orders WHERE id = {$orderId}");
            } catch (\Exception $e) {
                // Ignore cleanup errors
            }
        }
    }

    /**
     * Feature: ceylon-cinnamon-ecommerce, Property 14: Credit card data protection
     * 
     * For any payment transaction, no credit card information should remain 
     * stored in the system database after processing.
     * 
     * Validates: Requirements 4.4
     * 
     * This test verifies that:
     * 1. The PaymentProcessor class does not store card numbers
     * 2. The Order model does not have fields for storing card data
     * 3. No credit card patterns exist in the orders table
     */
    public function testCreditCardDataNotStoredInDatabase(): void
    {
        if (!$this->dbAvailable) {
            $this->markTestSkipped('Database not available for testing');
        }

        // Generate various credit card number patterns to test
        $this->limitTo(10)
            ->forAll(
                Generator\elements(
                    '4111111111111111',  // Visa test
                    '5500000000000004',  // Mastercard test
                    '340000000000009',   // Amex test
                    '6011000000000004',  // Discover test
                    '4242424242424242',  // Stripe test card
                    '378282246310005'    // Amex test
                ),
                Generator\elements('123', '456', '789', '000', '999'), // CVV
                Generator\elements('12/25', '01/26', '06/27', '11/28') // Expiry
            )
            ->then(function (string $cardNumber, string $cvv, string $expiry): void {
                // Verify the orders table schema does not contain credit card fields
                $this->assertDatabaseSchemaDoesNotContainCardFields();
                
                // Verify no card data patterns exist in orders table
                $this->assertNoCardDataInOrdersTable($cardNumber);
                
                // Verify PaymentProcessor does not expose card storage methods
                $this->assertPaymentProcessorDoesNotStoreCards();
            });
    }

    /**
     * Test that the database schema does not have fields for storing card data
     */
    private function assertDatabaseSchemaDoesNotContainCardFields(): void
    {
        // Get orders table columns
        $stmt = $this->db->query("DESCRIBE orders");
        $columns = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        
        // List of column names that would indicate card storage (should NOT exist)
        $forbiddenColumns = [
            'card_number',
            'credit_card',
            'card_num',
            'cc_number',
            'cvv',
            'cvc',
            'card_cvv',
            'card_cvc',
            'card_expiry',
            'expiry_date',
            'card_exp',
            'card_holder',
            'cardholder_name'
        ];
        
        foreach ($forbiddenColumns as $forbidden) {
            $this->assertNotContains(
                $forbidden,
                $columns,
                "Orders table should NOT contain column '{$forbidden}' for storing card data"
            );
        }
    }

    /**
     * Test that no credit card number patterns exist in the orders table
     */
    private function assertNoCardDataInOrdersTable(string $cardNumber): void
    {
        // Check all text columns in orders table for card number patterns
        $textColumns = ['notes', 'shipping_address', 'billing_address'];
        
        foreach ($textColumns as $column) {
            // Search for the card number in this column
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM orders WHERE {$column} LIKE :pattern"
            );
            $stmt->execute(['pattern' => '%' . $cardNumber . '%']);
            $count = (int) $stmt->fetchColumn();
            
            $this->assertEquals(
                0,
                $count,
                "Column '{$column}' should not contain credit card number patterns"
            );
        }
    }

    /**
     * Test that PaymentProcessor class does not have methods for storing card data
     */
    private function assertPaymentProcessorDoesNotStoreCards(): void
    {
        $paymentProcessor = new \PaymentProcessor();
        
        // Get all public methods
        $reflection = new \ReflectionClass($paymentProcessor);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        $methodNames = array_map(fn($m) => $m->getName(), $methods);
        
        // Methods that would indicate card storage (should NOT exist)
        $forbiddenMethods = [
            'storeCard',
            'saveCard',
            'saveCardNumber',
            'storeCreditCard',
            'persistCard',
            'savePaymentCard'
        ];
        
        foreach ($forbiddenMethods as $forbidden) {
            $this->assertNotContains(
                $forbidden,
                $methodNames,
                "PaymentProcessor should NOT have method '{$forbidden}' for storing card data"
            );
        }
        
        // Get all properties
        $properties = $reflection->getProperties();
        $propertyNames = array_map(fn($p) => $p->getName(), $properties);
        
        // Properties that would indicate card storage (should NOT exist)
        $forbiddenProperties = [
            'cardNumber',
            'creditCard',
            'storedCards',
            'cardData',
            'cvv',
            'cardExpiry'
        ];
        
        foreach ($forbiddenProperties as $forbidden) {
            $this->assertNotContains(
                $forbidden,
                $propertyNames,
                "PaymentProcessor should NOT have property '{$forbidden}' for storing card data"
            );
        }
    }

    /**
     * Test that payment result does not contain sensitive card data
     */
    public function testPaymentResultDoesNotContainCardData(): void
    {
        $this->limitTo(10)
            ->forAll(
                Generator\elements('stripe', 'paypal', 'bank_transfer'),
                Generator\choose(10, 1000)
            )
            ->then(function (string $method, int $amount): void {
                $paymentProcessor = new \PaymentProcessor();
                
                // Process a payment (will fail due to invalid credentials, but that's OK)
                $result = $paymentProcessor->process(
                    $method,
                    $amount,
                    ['token' => 'test_token', 'order_id' => 'test_order'],
                    ['order_number' => 'TEST123', 'email' => 'test@example.com']
                );
                
                // Verify result structure does not contain card data fields
                $this->assertArrayNotHasKey('card_number', $result);
                $this->assertArrayNotHasKey('cvv', $result);
                $this->assertArrayNotHasKey('card_expiry', $result);
                $this->assertArrayNotHasKey('cardholder', $result);
                
                // Verify result values don't contain card-like patterns
                foreach ($result as $key => $value) {
                    if (is_string($value)) {
                        // Check for 16-digit card number patterns
                        $this->assertDoesNotMatchRegularExpression(
                            '/\b\d{13,19}\b/',
                            $value,
                            "Payment result field '{$key}' should not contain card number patterns"
                        );
                        
                        // Check for CVV patterns (3-4 digits alone)
                        $this->assertDoesNotMatchRegularExpression(
                            '/\bcvv[:\s]*\d{3,4}\b/i',
                            $value,
                            "Payment result field '{$key}' should not contain CVV data"
                        );
                    }
                }
            });
    }

    /**
     * Test that bank transfer method does not request card data
     */
    public function testBankTransferDoesNotRequireCardData(): void
    {
        $this->limitTo(10)
            ->forAll(
                Generator\choose(10, 500)
            )
            ->then(function (int $amount): void {
                $paymentProcessor = new \PaymentProcessor();
                
                // Bank transfer should work without any card data
                $result = $paymentProcessor->processBankTransfer(
                    (float) $amount,
                    ['order_number' => 'BT-TEST-' . uniqid()]
                );
                
                $this->assertTrue($result['success']);
                $this->assertArrayHasKey('bank_details', $result);
                $this->assertArrayHasKey('reference', $result);
                
                // Bank details should not contain card-related fields
                $bankDetails = $result['bank_details'];
                $this->assertArrayNotHasKey('card_number', $bankDetails);
                $this->assertArrayNotHasKey('cvv', $bankDetails);
            });
    }
}
