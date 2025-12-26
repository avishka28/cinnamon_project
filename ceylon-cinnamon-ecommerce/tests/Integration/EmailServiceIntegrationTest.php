<?php
/**
 * Email Service Integration Test
 * Tests email service integration and template generation
 * 
 * Requirements:
 * - 12.4: Use SMTP configuration for reliable email delivery
 * - 12.5: Include order details and company branding in all emails
 */

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

class EmailServiceIntegrationTest extends TestCase
{
    private \EmailService $emailService;
    private \EmailTemplates $emailTemplates;

    protected function setUp(): void
    {
        parent::setUp();
        $this->emailService = new \EmailService();
        $this->emailTemplates = new \EmailTemplates($this->emailService);
    }

    /**
     * Test email service initialization
     * Requirement 12.4: SMTP configuration
     */
    public function testEmailServiceInitialization(): void
    {
        $this->assertInstanceOf(\EmailService::class, $this->emailService);
    }

    /**
     * Test company info retrieval
     * Requirement 12.5: Include company branding
     */
    public function testCompanyInfoRetrieval(): void
    {
        $companyInfo = $this->emailService->getCompanyInfo();
        
        $requiredFields = ['name', 'address', 'city', 'country', 'phone', 'email', 'website'];
        
        foreach ($requiredFields as $field) {
            $this->assertArrayHasKey($field, $companyInfo);
            $this->assertNotEmpty($companyInfo[$field]);
        }
    }

    /**
     * Test email header generation
     * Requirement 12.5: Include company branding
     */
    public function testEmailHeaderGeneration(): void
    {
        $header = $this->emailService->getEmailHeader();
        
        // Header should contain HTML structure
        $this->assertStringContainsString('<!DOCTYPE html>', $header);
        $this->assertStringContainsString('<html>', $header);
        $this->assertStringContainsString('<head>', $header);
        $this->assertStringContainsString('<body>', $header);
        
        // Header should contain company branding
        $companyInfo = $this->emailService->getCompanyInfo();
        $this->assertStringContainsString($companyInfo['name'], $header);
    }

    /**
     * Test email footer generation
     * Requirement 12.5: Include company branding
     */
    public function testEmailFooterGeneration(): void
    {
        $footer = $this->emailService->getEmailFooter();
        
        // Footer should close HTML structure
        $this->assertStringContainsString('</body>', $footer);
        $this->assertStringContainsString('</html>', $footer);
        
        // Footer should contain company info
        $companyInfo = $this->emailService->getCompanyInfo();
        $this->assertStringContainsString($companyInfo['name'], $footer);
        $this->assertStringContainsString($companyInfo['phone'], $footer);
    }

    /**
     * Test email validation
     */
    public function testEmailValidation(): void
    {
        // Valid emails
        $this->assertTrue($this->emailService->isValidEmail('test@example.com'));
        $this->assertTrue($this->emailService->isValidEmail('user.name@domain.co.uk'));
        
        // Invalid emails
        $this->assertFalse($this->emailService->isValidEmail('invalid'));
        $this->assertFalse($this->emailService->isValidEmail('invalid@'));
        $this->assertFalse($this->emailService->isValidEmail('@domain.com'));
        $this->assertFalse($this->emailService->isValidEmail(''));
    }

    /**
     * Test from email retrieval
     */
    public function testFromEmailRetrieval(): void
    {
        $fromEmail = $this->emailService->getFromEmail();
        
        $this->assertNotEmpty($fromEmail);
        $this->assertTrue($this->emailService->isValidEmail($fromEmail));
    }

    /**
     * Test from name retrieval
     */
    public function testFromNameRetrieval(): void
    {
        $fromName = $this->emailService->getFromName();
        
        $this->assertNotEmpty($fromName);
    }

    /**
     * Test order confirmation template generation
     * Requirement 12.1: Order confirmation emails
     */
    public function testOrderConfirmationTemplateGeneration(): void
    {
        $orderData = [
            'order_number' => 'CC2025123456',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'total_amount' => 150.00,
            'subtotal' => 140.00,
            'shipping_cost' => 10.00,
            'tax_amount' => 0.00,
            'payment_method' => 'stripe',
            'order_status' => 'pending',
            'shipping_address' => '123 Test St, City, Country',
            'phone' => '+1234567890',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $items = [
            [
                'product_name' => 'Ceylon Cinnamon Sticks',
                'product_sku' => 'CCS-001',
                'quantity' => 2,
                'price' => 25.00,
                'total' => 50.00
            ],
            [
                'product_name' => 'Cinnamon Powder',
                'product_sku' => 'CP-001',
                'quantity' => 1,
                'price' => 100.00,
                'total' => 100.00
            ]
        ];
        
        $html = $this->emailTemplates->orderConfirmation($orderData, $items);
        
        // Template should contain order details
        $this->assertStringContainsString($orderData['order_number'], $html);
        $this->assertStringContainsString($orderData['first_name'], $html);
        
        // Template should contain items
        foreach ($items as $item) {
            $this->assertStringContainsString($item['product_name'], $html);
        }
    }

    /**
     * Test order status update template generation
     * Requirement 12.2: Status update emails
     */
    public function testOrderStatusUpdateTemplateGeneration(): void
    {
        $orderData = [
            'order_number' => 'CC2025123456',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'order_status' => 'shipped'
        ];
        
        $html = $this->emailTemplates->orderStatusUpdate($orderData, 'shipped');
        
        // Template should contain order number
        $this->assertStringContainsString($orderData['order_number'], $html);
        
        // Template should mention the new status
        $this->assertStringContainsString('shipped', strtolower($html));
    }

    /**
     * Test shipping notification template generation
     * Requirement 12.3: Shipping notification with tracking
     */
    public function testShippingNotificationTemplateGeneration(): void
    {
        $orderData = [
            'order_number' => 'CC2025123456',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'shipping_address' => '123 Test St, City, Country'
        ];
        
        $html = $this->emailTemplates->shippingNotification(
            $orderData,
            'TRACK123456',
            'DHL',
            'https://tracking.dhl.com/TRACK123456'
        );
        
        // Template should contain tracking info
        $this->assertStringContainsString('TRACK123456', $html);
        $this->assertStringContainsString('DHL', $html);
    }

    /**
     * Test email templates include company branding
     * Requirement 12.5: Include company branding
     */
    public function testEmailTemplatesIncludeCompanyBranding(): void
    {
        $orderData = [
            'order_number' => 'CC2025123456',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'total_amount' => 100.00,
            'subtotal' => 100.00,
            'shipping_cost' => 0.00,
            'tax_amount' => 0.00,
            'payment_method' => 'stripe',
            'order_status' => 'pending',
            'shipping_address' => '123 Test St',
            'phone' => '',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $html = $this->emailTemplates->orderConfirmation($orderData, []);
        
        // Should contain company name
        $companyInfo = $this->emailService->getCompanyInfo();
        $this->assertStringContainsString($companyInfo['name'], $html);
    }

    /**
     * Test email template CSS styling
     */
    public function testEmailTemplateCssStyling(): void
    {
        $header = $this->emailService->getEmailHeader();
        
        // Should contain inline CSS styles
        $this->assertStringContainsString('<style>', $header);
        $this->assertStringContainsString('font-family', $header);
    }

    /**
     * Test email template responsive design
     */
    public function testEmailTemplateResponsiveDesign(): void
    {
        $header = $this->emailService->getEmailHeader();
        
        // Should contain viewport meta tag
        $this->assertStringContainsString('viewport', $header);
        
        // Should have max-width for email container
        $this->assertStringContainsString('max-width', $header);
    }

    /**
     * Test order cancellation template
     */
    public function testOrderCancellationTemplate(): void
    {
        $orderData = [
            'order_number' => 'CC2025123456',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com'
        ];
        
        $html = $this->emailTemplates->orderCancellation($orderData, 'Customer requested cancellation');
        
        // Should contain order number
        $this->assertStringContainsString($orderData['order_number'], $html);
        
        // Should mention cancellation
        $this->assertStringContainsString('cancel', strtolower($html));
    }

    /**
     * Test delivery confirmation template
     */
    public function testDeliveryConfirmationTemplate(): void
    {
        $orderData = [
            'order_number' => 'CC2025123456',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com'
        ];
        
        $html = $this->emailTemplates->deliveryConfirmation($orderData);
        
        // Should contain order number
        $this->assertStringContainsString($orderData['order_number'], $html);
        
        // Should mention delivery
        $this->assertStringContainsString('deliver', strtolower($html));
    }
}
