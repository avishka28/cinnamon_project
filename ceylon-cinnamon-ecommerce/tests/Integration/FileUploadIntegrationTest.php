<?php
/**
 * File Upload Integration Test
 * Tests file upload workflows and security
 * 
 * Requirements:
 * - 6.2: Validate file types and sanitize file names for images
 * - 6.3: Validate file types and store securely for videos
 * - 10.4: Validate file types and scan for malicious content
 */

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

class FileUploadIntegrationTest extends TestCase
{
    private \FileUploadHandler $uploadHandler;
    private string $testUploadDir;

    protected function setUp(): void
    {
        parent::setUp();
        
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

    /**
     * Recursively remove a directory
     */
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
     * Test file upload handler initialization
     */
    public function testFileUploadHandlerInitialization(): void
    {
        $this->assertInstanceOf(\FileUploadHandler::class, $this->uploadHandler);
    }

    /**
     * Test allowed image types
     * Requirement 6.2: Validate file types for images
     */
    public function testAllowedImageTypes(): void
    {
        $allowedTypes = $this->uploadHandler->getAllowedImageTypes();
        
        // Should allow common image formats
        $this->assertArrayHasKey('image/jpeg', $allowedTypes);
        $this->assertArrayHasKey('image/png', $allowedTypes);
        $this->assertArrayHasKey('image/gif', $allowedTypes);
        $this->assertArrayHasKey('image/webp', $allowedTypes);
    }

    /**
     * Test allowed video types
     * Requirement 6.3: Validate file types for videos
     */
    public function testAllowedVideoTypes(): void
    {
        $allowedTypes = $this->uploadHandler->getAllowedVideoTypes();
        
        // Should allow common video formats
        $this->assertArrayHasKey('video/mp4', $allowedTypes);
        $this->assertArrayHasKey('video/webm', $allowedTypes);
    }

    /**
     * Test filename sanitization
     * Requirement 6.2: Sanitize file names
     */
    public function testFilenameSanitization(): void
    {
        // Test various dangerous filenames
        $testCases = [
            'normal_file' => 'normal_file',
            '../../../etc/passwd' => 'passwd',
            'file.php.jpg' => 'filephp',
            'UPPERCASE_FILE' => 'uppercase_file',
            'file@#$%^&*()' => 'file',
        ];
        
        foreach ($testCases as $input => $expected) {
            $sanitized = $this->uploadHandler->sanitizeFilename($input);
            $this->assertEquals($expected, $sanitized, "Failed for input: {$input}");
        }
    }

    /**
     * Test filename sanitization removes path traversal
     * Requirement 10.4: Security validation
     */
    public function testFilenameSanitizationRemovesPathTraversal(): void
    {
        $dangerous = '../../../etc/passwd';
        $sanitized = $this->uploadHandler->sanitizeFilename($dangerous);
        
        $this->assertStringNotContainsString('..', $sanitized);
        $this->assertStringNotContainsString('/', $sanitized);
        $this->assertStringNotContainsString('\\', $sanitized);
    }

    /**
     * Test filename sanitization removes PHP extensions
     * Requirement 10.4: Security validation
     */
    public function testFilenameSanitizationHandlesPhpExtensions(): void
    {
        $dangerous = 'malicious.php';
        $sanitized = $this->uploadHandler->sanitizeFilename($dangerous);
        
        // The sanitized name should not contain .php
        $this->assertStringNotContainsString('.php', $sanitized);
    }

    /**
     * Test upload error handling - no file
     */
    public function testUploadErrorHandlingNoFile(): void
    {
        $file = [
            'name' => '',
            'type' => '',
            'tmp_name' => '',
            'error' => UPLOAD_ERR_NO_FILE,
            'size' => 0
        ];
        
        $result = $this->uploadHandler->uploadImage($file);
        
        $this->assertNull($result);
        $this->assertNotEmpty($this->uploadHandler->getLastError());
    }

    /**
     * Test upload error handling - file too large
     */
    public function testUploadErrorHandlingFileTooLarge(): void
    {
        $file = [
            'name' => 'large_file.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => '',
            'error' => UPLOAD_ERR_INI_SIZE,
            'size' => 0
        ];
        
        $result = $this->uploadHandler->uploadImage($file);
        
        $this->assertNull($result);
        $this->assertNotEmpty($this->uploadHandler->getLastError());
    }

    /**
     * Test file validation without upload
     */
    public function testFileValidationWithoutUpload(): void
    {
        // Create a temporary test file
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tempFile, 'test content');
        
        $file = [
            'name' => 'test.txt',
            'type' => 'text/plain',
            'tmp_name' => $tempFile,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($tempFile)
        ];
        
        // Text files should not be valid as images
        $isValid = $this->uploadHandler->validateFile($file, 'image');
        
        $this->assertFalse($isValid);
        
        unlink($tempFile);
    }

    /**
     * Test get last error
     */
    public function testGetLastError(): void
    {
        // Initially no error
        $handler = new \FileUploadHandler($this->testUploadDir);
        
        // Trigger an error
        $file = [
            'name' => '',
            'type' => '',
            'tmp_name' => '',
            'error' => UPLOAD_ERR_NO_FILE,
            'size' => 0
        ];
        
        $handler->uploadImage($file);
        
        $error = $handler->getLastError();
        $this->assertNotNull($error);
        $this->assertIsString($error);
    }

    /**
     * Test delete file functionality
     */
    public function testDeleteFile(): void
    {
        // Create a test file
        $testDir = $this->testUploadDir . '/images';
        mkdir($testDir, 0755, true);
        $testFile = $testDir . '/test_file.jpg';
        file_put_contents($testFile, 'test content');
        
        $this->assertTrue(file_exists($testFile));
        
        // Delete the file
        $result = $this->uploadHandler->deleteFile('/uploads/images/test_file.jpg');
        
        $this->assertTrue($result);
        $this->assertFalse(file_exists($testFile));
    }

    /**
     * Test delete non-existent file
     */
    public function testDeleteNonExistentFile(): void
    {
        $result = $this->uploadHandler->deleteFile('/uploads/images/non_existent.jpg');
        
        $this->assertFalse($result);
    }

    /**
     * Test filename length limitation
     * Requirement 6.2: Sanitize file names
     */
    public function testFilenameLengthLimitation(): void
    {
        $longName = str_repeat('a', 200);
        $sanitized = $this->uploadHandler->sanitizeFilename($longName);
        
        // Filename should be limited to 100 characters
        $this->assertLessThanOrEqual(100, strlen($sanitized));
    }

    /**
     * Test filename lowercase conversion
     * Requirement 6.2: Sanitize file names
     */
    public function testFilenameLowercaseConversion(): void
    {
        $mixedCase = 'MyFileName';
        $sanitized = $this->uploadHandler->sanitizeFilename($mixedCase);
        
        $this->assertEquals('myfilename', $sanitized);
    }

    /**
     * Test empty filename handling
     */
    public function testEmptyFilenameHandling(): void
    {
        $sanitized = $this->uploadHandler->sanitizeFilename('');
        
        // Empty filename should result in empty string
        $this->assertEquals('', $sanitized);
    }

    /**
     * Test special characters removal
     * Requirement 6.2: Sanitize file names
     */
    public function testSpecialCharactersRemoval(): void
    {
        $specialChars = 'file!@#$%^&*()+=[]{}|;:\'",.<>?/\\`~';
        $sanitized = $this->uploadHandler->sanitizeFilename($specialChars);
        
        // Only alphanumeric, underscore, and hyphen should remain
        $this->assertMatchesRegularExpression('/^[a-z0-9_-]*$/', $sanitized);
    }

    /**
     * Test unicode character handling
     */
    public function testUnicodeCharacterHandling(): void
    {
        $unicode = 'файл_文件_αρχείο';
        $sanitized = $this->uploadHandler->sanitizeFilename($unicode);
        
        // Unicode characters should be removed
        $this->assertMatchesRegularExpression('/^[a-z0-9_-]*$/', $sanitized);
    }

    /**
     * Test multiple consecutive special characters
     */
    public function testMultipleConsecutiveSpecialCharacters(): void
    {
        $input = 'file___---name';
        $sanitized = $this->uploadHandler->sanitizeFilename($input);
        
        // Should preserve underscores and hyphens
        $this->assertStringContainsString('_', $sanitized);
        $this->assertStringContainsString('-', $sanitized);
    }
}
