<?php
/**
 * Property-Based Tests for Input Sanitization
 * 
 * Feature: ceylon-cinnamon-ecommerce, Property 27: Input sanitization
 * Validates: Requirements 10.3
 * 
 * Tests that all user input is properly sanitized and validated server-side.
 */

declare(strict_types=1);

namespace Tests\Property;

use PHPUnit\Framework\TestCase;
use Eris\Generator;
use Eris\TestTrait;

class InputSanitizationPropertyTest extends TestCase
{
    use TestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Load required files
        require_once __DIR__ . '/../../config/env.php';
        require_once __DIR__ . '/../../includes/InputSanitizer.php';
        require_once __DIR__ . '/../../includes/InputValidator.php';
    }

    /**
     * Feature: ceylon-cinnamon-ecommerce, Property 27: Input sanitization
     * 
     * For any user input containing HTML special characters, the sanitized output
     * should have those characters converted to HTML entities.
     * 
     * Validates: Requirements 10.3
     */
    public function testHtmlSpecialCharactersAreEscaped(): void
    {
        $this->limitTo(100)
            ->forAll(
                Generator\string() // Random string input
            )
            ->then(function (string $input): void {
                $sanitized = \InputSanitizer::sanitizeString($input);
                
                // Property: Sanitized output should not contain raw HTML special chars
                // (unless they were already escaped in input)
                $this->assertStringNotContainsString('<', $sanitized);
                $this->assertStringNotContainsString('>', $sanitized);
                
                // If input had these chars, they should be escaped
                if (strpos($input, '<') !== false) {
                    $this->assertStringContainsString('&lt;', $sanitized);
                }
                if (strpos($input, '>') !== false) {
                    $this->assertStringContainsString('&gt;', $sanitized);
                }
            });
    }

    /**
     * Property: XSS attack patterns are neutralized
     * 
     * For any input containing common XSS patterns, the sanitized output
     * should not contain executable script content (raw HTML tags are escaped).
     * 
     * Validates: Requirements 10.3
     */
    public function testXssAttackPatternsAreNeutralized(): void
    {
        // Common XSS attack patterns that contain HTML/script elements
        $xssPatterns = [
            '<script>alert("xss")</script>',
            '<img src="x" onerror="alert(1)">',
            '<a href="javascript:alert(1)">click</a>',
            '<div onmouseover="alert(1)">hover</div>',
            '<iframe src="evil.com"></iframe>',
            '<body onload="alert(1)">',
            '<svg onload="alert(1)">',
            '"><script>alert(1)</script>',
            '<img src=x onerror=alert(1)>',
        ];
        
        $this->limitTo(count($xssPatterns))
            ->forAll(
                Generator\elements(...$xssPatterns)
            )
            ->then(function (string $xssInput): void {
                $sanitized = \InputSanitizer::sanitizeString($xssInput);
                
                // Property 1: No raw (unescaped) script tags - they should be escaped to &lt;script
                $this->assertStringNotContainsString('<script', $sanitized);
                
                // Property 2: No raw (unescaped) angle brackets - XSS requires executable HTML
                $this->assertStringNotContainsString('<', $sanitized);
                $this->assertStringNotContainsString('>', $sanitized);
                
                // Property 3: XSS detection should flag the original input
                $this->assertTrue(
                    \InputSanitizer::containsXss($xssInput),
                    "XSS detection should identify attack pattern: {$xssInput}"
                );
            });
    }

    /**
     * Property: Sanitization preserves safe content
     * 
     * For any alphanumeric input, the sanitized output should be identical
     * to the input (after trimming).
     * 
     * Validates: Requirements 10.3
     */
    public function testSanitizationPreservesSafeContent(): void
    {
        // Use predefined safe strings instead of regex generator
        $safeStrings = [
            'Hello World',
            'Test123',
            'Simple text',
            'Numbers 12345',
            'Mixed Content 2024',
            'UPPERCASE lowercase',
            'a',
            'A simple sentence with spaces',
            'Product Name 100g',
            'Ceylon Cinnamon',
        ];
        
        $this->limitTo(count($safeStrings))
            ->forAll(
                Generator\elements(...$safeStrings)
            )
            ->then(function (string $safeInput): void {
                $sanitized = \InputSanitizer::sanitizeString($safeInput);
                
                // Property: Safe alphanumeric content should be preserved
                $this->assertEquals(
                    trim($safeInput),
                    $sanitized,
                    'Safe alphanumeric content should be preserved after sanitization'
                );
            });
    }

    /**
     * Property: Email sanitization validates format
     * 
     * For any valid email format, sanitization should preserve it.
     * For invalid formats, it should return false.
     * 
     * Validates: Requirements 10.3
     */
    public function testEmailSanitizationValidatesFormat(): void
    {
        // Valid email patterns
        $validEmails = [
            'test@example.com',
            'user.name@domain.org',
            'user+tag@example.co.uk',
            'a@b.co',
        ];
        
        $this->limitTo(count($validEmails))
            ->forAll(
                Generator\elements(...$validEmails)
            )
            ->then(function (string $email): void {
                $sanitized = \InputSanitizer::email($email);
                
                // Property: Valid emails should be preserved
                $this->assertNotFalse($sanitized, 'Valid email should not return false');
                $this->assertEquals($email, $sanitized, 'Valid email should be preserved');
            });
    }

    /**
     * Property: Invalid emails are rejected
     * 
     * For any invalid email format, sanitization should return false.
     * 
     * Validates: Requirements 10.3
     */
    public function testInvalidEmailsAreRejected(): void
    {
        // Invalid email patterns (that remain invalid after sanitization)
        $invalidEmails = [
            'not-an-email',
            '@nodomain.com',
            'noat.com',
            '',
            'missing@',
            'double@@at.com',
            'no.domain@',
        ];
        
        $this->limitTo(count($invalidEmails))
            ->forAll(
                Generator\elements(...$invalidEmails)
            )
            ->then(function (string $email): void {
                $sanitized = \InputSanitizer::email($email);
                
                // Property: Invalid emails should return false
                $this->assertFalse($sanitized, "Invalid email '{$email}' should return false");
            });
    }

    /**
     * Property: Integer sanitization returns integers
     * 
     * For any input, integer sanitization should return an integer type.
     * 
     * Validates: Requirements 10.3
     */
    public function testIntegerSanitizationReturnsIntegers(): void
    {
        $this->limitTo(100)
            ->forAll(
                Generator\oneOf(
                    Generator\int(),
                    Generator\string(),
                    Generator\float()
                )
            )
            ->then(function (mixed $input): void {
                $sanitized = \InputSanitizer::int($input);
                
                // Property: Result should always be an integer
                $this->assertIsInt($sanitized, 'Integer sanitization should return int type');
            });
    }

    /**
     * Property: Float sanitization returns floats
     * 
     * For any input, float sanitization should return a float type.
     * 
     * Validates: Requirements 10.3
     */
    public function testFloatSanitizationReturnsFloats(): void
    {
        $this->limitTo(100)
            ->forAll(
                Generator\oneOf(
                    Generator\int(),
                    Generator\string(),
                    Generator\float()
                )
            )
            ->then(function (mixed $input): void {
                $sanitized = \InputSanitizer::float($input);
                
                // Property: Result should always be a float
                $this->assertIsFloat($sanitized, 'Float sanitization should return float type');
            });
    }

    /**
     * Property: Filename sanitization removes dangerous characters
     * 
     * For any filename input, sanitization should remove path traversal
     * and dangerous characters.
     * 
     * Validates: Requirements 10.3, 10.4
     */
    public function testFilenameSanitizationRemovesDangerousCharacters(): void
    {
        // Dangerous filename patterns
        $dangerousFilenames = [
            '../../../etc/passwd',
            '..\\..\\windows\\system32\\config',
            'file.php\0.jpg',
            'file<script>.txt',
            'file;rm -rf /.txt',
            '/etc/passwd',
            'C:\\Windows\\System32\\config',
        ];
        
        $this->limitTo(count($dangerousFilenames))
            ->forAll(
                Generator\elements(...$dangerousFilenames)
            )
            ->then(function (string $filename): void {
                $sanitized = \InputSanitizer::filename($filename);
                
                // Property 1: No path traversal
                $this->assertStringNotContainsString('..', $sanitized);
                $this->assertStringNotContainsString('/', $sanitized);
                $this->assertStringNotContainsString('\\', $sanitized);
                
                // Property 2: No null bytes
                $this->assertStringNotContainsString("\0", $sanitized);
                
                // Property 3: Only safe characters
                $this->assertMatchesRegularExpression(
                    '/^[a-zA-Z0-9._-]+$/',
                    $sanitized,
                    'Filename should only contain safe characters'
                );
            });
    }

    /**
     * Property: Slug generation creates URL-safe strings
     * 
     * For any input, slug generation should create a URL-safe string.
     * 
     * Validates: Requirements 10.3
     */
    public function testSlugGenerationCreatesUrlSafeStrings(): void
    {
        $this->limitTo(100)
            ->forAll(
                Generator\string()
            )
            ->when(fn($s) => strlen(trim($s)) > 0)
            ->then(function (string $input): void {
                $slug = \InputSanitizer::slug($input);
                
                // Property 1: Slug should be lowercase
                $this->assertEquals(
                    strtolower($slug),
                    $slug,
                    'Slug should be lowercase'
                );
                
                // Property 2: Slug should only contain safe URL characters
                if (!empty($slug)) {
                    $this->assertMatchesRegularExpression(
                        '/^[a-z0-9-]+$/',
                        $slug,
                        'Slug should only contain lowercase letters, numbers, and hyphens'
                    );
                }
                
                // Property 3: No consecutive hyphens
                $this->assertStringNotContainsString('--', $slug);
            });
    }

    /**
     * Property: Strip tags removes all HTML
     * 
     * For any input containing HTML tags, strip_tags should remove them all.
     * 
     * Validates: Requirements 10.3
     */
    public function testStripTagsRemovesAllHtml(): void
    {
        $htmlInputs = [
            '<p>Hello</p>',
            '<div class="test">Content</div>',
            '<script>alert(1)</script>',
            '<a href="test">Link</a>',
            'Plain text',
            '<b>Bold</b> and <i>italic</i>',
        ];
        
        $this->limitTo(count($htmlInputs))
            ->forAll(
                Generator\elements(...$htmlInputs)
            )
            ->then(function (string $input): void {
                $stripped = \InputSanitizer::stripTags($input);
                
                // Property: No HTML tags should remain
                $this->assertStringNotContainsString('<', $stripped);
                $this->assertStringNotContainsString('>', $stripped);
            });
    }

    /**
     * Property: Phone sanitization keeps only valid characters
     * 
     * For any phone input, sanitization should keep only digits and
     * common phone formatting characters.
     * 
     * Validates: Requirements 10.3
     */
    public function testPhoneSanitizationKeepsValidCharacters(): void
    {
        $this->limitTo(100)
            ->forAll(
                Generator\string()
            )
            ->then(function (string $input): void {
                $sanitized = \InputSanitizer::phone($input);
                
                // Property: Only valid phone characters should remain
                $this->assertMatchesRegularExpression(
                    '/^[\d\s\-\(\)\+]*$/',
                    $sanitized,
                    'Phone should only contain digits, spaces, dashes, parentheses, and plus'
                );
            });
    }

    /**
     * Property: Input validator correctly identifies required fields
     * 
     * For any empty required field, validation should fail.
     * 
     * Validates: Requirements 10.3
     */
    public function testValidatorIdentifiesRequiredFields(): void
    {
        $emptyValues = ['', '   ', null];
        
        $this->limitTo(count($emptyValues))
            ->forAll(
                Generator\elements(...$emptyValues)
            )
            ->then(function (mixed $emptyValue): void {
                $validator = new \InputValidator(['field' => $emptyValue]);
                $validator->required('field', 'Test Field');
                
                // Property: Empty required fields should fail validation
                $this->assertTrue(
                    $validator->fails(),
                    'Empty required field should fail validation'
                );
                
                $this->assertTrue(
                    $validator->hasError('field'),
                    'Required field should have error'
                );
            });
    }

    /**
     * Property: Input validator correctly validates email format
     * 
     * For any invalid email, validation should fail.
     * 
     * Validates: Requirements 10.3
     */
    public function testValidatorValidatesEmailFormat(): void
    {
        $invalidEmails = ['not-email', '@no-local', 'no-at.com', 'spaces in@email.com'];
        
        $this->limitTo(count($invalidEmails))
            ->forAll(
                Generator\elements(...$invalidEmails)
            )
            ->then(function (string $invalidEmail): void {
                $validator = new \InputValidator(['email' => $invalidEmail]);
                $validator->email('email');
                
                // Property: Invalid emails should fail validation
                $this->assertTrue(
                    $validator->fails(),
                    "Invalid email '{$invalidEmail}' should fail validation"
                );
            });
    }

    /**
     * Property: Input validator correctly validates length constraints
     * 
     * For any string exceeding max length, validation should fail.
     * 
     * Validates: Requirements 10.3
     */
    public function testValidatorValidatesLengthConstraints(): void
    {
        $this->limitTo(50)
            ->forAll(
                Generator\choose(11, 100) // Length greater than max of 10
            )
            ->then(function (int $length): void {
                $longString = str_repeat('a', $length);
                
                $validator = new \InputValidator(['field' => $longString]);
                $validator->maxLength('field', 10);
                
                // Property: Strings exceeding max length should fail
                $this->assertTrue(
                    $validator->fails(),
                    "String of length {$length} should fail maxLength(10) validation"
                );
            });
    }

    /**
     * Property: Input validator correctly validates numeric constraints
     * 
     * For any non-numeric input, numeric validation should fail.
     * 
     * Validates: Requirements 10.3
     */
    public function testValidatorValidatesNumericConstraints(): void
    {
        $nonNumeric = ['abc', 'one', '12.34.56', 'a1b2'];
        
        $this->limitTo(count($nonNumeric))
            ->forAll(
                Generator\elements(...$nonNumeric)
            )
            ->then(function (string $input): void {
                $validator = new \InputValidator(['field' => $input]);
                $validator->numeric('field');
                
                // Property: Non-numeric values should fail numeric validation
                $this->assertTrue(
                    $validator->fails(),
                    "Non-numeric value '{$input}' should fail numeric validation"
                );
            });
    }

    /**
     * Property: Recursive array sanitization sanitizes all values
     * 
     * For any nested array with HTML, all values should be sanitized.
     * 
     * Validates: Requirements 10.3
     */
    public function testRecursiveArraySanitization(): void
    {
        $this->limitTo(20)
            ->forAll(
                Generator\pos() // Random iteration
            )
            ->then(function (int $iteration): void {
                $input = [
                    'name' => '<script>alert(1)</script>',
                    'nested' => [
                        'value' => '<img onerror="alert(1)">',
                        'deep' => [
                            'item' => '<a href="javascript:void(0)">link</a>'
                        ]
                    ]
                ];
                
                $sanitized = \InputSanitizer::arrayRecursive($input);
                
                // Property: All nested values should be sanitized
                $this->assertStringNotContainsString('<script', $sanitized['name']);
                $this->assertStringNotContainsString('<img', $sanitized['nested']['value']);
                $this->assertStringNotContainsString('<a', $sanitized['nested']['deep']['item']);
            });
    }
}
