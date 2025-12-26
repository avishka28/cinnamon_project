<?php
/**
 * Payment Gateway Integration Test
 * Tests payment gateway integration for Stripe, PayPal, and bank transfer
 * 
 * Requirements:
 * - 4.1: Process payment through Stripe API in demo mode
 * - 4.2: Process payment through PayPal API in demo mode
 * - 4.4: Do NOT store credit card information on server
 */

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

class PaymentGatewayIntegrationTest extends TestCase
{
    private \PaymentProcessor $processor;
    private \PaymentErrorHandler $errorHandler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->processor = new \PaymentProcessor();
        $this->errorHandler = new \PaymentErrorHandler();
    }

    /**
     * Test payment processor available methods
     * Requirement 4.1, 4.2, 4.3: Multiple payment methods
     */
    public function testAvailablePaymentMethods(): void
    {
        $methods = $this->processor->getAvailableMethods();
        
        // Bank transfer should always be available
        $this->assertArrayHasKey('bank_transfer', $methods);
        
        // Verify bank transfer details
        $bankTransfer = $methods['bank_transfer'];
        $this->assertEquals('Bank Transfer', $bankTransfer['name']);
        $this->assertNotEmpty($bankTransfer['description']);
    }

    /**
     * Test bank transfer processing
     * Requirement 4.3: Support bank transfer with bank details
     */
    public function testBankTransferProcessing(): void
    {
        $amount = 150.00;
        $orderData = [
            'email' => 'customer@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'order_number' => 'CC2025123456'
        ];
        
        $result = $this->processor->processBankTransfer($amount, $orderData);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('pending', $result['status']);
        $this->assertStringStartsWith('BT-', $result['reference']);
        $this->assertArrayHasKey('bank_details', $result);
        $this->assertArrayHasKey('instructions', $result);
        $this->assertStringContainsString((string)$amount, $result['instructions']);
    }

    /**
     * Test bank details structure
     */
    public function testBankDetailsStructure(): void
    {
        $bankDetails = $this->processor->getBankDetails();
        
        $requiredFields = ['bank_name', 'account_name', 'account_number', 'currency'];
        
        foreach ($requiredFields as $field) {
            $this->assertArrayHasKey($field, $bankDetails);
            $this->assertNotEmpty($bankDetails[$field]);
        }
    }

    /**
     * Test Stripe payment requires token
     * Requirement 4.4: Credit card data protection
     */
    public function testStripeRequiresToken(): void
    {
        $result = $this->processor->processStripe(100.00, [], []);
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('token', strtolower($result['error']));
    }

    /**
     * Test PayPal payment requires order ID
     */
    public function testPayPalRequiresOrderId(): void
    {
        $result = $this->processor->processPayPal(100.00, [], []);
        
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('order', strtolower($result['error']));
    }

    /**
     * Test payment method availability check
     */
    public function testPaymentMethodAvailabilityCheck(): void
    {
        // Bank transfer should always be available
        $this->assertTrue($this->processor->isMethodAvailable('bank_transfer'));
        
        // Invalid method should not be available
        $this->assertFalse($this->processor->isMethodAvailable('invalid_method'));
    }

    /**
     * Test payment error handler for card declined
     */
    public function testPaymentErrorHandlerCardDeclined(): void
    {
        $errorInfo = $this->errorHandler->handleError(
            'card_declined',
            'Your card was declined',
            'stripe'
        );
        
        $this->assertArrayHasKey('message', $errorInfo);
        $this->assertArrayHasKey('error_code', $errorInfo);
        $this->assertNotEmpty($errorInfo['message']);
    }

    /**
     * Test payment error handler for insufficient funds
     */
    public function testPaymentErrorHandlerInsufficientFunds(): void
    {
        $errorInfo = $this->errorHandler->handleError(
            'insufficient_funds',
            'Insufficient funds',
            'stripe'
        );
        
        $this->assertArrayHasKey('message', $errorInfo);
        $this->assertNotEmpty($errorInfo['message']);
    }

    /**
     * Test payment error handler for unknown error
     */
    public function testPaymentErrorHandlerUnknownError(): void
    {
        $errorInfo = $this->errorHandler->handleError(
            'unknown_error',
            'Something went wrong',
            'stripe'
        );
        
        $this->assertArrayHasKey('message', $errorInfo);
        $this->assertNotEmpty($errorInfo['message']);
    }

    /**
     * Test payment result structure for success
     */
    public function testPaymentResultStructureSuccess(): void
    {
        $result = $this->processor->processBankTransfer(100.00, []);
        
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('transaction_id', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('payment_method', $result);
    }

    /**
     * Test payment result structure for failure
     */
    public function testPaymentResultStructureFailure(): void
    {
        $result = $this->processor->process('invalid', 100.00, [], []);
        
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('error', $result);
        $this->assertFalse($result['success']);
        $this->assertNotEmpty($result['error']);
    }

    /**
     * Test payment verification for bank transfer
     */
    public function testPaymentVerificationBankTransfer(): void
    {
        $result = $this->processor->verifyPayment('bank_transfer', 'BT-TEST123');
        
        $this->assertTrue($result['success']);
        $this->assertEquals('pending', $result['status']);
    }

    /**
     * Test invalid payment method verification
     */
    public function testInvalidPaymentMethodVerification(): void
    {
        $result = $this->processor->verifyPayment('invalid_method', 'TEST123');
        
        $this->assertFalse($result['success']);
    }

    /**
     * Test payment amount handling
     */
    public function testPaymentAmountHandling(): void
    {
        $amounts = [0.01, 1.00, 99.99, 1000.00, 9999.99];
        
        foreach ($amounts as $amount) {
            $result = $this->processor->processBankTransfer($amount, []);
            
            $this->assertTrue($result['success']);
            $this->assertEquals($amount, $result['amount']);
        }
    }

    /**
     * Test payment processor handles zero amount
     */
    public function testPaymentProcessorZeroAmount(): void
    {
        $result = $this->processor->processBankTransfer(0.00, []);
        
        // Bank transfer should still succeed with zero amount (edge case)
        $this->assertTrue($result['success']);
    }

    /**
     * Test PayPal order creation without credentials
     */
    public function testPayPalOrderCreationWithoutCredentials(): void
    {
        // This test verifies error handling when PayPal is not configured
        $result = $this->processor->createPayPalOrder(100.00, []);
        
        // Should fail gracefully if PayPal is not configured
        $this->assertArrayHasKey('success', $result);
        if (!$result['success']) {
            $this->assertArrayHasKey('error', $result);
        }
    }
}
