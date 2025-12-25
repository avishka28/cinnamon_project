<?php
/**
 * Property-Based Tests for File Upload Security
 * 
 * Feature: ceylon-cinnamon-ecommerce
 * Property 22: File upload security
 * 
 * For any file upload, only valid file types should be accepted and file names should be sanitized.
 * 
 * Validates: Requirements 6.2, 6.3
 */

declare(strict_types=1);

namespace Tests\Property;

use PHPUnit\Framework\TestCase;
use Eris\Generator;
use Eris\TestTrait;

class FileUploadSecurityPropertyTest extends TestCase
{
    use TestTrait;

    private \FileUploadHandler $uploadHandler;
    private string $testUploadDir;

    protected function setUp(): void
    {
        parent::setUp();
        
        require_once __DIR__ . '/../../includes/FileUploadHandler.php';
        
        // Create a temporary upload directory for testing
        $this->testUploadDir = sys_get_temp_dir() . '/ceylon_test_uploads_' . uniqid();
        mkdir($this->testUploadDir, 0755, true);
        
        $this->uploadHandler = new \FileUploadHandler($this->testUploadDir);
    }

    protected function tearDown(): void
    {
        // Clean up test upload directory
        $this->removeDirectory($this->testUploadDir);
        parent::tearDown();
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    /**
     * Feature: ceylon-cinnamon-ecommerce, Property 22: File upload security
     * 
     * For any filename, the sanitized version should only contain safe characters
     * (alphanumeric, underscore, hyphen).
     * 
     * Validates: Requirements 6.2
     */
    public function testFilenameSanitizationRemovesDangerousCharacters(): void
    {
        $this->limitTo(100)
            ->forAll(
                Generator\string()
            )
            ->then(function (string $filename): void {
                $sanitized = $this->uploadHandler->sanitizeFilename($filename);
                
                // Sanitized filename should only contain safe characters
                $this->assertMatchesRegularExpression(
                    '/^[a-z0-9_-]*$/',
                    $sanitized,
                    "Sanitized filename should only contain lowercase alphanumeric, underscore, and hyphen characters"
                );
                
                // Sanitized filename should not contain path traversal sequences
                $this->assertStringNotContainsString(
                    '..',
                    $sanitized,
                    "Sanitized filename should not contain path traversal sequences"
                );
                
                // Sanitized filename should not contain directory separators
                $this->assertStringNotContainsString(
                    '/',
                    $sanitized,
                    "Sanitized filename should not contain forward slashes"
                );
                
                $this->assertStringNotContainsString(
                    '\\',
                    $sanitized,
                    "Sanitized filename should not contain backslashes"
                );
            });
    }

    /**
     * Test that dangerous filenames are properly sanitized
     * 
     * For any filename containing dangerous patterns, the sanitized version
     * should be safe.
     */
    public function testDangerousFilenamesAreSanitized(): void
    {
        $dangerousFilenames = [
            '../../../etc/passwd',
            '..\\..\\..\\windows\\system32\\config\\sam',
            'file.php',
            'file.php.jpg',
            '<script>alert(1)</script>.jpg',
            'file\x00.jpg',
            'file%00.jpg',
            '....//....//etc/passwd',
            'file;rm -rf /',
            'file|cat /etc/passwd',
            'file`whoami`.jpg',
            'file$(whoami).jpg',
            "file\nwhoami.jpg",
            "file\rwhoami.jpg",
        ];

        foreach ($dangerousFilenames as $dangerous) {
            $sanitized = $this->uploadHandler->sanitizeFilename($dangerous);
            
            // Should only contain safe characters
            $this->assertMatchesRegularExpression(
                '/^[a-z0-9_-]*$/',
                $sanitized,
                "Dangerous filename '{$dangerous}' should be sanitized to safe characters only"
            );
            
            // Should not contain any dangerous sequences
            $this->assertStringNotContainsString('..', $sanitized);
            $this->assertStringNotContainsString('/', $sanitized);
            $this->assertStringNotContainsString('\\', $sanitized);
            $this->assertStringNotContainsString("\x00", $sanitized);
            $this->assertStringNotContainsString(';', $sanitized);
            $this->assertStringNotContainsString('|', $sanitized);
            $this->assertStringNotContainsString('`', $sanitized);
            $this->assertStringNotContainsString('$', $sanitized);
            $this->assertStringNotContainsString("\n", $sanitized);
            $this->assertStringNotContainsString("\r", $sanitized);
        }
    }

    /**
     * Test that only allowed image MIME types are accepted
     * 
     * For any file type not in the allowed list, validation should fail.
     * 
     * Validates: Requirements 6.2
     */
    public function testOnlyAllowedImageTypesAccepted(): void
    {
        $allowedTypes = $this->uploadHandler->getAllowedImageTypes();
        
        // Verify allowed types are what we expect
        $this->assertArrayHasKey('image/jpeg', $allowedTypes);
        $this->assertArrayHasKey('image/png', $allowedTypes);
        $this->assertArrayHasKey('image/gif', $allowedTypes);
        $this->assertArrayHasKey('image/webp', $allowedTypes);
        
        // Verify dangerous types are NOT allowed
        $this->assertArrayNotHasKey('application/x-php', $allowedTypes);
        $this->assertArrayNotHasKey('text/html', $allowedTypes);
        $this->assertArrayNotHasKey('application/javascript', $allowedTypes);
        $this->assertArrayNotHasKey('text/x-php', $allowedTypes);
        $this->assertArrayNotHasKey('application/x-httpd-php', $allowedTypes);
    }

    /**
     * Test that only allowed video MIME types are accepted
     * 
     * Validates: Requirements 6.3
     */
    public function testOnlyAllowedVideoTypesAccepted(): void
    {
        $allowedTypes = $this->uploadHandler->getAllowedVideoTypes();
        
        // Verify allowed types are what we expect
        $this->assertArrayHasKey('video/mp4', $allowedTypes);
        $this->assertArrayHasKey('video/webm', $allowedTypes);
        $this->assertArrayHasKey('video/ogg', $allowedTypes);
        
        // Verify dangerous types are NOT allowed
        $this->assertArrayNotHasKey('application/x-php', $allowedTypes);
        $this->assertArrayNotHasKey('text/html', $allowedTypes);
        $this->assertArrayNotHasKey('application/javascript', $allowedTypes);
    }

    /**
     * Test that sanitized filenames have limited length
     * 
     * For any filename, the sanitized version should not exceed the maximum length.
     */
    public function testSanitizedFilenameLengthIsLimited(): void
    {
        $this->limitTo(50)
            ->forAll(
                Generator\string()
            )
            ->then(function (string $filename): void {
                $sanitized = $this->uploadHandler->sanitizeFilename($filename);
                
                // Sanitized filename should not exceed 100 characters
                $this->assertLessThanOrEqual(
                    100,
                    strlen($sanitized),
                    "Sanitized filename length should not exceed 100 characters"
                );
            });
    }

    /**
     * Test that file validation rejects files with upload errors
     */
    public function testValidationRejectsFilesWithUploadErrors(): void
    {
        $errorCodes = [
            UPLOAD_ERR_INI_SIZE,
            UPLOAD_ERR_FORM_SIZE,
            UPLOAD_ERR_PARTIAL,
            UPLOAD_ERR_NO_FILE,
            UPLOAD_ERR_NO_TMP_DIR,
            UPLOAD_ERR_CANT_WRITE,
            UPLOAD_ERR_EXTENSION,
        ];

        foreach ($errorCodes as $errorCode) {
            $file = [
                'name' => 'test.jpg',
                'type' => 'image/jpeg',
                'tmp_name' => '/tmp/test.jpg',
                'error' => $errorCode,
                'size' => 1000
            ];

            $result = $this->uploadHandler->validateFile($file, 'image');
            
            $this->assertFalse(
                $result,
                "File with error code {$errorCode} should be rejected"
            );
            
            $this->assertNotNull(
                $this->uploadHandler->getLastError(),
                "Error message should be set for error code {$errorCode}"
            );
        }
    }

    /**
     * Test that sanitized filenames are lowercase
     * 
     * For any filename, the sanitized version should be lowercase.
     */
    public function testSanitizedFilenamesAreLowercase(): void
    {
        $this->limitTo(50)
            ->forAll(
                Generator\suchThat(
                    fn($s) => strlen($s) > 0 && preg_match('/[a-zA-Z]/', $s),
                    Generator\string()
                )
            )
            ->then(function (string $filename): void {
                $sanitized = $this->uploadHandler->sanitizeFilename($filename);
                
                if (!empty($sanitized)) {
                    $this->assertEquals(
                        strtolower($sanitized),
                        $sanitized,
                        "Sanitized filename should be lowercase"
                    );
                }
            });
    }

    /**
     * Test that spaces in filenames are converted to underscores
     */
    public function testSpacesConvertedToUnderscores(): void
    {
        $filenamesWithSpaces = [
            'my file name',
            'test file.jpg',
            'product image 001',
            'file   with   multiple   spaces',
        ];

        foreach ($filenamesWithSpaces as $filename) {
            $sanitized = $this->uploadHandler->sanitizeFilename($filename);
            
            $this->assertStringNotContainsString(
                ' ',
                $sanitized,
                "Sanitized filename should not contain spaces"
            );
        }
    }

    /**
     * Test that file extensions are not included in sanitized base name
     */
    public function testExtensionsRemovedFromSanitizedBaseName(): void
    {
        $filenamesWithExtensions = [
            'file.jpg',
            'file.php',
            'file.php.jpg',
            'file.tar.gz',
            'file.exe',
        ];

        foreach ($filenamesWithExtensions as $filename) {
            $sanitized = $this->uploadHandler->sanitizeFilename($filename);
            
            // The sanitized base name should not contain dots
            $this->assertStringNotContainsString(
                '.',
                $sanitized,
                "Sanitized base name should not contain dots/extensions"
            );
        }
    }

    /**
     * Test that empty or whitespace-only filenames result in empty string
     */
    public function testEmptyFilenamesHandledGracefully(): void
    {
        $emptyFilenames = [
            '',
            '   ',
            "\t\n\r",
            '...',
            '////',
            '\\\\\\\\',
        ];

        foreach ($emptyFilenames as $filename) {
            $sanitized = $this->uploadHandler->sanitizeFilename($filename);
            
            // Should either be empty or contain only safe characters
            $this->assertMatchesRegularExpression(
                '/^[a-z0-9_-]*$/',
                $sanitized,
                "Empty/special filename should result in safe output"
            );
        }
    }

    /**
     * Test that Unicode characters are removed from filenames
     */
    public function testUnicodeCharactersRemoved(): void
    {
        $unicodeFilenames = [
            'файл.jpg',           // Russian
            '文件.jpg',            // Chinese
            'αρχείο.jpg',         // Greek
            'ファイル.jpg',        // Japanese
            'file_émoji_🎉.jpg',  // Emoji
        ];

        foreach ($unicodeFilenames as $filename) {
            $sanitized = $this->uploadHandler->sanitizeFilename($filename);
            
            // Should only contain ASCII characters
            $this->assertMatchesRegularExpression(
                '/^[a-z0-9_-]*$/',
                $sanitized,
                "Unicode filename should be sanitized to ASCII-only"
            );
        }
    }
}
