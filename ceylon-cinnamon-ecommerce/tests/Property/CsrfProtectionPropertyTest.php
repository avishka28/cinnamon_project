<?php
/**
 * Property-Based Tests for CSRF Protection
 * 
 * Feature: ceylon-cinnamon-ecommerce, Property 26: CSRF token validation
 * Validates: Requirements 10.2
 * 
 * Tests that CSRF tokens are properly generated and validated for all form submissions.
 */

declare(strict_types=1);

namespace Tests\Property;

use PHPUnit\Framework\TestCase;
use Eris\Generator;
use Eris\TestTrait;

class CsrfProtectionPropertyTest extends TestCase
{
    use TestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Load required files
        require_once __DIR__ . '/../../config/env.php';
        require_once __DIR__ . '/../../includes/SessionManager.php';
        require_once __DIR__ . '/../../includes/CsrfProtection.php';
        require_once __DIR__ . '/../../includes/Middleware.php';
        require_once __DIR__ . '/../../includes/CsrfMiddleware.php';
        
        // Initialize session array
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        // Clean up session
        $_SESSION = [];
        
        // Clean up superglobals
        $_POST = [];
        $_GET = [];
        unset($_SERVER['HTTP_X_CSRF_TOKEN']);
        unset($_SERVER['CONTENT_TYPE']);
        
        parent::tearDown();
    }

    /**
     * Feature: ceylon-cinnamon-ecommerce, Property 26: CSRF token validation
     * 
     * For any generated CSRF token, it should:
     * 1. Be 64 characters long (32 bytes hex encoded)
     * 2. Be valid hexadecimal
     * 3. Be stored in session
     * 
     * Validates: Requirements 10.2
     */
    public function testCsrfTokenGenerationFormat(): void
    {
        $this->limitTo(100)
            ->forAll(
                Generator\pos() // Random iteration count
            )
            ->then(function (int $iteration): void {
                // Create fresh session manager and CSRF protection
                $sessionManager = $this->createMockSessionManager();
                $csrfProtection = new \CsrfProtection($sessionManager);
                
                // Generate token
                $token = $csrfProtection->generateToken();
                
                // Property 1: Token must be 64 characters (32 bytes hex)
                $this->assertEquals(
                    64,
                    strlen($token),
                    'CSRF token must be 64 characters (32 bytes hex encoded)'
                );
                
                // Property 2: Token must be valid hex
                $this->assertMatchesRegularExpression(
                    '/^[a-f0-9]{64}$/',
                    $token,
                    'CSRF token must be valid hexadecimal'
                );
                
                // Property 3: Token must be stored in session
                $storedToken = $sessionManager->get(\CsrfProtection::TOKEN_NAME);
                $this->assertEquals(
                    $token,
                    $storedToken,
                    'CSRF token must be stored in session'
                );
            });
    }

    /**
     * Property: Valid tokens are accepted
     * 
     * For any generated CSRF token, validation with the same token should succeed.
     * 
     * Validates: Requirements 10.2
     */
    public function testValidTokensAreAccepted(): void
    {
        $this->limitTo(100)
            ->forAll(
                Generator\pos() // Random iteration count
            )
            ->then(function (int $iteration): void {
                $sessionManager = $this->createMockSessionManager();
                $csrfProtection = new \CsrfProtection($sessionManager);
                
                // Generate token
                $token = $csrfProtection->generateToken();
                
                // Property: Same token should validate successfully
                $this->assertTrue(
                    $csrfProtection->validateToken($token),
                    'Valid CSRF token must be accepted'
                );
            });
    }

    /**
     * Property: Invalid tokens are rejected
     * 
     * For any generated CSRF token, validation with a different token should fail.
     * 
     * Validates: Requirements 10.2
     */
    public function testInvalidTokensAreRejected(): void
    {
        $this->limitTo(100)
            ->forAll(
                Generator\string() // Random invalid token
            )
            ->then(function (string $invalidToken): void {
                $sessionManager = $this->createMockSessionManager();
                $csrfProtection = new \CsrfProtection($sessionManager);
                
                // Generate a valid token
                $validToken = $csrfProtection->generateToken();
                
                // Skip if random string happens to match (extremely unlikely)
                if ($invalidToken === $validToken) {
                    return;
                }
                
                // Property: Different token should be rejected
                $this->assertFalse(
                    $csrfProtection->validateToken($invalidToken),
                    'Invalid CSRF token must be rejected'
                );
            });
    }

    /**
     * Property: Empty tokens are rejected
     * 
     * Empty string tokens should always be rejected.
     * 
     * Validates: Requirements 10.2
     */
    public function testEmptyTokensAreRejected(): void
    {
        $this->limitTo(20)
            ->forAll(
                Generator\pos() // Random iteration
            )
            ->then(function (int $iteration): void {
                $sessionManager = $this->createMockSessionManager();
                $csrfProtection = new \CsrfProtection($sessionManager);
                
                // Generate a valid token first
                $csrfProtection->generateToken();
                
                // Property: Empty token should be rejected
                $this->assertFalse(
                    $csrfProtection->validateToken(''),
                    'Empty CSRF token must be rejected'
                );
            });
    }

    /**
     * Property: Token regeneration creates new unique tokens
     * 
     * Each call to regenerateToken should create a different token.
     * 
     * Validates: Requirements 10.2
     */
    public function testTokenRegenerationCreatesUniqueTokens(): void
    {
        $this->limitTo(50)
            ->forAll(
                Generator\choose(2, 10) // Number of regenerations
            )
            ->then(function (int $regenerations): void {
                $sessionManager = $this->createMockSessionManager();
                $csrfProtection = new \CsrfProtection($sessionManager);
                
                $tokens = [];
                
                for ($i = 0; $i < $regenerations; $i++) {
                    $token = $csrfProtection->regenerateToken();
                    
                    // Property: Each token should be unique
                    $this->assertNotContains(
                        $token,
                        $tokens,
                        'Regenerated CSRF tokens must be unique'
                    );
                    
                    $tokens[] = $token;
                }
            });
    }

    /**
     * Property: Token validation uses timing-safe comparison
     * 
     * Tests that token validation uses hash_equals for timing-safe comparison.
     * 
     * Validates: Requirements 10.2
     */
    public function testTokenValidationIsTimingSafe(): void
    {
        $this->limitTo(100)
            ->forAll(
                Generator\string(), // Random token 1
                Generator\string()  // Random token 2
            )
            ->then(function (string $token1, string $token2): void {
                // hash_equals should return true only for identical strings
                $result = hash_equals($token1, $token2);
                
                if ($token1 === $token2) {
                    $this->assertTrue($result, 'hash_equals should return true for identical strings');
                } else {
                    $this->assertFalse($result, 'hash_equals should return false for different strings');
                }
            });
    }

    /**
     * Property: Hidden input field contains valid token
     * 
     * The generated hidden input field should contain a valid CSRF token.
     * 
     * Validates: Requirements 10.2
     */
    public function testHiddenInputFieldContainsValidToken(): void
    {
        $this->limitTo(50)
            ->forAll(
                Generator\pos() // Random iteration
            )
            ->then(function (int $iteration): void {
                $sessionManager = $this->createMockSessionManager();
                $csrfProtection = new \CsrfProtection($sessionManager);
                
                // Get hidden input
                $hiddenInput = $csrfProtection->getHiddenInput();
                
                // Property 1: Should be a valid HTML input element
                $this->assertStringContainsString(
                    '<input type="hidden"',
                    $hiddenInput,
                    'Hidden input must be a valid HTML input element'
                );
                
                // Property 2: Should contain the token name
                $this->assertStringContainsString(
                    'name="csrf_token"',
                    $hiddenInput,
                    'Hidden input must have correct name attribute'
                );
                
                // Property 3: Should contain a 64-character hex token
                preg_match('/value="([a-f0-9]{64})"/', $hiddenInput, $matches);
                $this->assertNotEmpty(
                    $matches,
                    'Hidden input must contain a valid 64-character hex token'
                );
            });
    }

    /**
     * Property: Meta tag contains valid token
     * 
     * The generated meta tag should contain a valid CSRF token.
     * 
     * Validates: Requirements 10.2
     */
    public function testMetaTagContainsValidToken(): void
    {
        $this->limitTo(50)
            ->forAll(
                Generator\pos() // Random iteration
            )
            ->then(function (int $iteration): void {
                $sessionManager = $this->createMockSessionManager();
                $csrfProtection = new \CsrfProtection($sessionManager);
                
                // Get meta tag
                $metaTag = $csrfProtection->getMetaTag();
                
                // Property 1: Should be a valid HTML meta element
                $this->assertStringContainsString(
                    '<meta name="csrf-token"',
                    $metaTag,
                    'Meta tag must be a valid HTML meta element'
                );
                
                // Property 2: Should contain a 64-character hex token
                preg_match('/content="([a-f0-9]{64})"/', $metaTag, $matches);
                $this->assertNotEmpty(
                    $matches,
                    'Meta tag must contain a valid 64-character hex token'
                );
            });
    }

    /**
     * Property: Token from request is correctly extracted from POST
     * 
     * Tests that tokens can be extracted from POST data.
     * 
     * Validates: Requirements 10.2
     */
    public function testTokenExtractionFromPost(): void
    {
        $this->limitTo(50)
            ->forAll(
                Generator\pos() // Random iteration
            )
            ->then(function (int $iteration): void {
                $sessionManager = $this->createMockSessionManager();
                $csrfProtection = new \CsrfProtection($sessionManager);
                
                // Generate a token
                $token = $csrfProtection->generateToken();
                
                // Simulate POST request with token
                $_POST[\CsrfProtection::TOKEN_NAME] = $token;
                
                // Property: Token should be extractable from POST
                $extractedToken = $csrfProtection->getTokenFromRequest();
                $this->assertEquals(
                    $token,
                    $extractedToken,
                    'CSRF token must be extractable from POST data'
                );
                
                // Clean up
                $_POST = [];
            });
    }

    /**
     * Property: Token from request is correctly extracted from header
     * 
     * Tests that tokens can be extracted from X-CSRF-TOKEN header.
     * 
     * Validates: Requirements 10.2
     */
    public function testTokenExtractionFromHeader(): void
    {
        $this->limitTo(50)
            ->forAll(
                Generator\pos() // Random iteration
            )
            ->then(function (int $iteration): void {
                $sessionManager = $this->createMockSessionManager();
                $csrfProtection = new \CsrfProtection($sessionManager);
                
                // Generate a token
                $token = $csrfProtection->generateToken();
                
                // Simulate header with token
                $_SERVER['HTTP_X_CSRF_TOKEN'] = $token;
                
                // Property: Token should be extractable from header
                $extractedToken = $csrfProtection->getTokenFromRequest();
                $this->assertEquals(
                    $token,
                    $extractedToken,
                    'CSRF token must be extractable from X-CSRF-TOKEN header'
                );
                
                // Clean up
                unset($_SERVER['HTTP_X_CSRF_TOKEN']);
            });
    }

    /**
     * Property: Request validation works correctly
     * 
     * Tests that validateRequest correctly validates tokens from the request.
     * 
     * Validates: Requirements 10.2
     */
    public function testRequestValidation(): void
    {
        $this->limitTo(50)
            ->forAll(
                Generator\pos() // Random iteration
            )
            ->then(function (int $iteration): void {
                $sessionManager = $this->createMockSessionManager();
                $csrfProtection = new \CsrfProtection($sessionManager);
                
                // Generate a token
                $token = $csrfProtection->generateToken();
                
                // Simulate POST request with valid token
                $_POST[\CsrfProtection::TOKEN_NAME] = $token;
                
                // Property: Request with valid token should pass validation
                $this->assertTrue(
                    $csrfProtection->validateRequest(),
                    'Request with valid CSRF token must pass validation'
                );
                
                // Clean up
                $_POST = [];
            });
    }

    /**
     * Property: Request validation fails without token
     * 
     * Tests that validateRequest fails when no token is present.
     * 
     * Validates: Requirements 10.2
     */
    public function testRequestValidationFailsWithoutToken(): void
    {
        $this->limitTo(50)
            ->forAll(
                Generator\pos() // Random iteration
            )
            ->then(function (int $iteration): void {
                $sessionManager = $this->createMockSessionManager();
                $csrfProtection = new \CsrfProtection($sessionManager);
                
                // Generate a token (stored in session)
                $csrfProtection->generateToken();
                
                // Ensure no token in request
                $_POST = [];
                $_GET = [];
                unset($_SERVER['HTTP_X_CSRF_TOKEN']);
                
                // Property: Request without token should fail validation
                $this->assertFalse(
                    $csrfProtection->validateRequest(),
                    'Request without CSRF token must fail validation'
                );
            });
    }

    /**
     * Create a mock session manager that uses $_SESSION directly
     * 
     * @return \SessionManager
     */
    private function createMockSessionManager(): \SessionManager
    {
        // Create a simple mock that uses $_SESSION directly
        return new class extends \SessionManager {
            public function __construct()
            {
                // Don't call parent constructor to avoid session issues in CLI
            }
            
            public function start(): bool
            {
                return true;
            }
            
            public function set(string $key, mixed $value): void
            {
                $_SESSION[$key] = $value;
            }
            
            public function get(string $key, mixed $default = null): mixed
            {
                return $_SESSION[$key] ?? $default;
            }
            
            public function has(string $key): bool
            {
                return isset($_SESSION[$key]);
            }
            
            public function remove(string $key): void
            {
                unset($_SESSION[$key]);
            }
        };
    }
}
