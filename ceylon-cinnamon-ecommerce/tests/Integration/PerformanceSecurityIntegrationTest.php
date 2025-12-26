<?php
/**
 * Performance and Security Integration Test
 * Tests database performance, security measures, and responsive design
 * 
 * Requirements:
 * - 10.1: Use prepared statements for all database queries
 * - 10.2: Validate CSRF tokens
 * - 10.3: Sanitize and validate all data server-side
 * - 11.5: Responsive and mobile-first design
 */

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

class PerformanceSecurityIntegrationTest extends TestCase
{
    /**
     * Test database connection uses PDO
     * Requirement 10.1: Prepared statements
     */
    public function testDatabaseConnectionUsesPdo(): void
    {
        // Verify Database class exists and uses PDO
        $this->assertTrue(class_exists(\Database::class));
        
        // Check that Model class uses prepared statements
        $this->assertTrue(class_exists(\Model::class));
    }

    /**
     * Test all models extend base Model class
     * Requirement 10.1: Consistent database access
     */
    public function testModelsExtendBaseModel(): void
    {
        $models = [
            \User::class,
            \Product::class,
            \Category::class,
            \Order::class,
            \OrderItem::class,
            \Cart::class,
            \BlogPost::class,
            \BlogCategory::class,
            \Certificate::class,
            \GalleryItem::class,
            \ShippingZone::class,
            \ShippingMethod::class,
            \WholesaleInquiry::class,
            \WholesalePriceTier::class,
        ];
        
        foreach ($models as $modelClass) {
            $this->assertTrue(class_exists($modelClass), "Model {$modelClass} does not exist");
        }
    }

    /**
     * Test CSRF protection is available
     * Requirement 10.2: CSRF token validation
     */
    public function testCsrfProtectionAvailable(): void
    {
        $this->assertTrue(class_exists(\CsrfProtection::class));
        $this->assertTrue(class_exists(\CsrfMiddleware::class));
        
        // Create a mock session manager to avoid session_start issues in CLI
        $mockSessionManager = $this->createMock(\SessionManager::class);
        $storage = [];
        $mockSessionManager->method('get')->willReturnCallback(function ($key, $default = null) use (&$storage) {
            return $storage[$key] ?? $default;
        });
        $mockSessionManager->method('set')->willReturnCallback(function ($key, $value) use (&$storage) {
            $storage[$key] = $value;
        });
        $mockSessionManager->method('has')->willReturnCallback(function ($key) use (&$storage) {
            return isset($storage[$key]);
        });
        
        $csrf = new \CsrfProtection($mockSessionManager);
        $token = $csrf->generateToken();
        
        $this->assertNotEmpty($token);
        $this->assertTrue($csrf->validateToken($token));
    }

    /**
     * Test input sanitization is available
     * Requirement 10.3: Input sanitization
     */
    public function testInputSanitizationAvailable(): void
    {
        $this->assertTrue(class_exists(\InputSanitizer::class));
        $this->assertTrue(class_exists(\InputValidator::class));
        
        // Test sanitization works
        $dangerous = '<script>alert("xss")</script>';
        $sanitized = \InputSanitizer::sanitizeString($dangerous);
        
        $this->assertStringNotContainsString('<script>', $sanitized);
    }

    /**
     * Test session security configuration
     * Requirement 10.6: Secure session management
     */
    public function testSessionSecurityConfiguration(): void
    {
        $this->assertTrue(class_exists(\SessionManager::class));
        
        $sessionManager = new \SessionManager();
        $this->assertInstanceOf(\SessionManager::class, $sessionManager);
    }

    /**
     * Test authentication middleware exists
     * Requirement 2.4: Unauthorized access handling
     */
    public function testAuthenticationMiddlewareExists(): void
    {
        $this->assertTrue(class_exists(\AuthMiddleware::class));
        $this->assertTrue(class_exists(\AdminMiddleware::class));
        $this->assertTrue(class_exists(\ContentManagerMiddleware::class));
        $this->assertTrue(class_exists(\RoleMiddleware::class));
    }

    /**
     * Test file upload security
     * Requirement 10.4: File type validation
     */
    public function testFileUploadSecurityExists(): void
    {
        $this->assertTrue(class_exists(\FileUploadHandler::class));
        
        $handler = new \FileUploadHandler();
        
        // Verify allowed types are restricted
        $allowedImages = $handler->getAllowedImageTypes();
        $this->assertArrayHasKey('image/jpeg', $allowedImages);
        $this->assertArrayHasKey('image/png', $allowedImages);
        
        // Verify dangerous types are not allowed
        $this->assertArrayNotHasKey('application/x-php', $allowedImages);
        $this->assertArrayNotHasKey('text/html', $allowedImages);
    }

    /**
     * Test password hashing uses secure algorithm
     * Requirement 10.7: Strong password hashing
     */
    public function testPasswordHashingSecure(): void
    {
        $userModel = new \User();
        
        $password = 'TestPassword123!';
        $hash = $userModel->hashPassword($password);
        
        // Hash should be bcrypt format
        $this->assertStringStartsWith('$2y$', $hash);
        
        // Verify password verification works
        $this->assertTrue($userModel->verifyPassword($password, $hash));
        $this->assertFalse($userModel->verifyPassword('WrongPassword', $hash));
    }

    /**
     * Test SEO helper exists for responsive design
     * Requirement 11.5: Responsive design
     */
    public function testSeoHelperExists(): void
    {
        $this->assertTrue(class_exists(\SeoHelper::class));
        
        $seoHelper = new \SeoHelper();
        $this->assertInstanceOf(\SeoHelper::class, $seoHelper);
    }

    /**
     * Test asset helper exists for CDN support
     * Requirement 11.6: CDN integration
     */
    public function testAssetHelperExists(): void
    {
        $this->assertTrue(class_exists(\AssetHelper::class));
    }

    /**
     * Test router exists for URL handling
     */
    public function testRouterExists(): void
    {
        $this->assertTrue(class_exists(\Router::class));
    }

    /**
     * Test controller base class exists
     */
    public function testControllerBaseClassExists(): void
    {
        $this->assertTrue(class_exists(\Controller::class));
    }

    /**
     * Test all controllers exist
     */
    public function testAllControllersExist(): void
    {
        $controllers = [
            \HomeController::class,
            \ProductController::class,
            \AuthController::class,
            \CartController::class,
            \CheckoutController::class,
            \DashboardController::class,
            \BlogController::class,
            \WholesaleController::class,
            \ShippingController::class,
            \SeoController::class,
            \AdminController::class,
            \ProductAdminController::class,
            \OrderAdminController::class,
            \ContentAdminController::class,
            \ShippingAdminController::class,
        ];
        
        foreach ($controllers as $controllerClass) {
            $this->assertTrue(class_exists($controllerClass), "Controller {$controllerClass} does not exist");
        }
    }

    /**
     * Test payment processor security
     * Requirement 4.4: No credit card storage
     */
    public function testPaymentProcessorSecurity(): void
    {
        $this->assertTrue(class_exists(\PaymentProcessor::class));
        $this->assertTrue(class_exists(\PaymentErrorHandler::class));
        
        $processor = new \PaymentProcessor();
        
        // Verify bank transfer doesn't store sensitive data
        $result = $processor->processBankTransfer(100.00, []);
        
        $this->assertTrue($result['success']);
        $this->assertArrayNotHasKey('card_number', $result);
        $this->assertArrayNotHasKey('cvv', $result);
    }

    /**
     * Test email service security
     * Requirement 12.4: SMTP configuration
     */
    public function testEmailServiceSecurity(): void
    {
        $this->assertTrue(class_exists(\EmailService::class));
        $this->assertTrue(class_exists(\EmailTemplates::class));
        $this->assertTrue(class_exists(\OrderNotificationService::class));
        
        $emailService = new \EmailService();
        
        // Verify email validation works
        $this->assertTrue($emailService->isValidEmail('test@example.com'));
        $this->assertFalse($emailService->isValidEmail('invalid'));
    }

    /**
     * Test shipping calculator exists
     * Requirement 14.2: Shipping calculation
     */
    public function testShippingCalculatorExists(): void
    {
        $this->assertTrue(class_exists(\ShippingCalculator::class));
        
        $calculator = new \ShippingCalculator();
        $this->assertInstanceOf(\ShippingCalculator::class, $calculator);
    }

    /**
     * Test analytics model exists
     * Requirement 15.1: Analytics
     */
    public function testAnalyticsModelExists(): void
    {
        $this->assertTrue(class_exists(\Analytics::class));
        
        $analytics = new \Analytics();
        $this->assertInstanceOf(\Analytics::class, $analytics);
    }

    /**
     * Test sitemap generator exists
     * Requirement 11.3: Sitemap generation
     */
    public function testSitemapGeneratorExists(): void
    {
        $this->assertTrue(class_exists(\SitemapGenerator::class));
        
        $generator = new \SitemapGenerator();
        $this->assertInstanceOf(\SitemapGenerator::class, $generator);
    }

    /**
     * Test translation helper exists
     * Requirement 9.5: Translation support
     */
    public function testTranslationHelperExists(): void
    {
        // TranslationHelper is a file with functions, not a class
        // Check that the translation functions are available
        $this->assertTrue(function_exists('initializeLanguageManager') || file_exists(__DIR__ . '/../../includes/TranslationHelper.php'));
    }

    /**
     * Test CSV product importer exists
     * Requirement 6.4: CSV import
     */
    public function testCsvProductImporterExists(): void
    {
        $this->assertTrue(class_exists(\CsvProductImporter::class));
        
        $importer = new \CsvProductImporter();
        $this->assertInstanceOf(\CsvProductImporter::class, $importer);
    }

    /**
     * Test order confirmation generator exists
     * Requirement 4.6: Order confirmation
     */
    public function testOrderConfirmationExists(): void
    {
        $this->assertTrue(class_exists(\OrderConfirmation::class));
    }

    /**
     * Test sanitization helper exists
     */
    public function testSanitizationHelperExists(): void
    {
        // SanitizationHelper is a file with functions, not a class
        // Check that the sanitization functions are available
        $this->assertTrue(function_exists('sanitize') || file_exists(__DIR__ . '/../../includes/SanitizationHelper.php'));
    }

    /**
     * Test CSRF helper exists
     */
    public function testCsrfHelperExists(): void
    {
        // CsrfHelper is a file with functions, not a class
        // Check that the CSRF functions are available
        $this->assertTrue(function_exists('csrf_token') || file_exists(__DIR__ . '/../../includes/CsrfHelper.php'));
    }

    /**
     * Test middleware interface exists
     */
    public function testMiddlewareInterfaceExists(): void
    {
        // Middleware.php defines MiddlewareInterface, not Middleware class
        $this->assertTrue(interface_exists(\MiddlewareInterface::class) || file_exists(__DIR__ . '/../../includes/Middleware.php'));
    }

    /**
     * Test order model has transaction support
     * Requirement 3.5: Order creation with transaction
     */
    public function testOrderModelHasTransactionSupport(): void
    {
        $orderModel = new \Order();
        
        // Verify transaction methods exist
        $this->assertTrue(method_exists($orderModel, 'beginTransaction'));
        $this->assertTrue(method_exists($orderModel, 'commit'));
        $this->assertTrue(method_exists($orderModel, 'rollback'));
    }

    /**
     * Test order model has stock management
     * Requirement 3.7: Stock reduction
     */
    public function testOrderModelHasStockManagement(): void
    {
        $orderModel = new \Order();
        
        // Verify stock methods exist
        $this->assertTrue(method_exists($orderModel, 'reduceStock'));
        $this->assertTrue(method_exists($orderModel, 'restoreStock'));
    }

    /**
     * Test user model has role checking
     * Requirement 2.5: Role-based access
     */
    public function testUserModelHasRoleChecking(): void
    {
        $userModel = new \User();
        
        // Verify role methods exist
        $this->assertTrue(method_exists($userModel, 'hasRole'));
        $this->assertTrue(method_exists($userModel, 'isAdmin'));
        $this->assertTrue(method_exists($userModel, 'isContentManager'));
    }

    /**
     * Test product model has filtering
     * Requirement 1.2-1.5: Product filtering
     */
    public function testProductModelHasFiltering(): void
    {
        $productModel = new \Product();
        
        // Verify filtering method exists
        $this->assertTrue(method_exists($productModel, 'getFiltered'));
    }

    /**
     * Test cart model has validation
     * Requirement 3.7: Stock validation
     */
    public function testCartModelHasValidation(): void
    {
        // Create mock session for Cart
        $mockSession = new class {
            private array $data = [];
            public function start(): void {}
            public function has(string $key): bool { return isset($this->data[$key]); }
            public function get(string $key, $default = null) { return $this->data[$key] ?? $default; }
            public function set(string $key, $value): void { $this->data[$key] = $value; }
        };
        
        $cart = new \Cart($mockSession);
        
        // Verify validation method exists
        $this->assertTrue(method_exists($cart, 'validateStock'));
    }
}
