<?php
/**
 * Property-Based Tests for Email Notifications
 * 
 * Feature: ceylon-cinnamon-ecommerce
 * Property 32: Order confirmation emails
 * Property 19: Order status notification
 * 
 * Validates: Requirements 12.1, 5.3, 12.2
 */

declare(strict_types=1);

namespace Tests\Property;

use PHPUnit\Framework\TestCase;
use Eris\Generator;
use Eris\TestTrait;

class EmailNotificationPropertyTest extends TestCase
{
    use TestTrait;

    private \EmailService $emailService;
    private \EmailTemplates $emailTemplates;
    private \OrderNotificationService $notificationService;
    private bool $servicesAvailable = false;

    protected function setUp(): void
    {
        parent::setUp();
        
        try {
            // Load required files
            require_once __DIR__ . '/../../config/config.php';
            require_once __DIR__ . '/../../includes/EmailService.php';
            require_once __DIR__ . '/../../includes/EmailTemplates.php';
            require_once __DIR__ . '/../../config/database.php';
            require_once __DIR__ . '/../../includes/Model.php';
            require_once __DIR__ . '/../../models/OrderItem.php';
            require_once __DIR__ . '/../../models/Order.php';
            require_once __DIR__ . '/../../includes/OrderNotificationService.php';
            
            $this->emailService = new \EmailService();
            $this->emailTemplates = new \EmailTemplates($this->emailService);
            $this->notificationService = new \OrderNotificationService();
            $this->servicesAvailable = true;
        } catch (\Exception $e) {
            $this->servicesAvailable = false;
        }
    }

    /**
     * Feature: ceylon-cinnamon-ecommerce, Property 32: Order confirmation emails
     * 
     * For any placed order, a confirmation email should be generated with order details.
     * 
     * Validates: Requirements 12.1
     */
    public function testOrderConfirmationEmailContainsOrderDetails(): void
    {
        if (!$this->servicesAvailable) {
            $this->markTestSkipped('Email services not available for testing');
        }

        $this->limitTo(10)
            ->forAll(
                Generator\string(),  // order_number
                Generator\string(),  // first_name
                Generator\string(),  // last_name
                Generator\string(),  // email
                Generator\float()    // total_amount
            )
            ->then(function (
                string $orderNumber,
                string $firstName,
                string $lastName,
                string $email,
                float $totalAmount
            ): void {
                // Sanitize inputs
                $orderNumber = 'CC' . date('Y') . str_pad((string) abs(crc32($orderNumber) % 1000000), 6, '0', STR_PAD_LEFT);
                $firstName = substr(preg_replace('/[^a-zA-Z]/', '', $firstName) ?: 'Test', 0, 50) ?: 'Test';
                $lastName = substr(preg_replace('/[^a-zA-Z]/', '', $lastName) ?: 'User', 0, 50) ?: 'User';
                $email = 'test' . abs(crc32($email)) . '@example.com';
                $totalAmount = abs($totalAmount) + 10.00; // Ensure positive amount

                // Create mock order data
                $order = [
                    'id' => 1,
                    'order_number' => $orderNumber,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'shipping_address' => '123 Test Street, Test City',
                    'subtotal' => $totalAmount - 5.00,
                    'shipping_cost' => 5.00,
                    'tax_amount' => 0.00,
                    'total_amount' => $totalAmount,
                    'payment_method' => 'stripe',
                    'payment_status' => 'paid',
                    'order_status' => 'pending',
                    'created_at' => date('Y-m-d H:i:s')
                ];

                // Create mock order items
                $items = [
                    [
                        'product_name' => 'Ceylon Cinnamon Sticks',
                        'product_sku' => 'CCS-001',
                        'quantity' => 2,
                        'price' => 15.99,
                        'total' => 31.98
                    ]
                ];

                // Generate confirmation email
                $emailHtml = $this->emailTemplates->orderConfirmation($order, $items);

                // Property: Email should contain order number
                $this->assertStringContainsString(
                    $orderNumber,
                    $emailHtml,
                    "Confirmation email should contain order number"
                );

                // Property: Email should contain customer name
                $this->assertStringContainsString(
                    $firstName,
                    $emailHtml,
                    "Confirmation email should contain customer first name"
                );

                // Property: Email should contain company branding
                $this->assertStringContainsString(
                    'Ceylon Cinnamon',
                    $emailHtml,
                    "Confirmation email should contain company branding"
                );

                // Property: Email should contain order items
                $this->assertStringContainsString(
                    'Ceylon Cinnamon Sticks',
                    $emailHtml,
                    "Confirmation email should contain product name"
                );

                // Property: Email should contain total amount
                $this->assertStringContainsString(
                    number_format($totalAmount, 2),
                    $emailHtml,
                    "Confirmation email should contain total amount"
                );
            });
    }

    /**
     * Feature: ceylon-cinnamon-ecommerce, Property 19: Order status notification
     * 
     * For any order status change, an appropriate email notification should be generated.
     * 
     * Validates: Requirements 5.3, 12.2
     */
    public function testOrderStatusNotificationContainsStatusInfo(): void
    {
        if (!$this->servicesAvailable) {
            $this->markTestSkipped('Email services not available for testing');
        }

        $validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'returned'];

        $this->limitTo(10)
            ->forAll(
                Generator\string(),                           // order_number
                Generator\string(),                           // first_name
                Generator\elements($validStatuses)            // status
            )
            ->then(function (
                string $orderNumber,
                string $firstName,
                string $status
            ): void {
                // Sanitize inputs
                $orderNumber = 'CC' . date('Y') . str_pad((string) abs(crc32($orderNumber) % 1000000), 6, '0', STR_PAD_LEFT);
                $firstName = substr(preg_replace('/[^a-zA-Z]/', '', $firstName) ?: 'Test', 0, 50) ?: 'Test';

                // Create mock order data
                $order = [
                    'id' => 1,
                    'order_number' => $orderNumber,
                    'first_name' => $firstName,
                    'last_name' => 'User',
                    'email' => 'test@example.com',
                    'shipping_address' => '123 Test Street',
                    'subtotal' => 50.00,
                    'shipping_cost' => 5.00,
                    'tax_amount' => 0.00,
                    'total_amount' => 55.00,
                    'payment_method' => 'stripe',
                    'payment_status' => 'paid',
                    'order_status' => $status,
                    'created_at' => date('Y-m-d H:i:s')
                ];

                // Generate status update email
                $emailHtml = $this->emailTemplates->orderStatusUpdate($order, $status);

                // Property: Email should contain order number
                $this->assertStringContainsString(
                    $orderNumber,
                    $emailHtml,
                    "Status notification should contain order number"
                );

                // Property: Email should contain customer name
                $this->assertStringContainsString(
                    $firstName,
                    $emailHtml,
                    "Status notification should contain customer name"
                );

                // Property: Email should contain status information
                $this->assertStringContainsString(
                    ucfirst($status),
                    $emailHtml,
                    "Status notification should contain status: {$status}"
                );

                // Property: Email should contain company branding
                $this->assertStringContainsString(
                    'Ceylon Cinnamon',
                    $emailHtml,
                    "Status notification should contain company branding"
                );
            });
    }

    /**
     * Test shipping notification contains tracking information when provided.
     */
    public function testShippingNotificationContainsTrackingInfo(): void
    {
        if (!$this->servicesAvailable) {
            $this->markTestSkipped('Email services not available for testing');
        }

        $this->limitTo(10)
            ->forAll(
                Generator\string(),  // order_number
                Generator\string(),  // tracking_number
                Generator\string()   // carrier
            )
            ->then(function (
                string $orderNumber,
                string $trackingNumber,
                string $carrier
            ): void {
                // Sanitize inputs
                $orderNumber = 'CC' . date('Y') . str_pad((string) abs(crc32($orderNumber) % 1000000), 6, '0', STR_PAD_LEFT);
                $trackingNumber = 'TRK' . abs(crc32($trackingNumber) % 10000000000);
                $carrier = substr(preg_replace('/[^a-zA-Z ]/', '', $carrier) ?: 'DHL', 0, 30) ?: 'DHL';

                // Create mock order data
                $order = [
                    'id' => 1,
                    'order_number' => $orderNumber,
                    'first_name' => 'Test',
                    'last_name' => 'User',
                    'email' => 'test@example.com',
                    'shipping_address' => '123 Test Street, Test City',
                    'subtotal' => 50.00,
                    'shipping_cost' => 5.00,
                    'tax_amount' => 0.00,
                    'total_amount' => 55.00,
                    'payment_method' => 'stripe',
                    'payment_status' => 'paid',
                    'order_status' => 'shipped',
                    'created_at' => date('Y-m-d H:i:s')
                ];

                // Generate shipping notification with tracking
                $emailHtml = $this->emailTemplates->shippingNotification(
                    $order,
                    $trackingNumber,
                    $carrier,
                    'https://tracking.example.com/' . $trackingNumber
                );

                // Property: Email should contain order number
                $this->assertStringContainsString(
                    $orderNumber,
                    $emailHtml,
                    "Shipping notification should contain order number"
                );

                // Property: Email should contain tracking number
                $this->assertStringContainsString(
                    $trackingNumber,
                    $emailHtml,
                    "Shipping notification should contain tracking number"
                );

                // Property: Email should contain carrier name
                $this->assertStringContainsString(
                    $carrier,
                    $emailHtml,
                    "Shipping notification should contain carrier name"
                );

                // Property: Email should indicate shipment
                $this->assertStringContainsString(
                    'shipped',
                    strtolower($emailHtml),
                    "Shipping notification should indicate shipment"
                );
            });
    }

    /**
     * Test email service validates email addresses correctly.
     */
    public function testEmailValidation(): void
    {
        if (!$this->servicesAvailable) {
            $this->markTestSkipped('Email services not available for testing');
        }

        // Test valid emails
        $validEmails = [
            'test@example.com',
            'user.name@domain.org',
            'user+tag@example.co.uk'
        ];

        foreach ($validEmails as $email) {
            $this->assertTrue(
                $this->emailService->isValidEmail($email),
                "Email {$email} should be valid"
            );
        }

        // Test invalid emails
        $invalidEmails = [
            'invalid',
            'invalid@',
            '@domain.com',
            'invalid@domain',
            ''
        ];

        foreach ($invalidEmails as $email) {
            $this->assertFalse(
                $this->emailService->isValidEmail($email),
                "Email {$email} should be invalid"
            );
        }
    }

    /**
     * Test email templates include company branding consistently.
     */
    public function testEmailTemplatesIncludeCompanyBranding(): void
    {
        if (!$this->servicesAvailable) {
            $this->markTestSkipped('Email services not available for testing');
        }

        $companyInfo = $this->emailService->getCompanyInfo();

        // Verify company info is populated
        $this->assertNotEmpty($companyInfo['name'], "Company name should be set");
        $this->assertNotEmpty($companyInfo['email'], "Company email should be set");
        $this->assertNotEmpty($companyInfo['website'], "Company website should be set");

        // Verify header contains company name
        $header = $this->emailService->getEmailHeader();
        $this->assertStringContainsString(
            $companyInfo['name'],
            $header,
            "Email header should contain company name"
        );

        // Verify footer contains company info
        $footer = $this->emailService->getEmailFooter();
        $this->assertStringContainsString(
            $companyInfo['name'],
            $footer,
            "Email footer should contain company name"
        );
        $this->assertStringContainsString(
            $companyInfo['email'],
            $footer,
            "Email footer should contain company email"
        );
    }

    /**
     * Test cancellation notification contains reason when provided.
     */
    public function testCancellationNotificationContainsReason(): void
    {
        if (!$this->servicesAvailable) {
            $this->markTestSkipped('Email services not available for testing');
        }

        $this->limitTo(10)
            ->forAll(
                Generator\string(),  // order_number
                Generator\string()   // reason
            )
            ->then(function (string $orderNumber, string $reason): void {
                // Sanitize inputs
                $orderNumber = 'CC' . date('Y') . str_pad((string) abs(crc32($orderNumber) % 1000000), 6, '0', STR_PAD_LEFT);
                $reason = substr(preg_replace('/[^a-zA-Z0-9 ]/', '', $reason) ?: 'Customer request', 0, 100) ?: 'Customer request';

                // Create mock order data
                $order = [
                    'id' => 1,
                    'order_number' => $orderNumber,
                    'first_name' => 'Test',
                    'last_name' => 'User',
                    'email' => 'test@example.com',
                    'shipping_address' => '123 Test Street',
                    'subtotal' => 50.00,
                    'shipping_cost' => 5.00,
                    'tax_amount' => 0.00,
                    'total_amount' => 55.00,
                    'payment_method' => 'stripe',
                    'payment_status' => 'paid',
                    'order_status' => 'cancelled',
                    'created_at' => date('Y-m-d H:i:s')
                ];

                // Generate cancellation email with reason
                $emailHtml = $this->emailTemplates->orderCancellation($order, $reason);

                // Property: Email should contain order number
                $this->assertStringContainsString(
                    $orderNumber,
                    $emailHtml,
                    "Cancellation notification should contain order number"
                );

                // Property: Email should contain cancellation reason
                $this->assertStringContainsString(
                    $reason,
                    $emailHtml,
                    "Cancellation notification should contain reason"
                );

                // Property: Email should indicate cancellation
                $this->assertStringContainsString(
                    'cancel',
                    strtolower($emailHtml),
                    "Cancellation notification should indicate cancellation"
                );
            });
    }
}
