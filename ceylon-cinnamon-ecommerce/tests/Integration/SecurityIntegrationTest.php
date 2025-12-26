<?php
/**
 * Security Integration Test
 * Tests security measures and access controls
 * 
 * Requirements:
 * - 10.1: Use prepared statements for all database queries
 * - 10.2: Validate CSRF tokens
 * - 10.3: Sanitize and validate all data server-side
 */

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

class SecurityIntegrationTest extends TestCase
{
    private \CsrfProtection $csrfProtection;
    private $mockSessionManager;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a mock session manager to avoid session_start issues in CLI
        $this->mockSessionManager = $this->createMock(\SessionManager::class);
        
        // Mock the get method to return from a local array
        $storage = [];
        $this->mockSessionManager->method('get')->willReturnCallback(function ($key, $default = null) use (&$storage) {
            return $storage[$key] ?? $default;
        });
        
        $this->mockSessionManager->method('set')->willReturnCallback(function ($key, $value) use (&$storage) {
            $storage[$key] = $value;
        });
        
        $this->mockSessionManager->method('has')->willReturnCallback(function ($key) use (&$storage) {
            return isset($storage[$key]);
        });
        
        $this->csrfProtection = new \CsrfProtection($this->mockSessionManager);
    }

    /**
     * Test CSRF token generation
     * Requirement 10.2: CSRF protection
     */
    public function testCsrfTokenGeneration(): void
    {
        $token = $this->csrfProtection->generateToken();
        
        $this->assertNotEmpty($token);
        $this->assertIsString($token);
        $this->assertGreaterThanOrEqual(32, strlen($token));
    }

    /**
     * Test CSRF token uniqueness
     * Requirement 10.2: CSRF protection
     */
    public function testCsrfTokenUniqueness(): void
    {
        $tokens = [];
        for ($i = 0; $i < 10; $i++) {
            // Create new mock for each token to ensure uniqueness
            $mockSession = $this->createMock(\SessionManager::class);
            $storage = [];
            $mockSession->method('get')->willReturnCallback(function ($key, $default = null) use (&$storage) {
                return $storage[$key] ?? $default;
            });
            $mockSession->method('set')->willReturnCallback(function ($key, $value) use (&$storage) {
                $storage[$key] = $value;
            });
            
            $csrf = new \CsrfProtection($mockSession);
            $tokens[] = $csrf->generateToken();
        }
        
        $uniqueTokens = array_unique($tokens);
        $this->assertCount(10, $uniqueTokens);
    }

    /**
     * Test CSRF token validation
     * Requirement 10.2: CSRF protection
     */
    public function testCsrfTokenValidation(): void
    {
        $token = $this->csrfProtection->generateToken();
        
        // Valid token should pass
        $this->assertTrue($this->csrfProtection->validateToken($token));
        
        // Invalid token should fail
        $this->assertFalse($this->csrfProtection->validateToken('invalid_token'));
        
        // Empty token should fail
        $this->assertFalse($this->csrfProtection->validateToken(''));
    }

    /**
     * Test input sanitization - XSS prevention
     * Requirement 10.3: Sanitize all data
     */
    public function testInputSanitizationXssPrevention(): void
    {
        $xssAttempts = [
            '<script>alert("xss")</script>' => '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;',
            '<img src="x" onerror="alert(1)">' => '&lt;img src=&quot;x&quot; onerror=&quot;alert(1)&quot;&gt;',
            'javascript:alert(1)' => 'javascript:alert(1)', // URL scheme, not HTML
            '<a href="javascript:void(0)">click</a>' => '&lt;a href=&quot;javascript:void(0)&quot;&gt;click&lt;/a&gt;',
        ];
        
        foreach ($xssAttempts as $input => $expected) {
            $sanitized = \InputSanitizer::sanitizeString($input);
            $this->assertEquals($expected, $sanitized, "Failed for input: {$input}");
        }
    }

    /**
     * Test input sanitization - HTML entities
     * Requirement 10.3: Sanitize all data
     */
    public function testInputSanitizationHtmlEntities(): void
    {
        $input = '<div class="test">Hello & World</div>';
        $sanitized = \InputSanitizer::sanitizeString($input);
        
        $this->assertStringNotContainsString('<div', $sanitized);
        $this->assertStringContainsString('&lt;', $sanitized);
        $this->assertStringContainsString('&amp;', $sanitized);
    }

    /**
     * Test input sanitization - trim whitespace
     * Requirement 10.3: Sanitize all data
     */
    public function testInputSanitizationTrimWhitespace(): void
    {
        $input = '   test value   ';
        $sanitized = \InputSanitizer::sanitizeString($input);
        
        $this->assertEquals('test value', $sanitized);
    }

    /**
     * Test email validation using InputValidator
     * Requirement 10.3: Validate all data
     */
    public function testEmailValidation(): void
    {
        // Valid emails
        $validator = new \InputValidator(['email' => 'test@example.com']);
        $validator->email('email');
        $this->assertTrue($validator->passes());
        
        $validator = new \InputValidator(['email' => 'user.name@domain.co.uk']);
        $validator->email('email');
        $this->assertTrue($validator->passes());
        
        // Invalid emails
        $validator = new \InputValidator(['email' => 'invalid']);
        $validator->email('email');
        $this->assertTrue($validator->fails());
    }

    /**
     * Test numeric validation
     * Requirement 10.3: Validate all data
     */
    public function testNumericValidation(): void
    {
        // Valid numeric
        $validator = new \InputValidator(['price' => '100.00']);
        $validator->numeric('price');
        $this->assertTrue($validator->passes());
        
        // Invalid numeric
        $validator = new \InputValidator(['price' => 'abc']);
        $validator->numeric('price');
        $this->assertTrue($validator->fails());
    }

    /**
     * Test minimum value validation
     * Requirement 10.3: Validate all data
     */
    public function testMinValueValidation(): void
    {
        // Valid - above minimum
        $validator = new \InputValidator(['quantity' => '5']);
        $validator->numeric('quantity')->min('quantity', 1);
        $this->assertTrue($validator->passes());
        
        // Invalid - below minimum (0.5 is below 1)
        $validator = new \InputValidator(['quantity' => '0.5']);
        $validator->numeric('quantity')->min('quantity', 1);
        $this->assertTrue($validator->fails());
    }

    /**
     * Test phone validation
     */
    public function testPhoneValidation(): void
    {
        // Valid phone numbers
        $validator = new \InputValidator(['phone' => '+1234567890']);
        $validator->phone('phone');
        $this->assertTrue($validator->passes());
        
        $validator = new \InputValidator(['phone' => '123-456-7890']);
        $validator->phone('phone');
        $this->assertTrue($validator->passes());
        
        // Invalid phone numbers
        $validator = new \InputValidator(['phone' => 'abc']);
        $validator->phone('phone');
        $this->assertTrue($validator->fails());
    }

    /**
     * Test required field validation
     */
    public function testRequiredFieldValidation(): void
    {
        // Valid - field present
        $validator = new \InputValidator(['name' => 'John']);
        $validator->required('name');
        $this->assertTrue($validator->passes());
        
        // Invalid - field empty
        $validator = new \InputValidator(['name' => '']);
        $validator->required('name');
        $this->assertTrue($validator->fails());
        
        // Invalid - field missing
        $validator = new \InputValidator([]);
        $validator->required('name');
        $this->assertTrue($validator->fails());
    }

    /**
     * Test SQL injection prevention in sanitization
     * Requirement 10.1: SQL injection prevention
     */
    public function testSqlInjectionPrevention(): void
    {
        $sqlInjectionAttempts = [
            "'; DROP TABLE users; --",
            "1' OR '1'='1",
        ];
        
        foreach ($sqlInjectionAttempts as $input) {
            $sanitized = \InputSanitizer::sanitizeString($input);
            
            // Sanitized output should have special characters escaped
            $this->assertNotEquals($input, $sanitized);
        }
    }

    /**
     * Test SQL injection detection
     * Requirement 10.1: SQL injection prevention
     */
    public function testSqlInjectionDetection(): void
    {
        // These patterns should be detected
        $this->assertTrue(\InputSanitizer::containsSqlInjection("'; DROP TABLE users; --"));
        $this->assertTrue(\InputSanitizer::containsSqlInjection("SELECT * FROM users"));
        $this->assertTrue(\InputSanitizer::containsSqlInjection("1 OR 1=1"));
        
        // These should not be detected
        $this->assertFalse(\InputSanitizer::containsSqlInjection("normal text"));
        $this->assertFalse(\InputSanitizer::containsSqlInjection("john@example.com"));
    }

    /**
     * Test XSS detection
     * Requirement 10.3: XSS prevention
     */
    public function testXssDetection(): void
    {
        $this->assertTrue(\InputSanitizer::containsXss('<script>alert(1)</script>'));
        $this->assertTrue(\InputSanitizer::containsXss('javascript:alert(1)'));
        $this->assertTrue(\InputSanitizer::containsXss('<img onerror="alert(1)">'));
        
        $this->assertFalse(\InputSanitizer::containsXss('normal text'));
        $this->assertFalse(\InputSanitizer::containsXss('Hello World'));
    }

    /**
     * Test session security configuration
     * Requirement 10.6: Secure session management
     */
    public function testSessionSecurityConfiguration(): void
    {
        $sessionManager = new \SessionManager();
        
        // Session manager should exist
        $this->assertInstanceOf(\SessionManager::class, $sessionManager);
    }

    /**
     * Test password hashing
     * Requirement 10.7: Strong password hashing
     */
    public function testPasswordHashing(): void
    {
        $password = 'TestPassword123!';
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Hash should be different from password
        $this->assertNotEquals($password, $hash);
        
        // Hash should be verifiable
        $this->assertTrue(password_verify($password, $hash));
        
        // Wrong password should not verify
        $this->assertFalse(password_verify('WrongPassword', $hash));
    }

    /**
     * Test role-based access control middleware
     * Requirement 2.5, 2.6, 2.7: Role-based access
     */
    public function testRoleMiddlewareExists(): void
    {
        $this->assertTrue(class_exists(\RoleMiddleware::class));
        $this->assertTrue(class_exists(\AdminMiddleware::class));
        $this->assertTrue(class_exists(\ContentManagerMiddleware::class));
    }

    /**
     * Test input sanitizer handles empty values
     */
    public function testInputSanitizerHandlesEmptyValues(): void
    {
        $result = \InputSanitizer::sanitizeString('');
        $this->assertEquals('', $result);
    }

    /**
     * Test input sanitizer handles arrays recursively
     */
    public function testInputSanitizerHandlesArraysRecursively(): void
    {
        $input = ['<script>alert(1)</script>', 'normal text'];
        $sanitized = \InputSanitizer::arrayRecursive($input);
        
        $this->assertIsArray($sanitized);
        $this->assertStringNotContainsString('<script>', $sanitized[0]);
        $this->assertEquals('normal text', $sanitized[1]);
    }

    /**
     * Test CSRF middleware exists
     * Requirement 10.2: CSRF protection
     */
    public function testCsrfMiddlewareExists(): void
    {
        $this->assertTrue(class_exists(\CsrfMiddleware::class));
    }

    /**
     * Test auth middleware exists
     */
    public function testAuthMiddlewareExists(): void
    {
        $this->assertTrue(class_exists(\AuthMiddleware::class));
    }

    /**
     * Test CSRF hidden input generation
     */
    public function testCsrfHiddenInputGeneration(): void
    {
        $hiddenInput = $this->csrfProtection->getHiddenInput();
        
        $this->assertStringContainsString('<input', $hiddenInput);
        $this->assertStringContainsString('type="hidden"', $hiddenInput);
        $this->assertStringContainsString('name="csrf_token"', $hiddenInput);
    }

    /**
     * Test CSRF meta tag generation
     */
    public function testCsrfMetaTagGeneration(): void
    {
        $metaTag = $this->csrfProtection->getMetaTag();
        
        $this->assertStringContainsString('<meta', $metaTag);
        $this->assertStringContainsString('name="csrf-token"', $metaTag);
    }

    /**
     * Test filename sanitization
     */
    public function testFilenameSanitization(): void
    {
        $dangerous = '../../../etc/passwd';
        $sanitized = \InputSanitizer::filename($dangerous);
        
        $this->assertStringNotContainsString('..', $sanitized);
        $this->assertStringNotContainsString('/', $sanitized);
    }

    /**
     * Test slug sanitization
     */
    public function testSlugSanitization(): void
    {
        $input = 'Hello World! This is a Test';
        $slug = \InputSanitizer::slug($input);
        
        $this->assertEquals('hello-world-this-is-a-test', $slug);
    }
}
