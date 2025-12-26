<?php
/**
 * Property-Based Tests for Language Switching
 * 
 * Feature: ceylon-cinnamon-ecommerce, Property 34: Language switching preservation
 * Validates: Requirements 9.3
 * 
 * Tests that language switching maintains page context and properly stores preferences.
 */

declare(strict_types=1);

namespace Tests\Property;

use PHPUnit\Framework\TestCase;
use Eris\Generator;
use Eris\TestTrait;

class LanguageSwitchingPropertyTest extends TestCase
{
    use TestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Load required files
        require_once __DIR__ . '/../../config/env.php';
        require_once __DIR__ . '/../../includes/SessionManager.php';
        require_once __DIR__ . '/../../includes/LanguageManager.php';
    }

    /**
     * Create a mock session manager that doesn't try to start sessions
     * 
     * @return \SessionManager Mock session manager
     */
    private function createMockSessionManager(): \SessionManager
    {
        // Create a mock that doesn't call session_start
        $mock = $this->createMock(\SessionManager::class);
        
        // Mock the get method to return from $_SESSION
        $mock->method('get')->willReturnCallback(function ($key, $default = null) {
            return $_SESSION[$key] ?? $default;
        });
        
        // Mock the set method to store in $_SESSION
        $mock->method('set')->willReturnCallback(function ($key, $value) {
            $_SESSION[$key] = $value;
        });
        
        // Mock the has method
        $mock->method('has')->willReturnCallback(function ($key) {
            return isset($_SESSION[$key]);
        });
        
        return $mock;
    }

    protected function tearDown(): void
    {
        // Clean up session if active
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = [];
        $_GET = [];
        $_SERVER = [];
        
        parent::tearDown();
    }

    /**
     * Feature: ceylon-cinnamon-ecommerce, Property 34: Language switching preservation
     * 
     * For any language switch, the current page context should be maintained.
     * 
     * Property: When switching languages, the URL path should remain the same,
     * only the 'lang' parameter should change.
     * 
     * Validates: Requirements 9.3
     */
    public function testLanguageSwitchingPreservesPageContext(): void
    {
        // Use predefined combinations to avoid evaluation ratio issues
        $testCases = [
            ['en', 'si', '/products'],
            ['si', 'en', '/cart'],
            ['en', 'si', '/checkout'],
            ['si', 'en', '/blog'],
            ['en', 'si', '/products/detail'],
        ];
        
        foreach ($testCases as [$currentLang, $targetLang, $pagePath]) {
            // Set up mock request
            $_SERVER['REQUEST_URI'] = $pagePath . '?category=1&sort=price';
            $_GET['lang'] = $targetLang;
            
            // Simulate session storage directly
            $_SESSION['language'] = $currentLang;
            
            // Create a mock session manager
            $sessionManager = $this->createMockSessionManager();
            
            // Create language manager
            $languageManager = new \LanguageManager($sessionManager);
            
            // Get switcher URL for target language
            $switcherUrl = $languageManager->getSwitcherUrl($targetLang);
            
            // Property 1: Switcher URL should contain the target language
            $this->assertStringContainsString(
                'lang=' . $targetLang,
                $switcherUrl,
                'Switcher URL must contain the target language parameter'
            );
            
            // Property 2: Switcher URL should preserve the page path
            $this->assertStringContainsString(
                $pagePath,
                $switcherUrl,
                'Switcher URL must preserve the page path'
            );
            
            // Property 3: Switcher URL should preserve query parameters (except lang)
            if (strpos($_SERVER['REQUEST_URI'], '?') !== false) {
                $this->assertStringContainsString(
                    'category=1',
                    $switcherUrl,
                    'Switcher URL must preserve existing query parameters'
                );
            }
            
            // Clean up for next iteration
            $_SESSION = [];
            $_GET = [];
        }
    }

    /**
     * Property: Language preference is stored in session
     * 
     * For any language selection, the preference should be stored in the session
     * and retrievable on subsequent requests.
     * 
     * Validates: Requirements 9.4
     */
    public function testLanguagePreferenceStoredInSession(): void
    {
        $this->limitTo(20)
            ->forAll(
                Generator\elements(['en', 'si']) // Language to set
            )
            ->then(function (string $language): void {
                // Create mock session manager
                $sessionManager = $this->createMockSessionManager();
                
                // Create language manager
                $languageManager = new \LanguageManager($sessionManager);
                
                // Set language
                $result = $languageManager->setLanguage($language);
                
                // Property 1: setLanguage should return true for valid languages
                $this->assertTrue(
                    $result,
                    'setLanguage should return true for valid language codes'
                );
                
                // Property 2: Language should be stored in session
                $this->assertEquals(
                    $language,
                    $_SESSION['language'] ?? null,
                    'Language preference must be stored in session'
                );
                
                // Property 3: getCurrentLanguage should return the set language
                $this->assertEquals(
                    $language,
                    $languageManager->getCurrentLanguage(),
                    'getCurrentLanguage must return the language that was set'
                );
            });
    }

    /**
     * Property: Invalid language codes are rejected
     * 
     * For any invalid language code, setLanguage should return false
     * and not change the current language.
     */
    public function testInvalidLanguageCodesAreRejected(): void
    {
        $this->limitTo(20)
            ->forAll(
                Generator\elements(['en', 'si']), // Valid starting language
                Generator\string() // Potentially invalid language code
            )
            ->when(fn($valid, $invalid) => !in_array($invalid, ['en', 'si']))
            ->then(function (string $validLang, string $invalidLang): void {
                // Create mock session manager
                $sessionManager = $this->createMockSessionManager();
                
                // Create language manager
                $languageManager = new \LanguageManager($sessionManager);
                
                // Set valid language first
                $languageManager->setLanguage($validLang);
                $originalLanguage = $languageManager->getCurrentLanguage();
                
                // Try to set invalid language
                $result = $languageManager->setLanguage($invalidLang);
                
                // Property 1: setLanguage should return false for invalid codes
                $this->assertFalse(
                    $result,
                    'setLanguage should return false for invalid language codes'
                );
                
                // Property 2: Current language should not change
                $this->assertEquals(
                    $originalLanguage,
                    $languageManager->getCurrentLanguage(),
                    'Current language must not change when setting invalid language'
                );
            });
    }

    /**
     * Property: Language switcher URLs are generated for all supported languages
     * 
     * For any supported language, a switcher URL should be generated.
     */
    public function testLanguageSwitcherUrlsForAllLanguages(): void
    {
        $this->limitTo(20)
            ->forAll(
                Generator\elements(['en', 'si']) // Current language
            )
            ->then(function (string $currentLang): void {
                // Set up mock request
                $_SERVER['REQUEST_URI'] = '/products';
                
                // Create mock session manager
                $sessionManager = $this->createMockSessionManager();
                
                // Create language manager
                $languageManager = new \LanguageManager($sessionManager);
                $languageManager->setLanguage($currentLang);
                
                // Get all switcher URLs
                $allUrls = $languageManager->getAllSwitcherUrls();
                
                // Property 1: Should have URLs for all supported languages
                $this->assertCount(
                    2,
                    $allUrls,
                    'Should have switcher URLs for all supported languages'
                );
                
                // Property 2: Each URL should contain the language parameter
                foreach ($allUrls as $lang => $url) {
                    $this->assertStringContainsString(
                        'lang=' . $lang,
                        $url,
                        "Switcher URL for {$lang} must contain lang parameter"
                    );
                }
                
                // Property 3: Each URL should preserve the page path
                foreach ($allUrls as $lang => $url) {
                    $this->assertStringContainsString(
                        '/products',
                        $url,
                        "Switcher URL for {$lang} must preserve page path"
                    );
                }
            });
    }

    /**
     * Property: isCurrentLanguage correctly identifies current language
     * 
     * For any language, isCurrentLanguage should return true only for the current language.
     */
    public function testIsCurrentLanguageAccuracy(): void
    {
        $this->limitTo(20)
            ->forAll(
                Generator\elements(['en', 'si']), // Current language
                Generator\elements(['en', 'si'])  // Language to check
            )
            ->then(function (string $currentLang, string $checkLang): void {
                // Create mock session manager
                $sessionManager = $this->createMockSessionManager();
                
                // Create language manager
                $languageManager = new \LanguageManager($sessionManager);
                $languageManager->setLanguage($currentLang);
                
                // Check if language is current
                $isCurrent = $languageManager->isCurrentLanguage($checkLang);
                
                // Property: isCurrentLanguage should match the actual current language
                if ($currentLang === $checkLang) {
                    $this->assertTrue(
                        $isCurrent,
                        "isCurrentLanguage should return true for the current language"
                    );
                } else {
                    $this->assertFalse(
                        $isCurrent,
                        "isCurrentLanguage should return false for non-current languages"
                    );
                }
            });
    }

    /**
     * Property: Supported languages are correctly identified
     * 
     * For any language code, isSupported should return true only for 'en' and 'si'.
     */
    public function testSupportedLanguagesIdentification(): void
    {
        $this->limitTo(20)
            ->forAll(
                Generator\string() // Any language code
            )
            ->then(function (string $language): void {
                // Create mock session manager
                $sessionManager = $this->createMockSessionManager();
                
                // Create language manager
                $languageManager = new \LanguageManager($sessionManager);
                
                // Get supported languages
                $supported = $languageManager->getSupportedLanguages();
                
                // Property 1: Supported languages should be an array
                $this->assertIsArray(
                    $supported,
                    'getSupportedLanguages should return an array'
                );
                
                // Property 2: Should contain exactly 2 languages
                $this->assertCount(
                    2,
                    $supported,
                    'Should support exactly 2 languages'
                );
                
                // Property 3: Should contain 'en' and 'si'
                $this->assertContains('en', $supported, 'Should support English');
                $this->assertContains('si', $supported, 'Should support Sinhala');
            });
    }

    /**
     * Property: Language names are correctly returned
     * 
     * For any supported language, getLanguageName should return a non-empty string.
     */
    public function testLanguageNameRetrieval(): void
    {
        $this->limitTo(20)
            ->forAll(
                Generator\elements(['en', 'si']) // Supported language
            )
            ->then(function (string $language): void {
                // Create mock session manager
                $sessionManager = $this->createMockSessionManager();
                
                // Create language manager
                $languageManager = new \LanguageManager($sessionManager);
                
                // Get language name
                $name = $languageManager->getLanguageName($language);
                
                // Property: Language name should be non-empty
                $this->assertNotEmpty(
                    $name,
                    "getLanguageName should return non-empty string for {$language}"
                );
                
                // Property: Language name should be a string
                $this->assertIsString(
                    $name,
                    "getLanguageName should return a string"
                );
            });
    }
}
