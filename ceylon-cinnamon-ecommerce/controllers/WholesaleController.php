<?php
/**
 * Wholesale Controller
 * Handles wholesale page display and inquiry submissions
 * 
 * Requirements:
 * - 13.1: Display wholesale inquiry form
 * - 13.2: Send notification to admin on inquiry submission
 * - 13.3: Display wholesale price tiers
 * - 13.4: Show wholesale pricing for wholesale customers
 * - 13.5: Support wholesale-specific product catalogs
 */

declare(strict_types=1);

class WholesaleController extends Controller
{
    private WholesaleInquiry $inquiryModel;
    private WholesalePriceTier $priceTierModel;
    private Product $productModel;
    private EmailService $emailService;

    public function __construct()
    {
        parent::__construct();
        $this->inquiryModel = new WholesaleInquiry();
        $this->priceTierModel = new WholesalePriceTier();
        $this->productModel = new Product();
        $this->emailService = new EmailService();
    }

    /**
     * Display wholesale page with inquiry form and price tiers
     * Requirements: 13.1, 13.3
     */
    public function index(): void
    {
        $seo = new SeoHelper();
        $seo->setTitle('Wholesale - Ceylon Cinnamon')
            ->setDescription('Wholesale pricing for Ceylon cinnamon products. Contact us for bulk orders and special pricing for retailers and distributors.')
            ->setCanonicalUrl(url('/wholesale'));

        // Get products with wholesale pricing (Requirement 13.3)
        $wholesaleProducts = $this->priceTierModel->getWholesaleProducts(12, 0);

        // Get price tiers for each product
        foreach ($wholesaleProducts as &$product) {
            $product['price_tiers'] = $this->priceTierModel->getTierSummary((int) $product['id']);
        }

        // Generate CSRF token
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $this->view('pages/wholesale', [
            'title' => 'Wholesale - Ceylon Cinnamon',
            'seo' => $seo,
            'wholesaleProducts' => $wholesaleProducts,
            'csrf_token' => $_SESSION['csrf_token']
        ]);
    }

    /**
     * Handle wholesale inquiry form submission
     * Requirements: 13.1, 13.2
     */
    public function submitInquiry(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/wholesale');
            return;
        }

        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
            $this->showError('Invalid form submission. Please try again.');
            return;
        }

        // Sanitize and validate input
        $data = $this->sanitizeInquiryData($_POST);
        $errors = $this->validateInquiryInput($data);

        if (!empty($errors)) {
            $this->showError(implode('<br>', $errors), $data);
            return;
        }

        try {
            // Create inquiry (Requirement 13.1)
            $inquiryId = $this->inquiryModel->createInquiry($data);

            // Send admin notification (Requirement 13.2)
            $this->sendAdminNotification($data, $inquiryId);

            // Send confirmation to customer
            $this->sendCustomerConfirmation($data);

            $this->showSuccess();
        } catch (Exception $e) {
            error_log("Wholesale inquiry error: " . $e->getMessage());
            $this->showError('An error occurred while submitting your inquiry. Please try again.', $data);
        }
    }

    /**
     * Sanitize inquiry form data
     */
    private function sanitizeInquiryData(array $post): array
    {
        return [
            'company_name' => $this->sanitize($post['company_name'] ?? ''),
            'contact_name' => $this->sanitize($post['contact_name'] ?? ''),
            'email' => filter_var(trim($post['email'] ?? ''), FILTER_SANITIZE_EMAIL),
            'phone' => $this->sanitize($post['phone'] ?? ''),
            'country' => $this->sanitize($post['country'] ?? ''),
            'business_type' => $this->sanitize($post['business_type'] ?? ''),
            'estimated_quantity' => $this->sanitize($post['estimated_quantity'] ?? ''),
            'products_interested' => $this->sanitize($post['products_interested'] ?? ''),
            'message' => $this->sanitize($post['message'] ?? '')
        ];
    }

    /**
     * Validate inquiry input
     */
    private function validateInquiryInput(array $data): array
    {
        $errors = [];

        if (empty($data['company_name'])) {
            $errors[] = 'Company name is required.';
        }

        if (empty($data['contact_name'])) {
            $errors[] = 'Contact name is required.';
        }

        if (empty($data['email'])) {
            $errors[] = 'Email address is required.';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }

        return $errors;
    }

    /**
     * Send notification email to admin
     * Requirement 13.2
     */
    private function sendAdminNotification(array $data, int $inquiryId): void
    {
        $adminEmail = defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'admin@ceyloncinnamon.com';
        
        $subject = "New Wholesale Inquiry #{$inquiryId} - {$data['company_name']}";
        
        $html = $this->emailService->getEmailHeader();
        $html .= <<<HTML
            <h2 style="color: #8B4513; margin-bottom: 20px;">New Wholesale Inquiry</h2>
            
            <p>A new wholesale inquiry has been submitted:</p>
            
            <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd; font-weight: bold; width: 40%;">Inquiry ID:</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">#{$inquiryId}</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd; font-weight: bold;">Company Name:</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">{$data['company_name']}</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd; font-weight: bold;">Contact Name:</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">{$data['contact_name']}</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd; font-weight: bold;">Email:</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;"><a href="mailto:{$data['email']}">{$data['email']}</a></td>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd; font-weight: bold;">Phone:</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">{$data['phone']}</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd; font-weight: bold;">Country:</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">{$data['country']}</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd; font-weight: bold;">Business Type:</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">{$data['business_type']}</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd; font-weight: bold;">Estimated Quantity:</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">{$data['estimated_quantity']}</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd; font-weight: bold;">Products Interested:</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">{$data['products_interested']}</td>
                </tr>
            </table>
            
            <h3 style="color: #8B4513; margin-top: 20px;">Message:</h3>
            <p style="background-color: #f9f9f9; padding: 15px; border-radius: 4px;">{$data['message']}</p>
            
            <p style="margin-top: 20px;">
                <a href="URL_PLACEHOLDER/admin/wholesale/{$inquiryId}" class="btn" style="display: inline-block; padding: 12px 24px; background-color: #8B4513; color: #ffffff; text-decoration: none; border-radius: 4px;">View in Admin Panel</a>
            </p>
HTML;
        $html .= $this->emailService->getEmailFooter();

        $this->emailService->send($adminEmail, $subject, $html);
    }

    /**
     * Send confirmation email to customer
     */
    private function sendCustomerConfirmation(array $data): void
    {
        $subject = "Thank You for Your Wholesale Inquiry - Ceylon Cinnamon";
        
        $html = $this->emailService->getEmailHeader();
        $html .= <<<HTML
            <h2 style="color: #8B4513; margin-bottom: 20px;">Thank You for Your Inquiry</h2>
            
            <p>Dear {$data['contact_name']},</p>
            
            <p>Thank you for your interest in our wholesale Ceylon cinnamon products. We have received your inquiry and our team will review it shortly.</p>
            
            <p>Here's a summary of your inquiry:</p>
            
            <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd; font-weight: bold; width: 40%;">Company:</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">{$data['company_name']}</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd; font-weight: bold;">Business Type:</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">{$data['business_type']}</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd; font-weight: bold;">Estimated Quantity:</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">{$data['estimated_quantity']}</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd; font-weight: bold;">Products Interested:</td>
                    <td style="padding: 10px; border-bottom: 1px solid #ddd;">{$data['products_interested']}</td>
                </tr>
            </table>
            
            <p>Our wholesale team typically responds within 1-2 business days. If you have any urgent questions, please don't hesitate to contact us directly.</p>
            
            <p>Best regards,<br>The Ceylon Cinnamon Team</p>
HTML;
        $html .= $this->emailService->getEmailFooter();

        $this->emailService->send($data['email'], $subject, $html);
    }

    /**
     * Show error message
     */
    private function showError(string $error, array $formData = []): void
    {
        $seo = new SeoHelper();
        $seo->setTitle('Wholesale - Ceylon Cinnamon');

        $wholesaleProducts = $this->priceTierModel->getWholesaleProducts(12, 0);
        foreach ($wholesaleProducts as &$product) {
            $product['price_tiers'] = $this->priceTierModel->getTierSummary((int) $product['id']);
        }

        $this->view('pages/wholesale', [
            'title' => 'Wholesale - Ceylon Cinnamon',
            'seo' => $seo,
            'wholesaleProducts' => $wholesaleProducts,
            'error' => $error,
            'formData' => $formData,
            'csrf_token' => $_SESSION['csrf_token'] ?? ''
        ]);
    }

    /**
     * Show success message
     */
    private function showSuccess(): void
    {
        $seo = new SeoHelper();
        $seo->setTitle('Wholesale - Ceylon Cinnamon');

        $wholesaleProducts = $this->priceTierModel->getWholesaleProducts(12, 0);
        foreach ($wholesaleProducts as &$product) {
            $product['price_tiers'] = $this->priceTierModel->getTierSummary((int) $product['id']);
        }

        $this->view('pages/wholesale', [
            'title' => 'Wholesale - Ceylon Cinnamon',
            'seo' => $seo,
            'wholesaleProducts' => $wholesaleProducts,
            'success' => 'Thank you for your wholesale inquiry! Our team will contact you within 1-2 business days.',
            'csrf_token' => $_SESSION['csrf_token'] ?? ''
        ]);
    }

    /**
     * Get wholesale pricing for a product (API endpoint)
     * Requirement 13.4
     */
    public function getProductPricing(int $productId): void
    {
        $product = $this->productModel->find($productId);
        
        if (!$product) {
            $this->json(['error' => 'Product not found'], 404);
            return;
        }

        $tiers = $this->priceTierModel->getProductTiers($productId);
        $minQuantity = $this->priceTierModel->getMinimumQuantity($productId);

        $this->json([
            'product_id' => $productId,
            'product_name' => $product['name'],
            'retail_price' => (float) $product['price'],
            'minimum_quantity' => $minQuantity,
            'price_tiers' => $tiers
        ]);
    }
}
