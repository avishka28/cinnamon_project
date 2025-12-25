<?php
/**
 * File Upload Handler
 * Handles secure file uploads with validation and sanitization
 * 
 * Requirements:
 * - 6.2: Validate file types and sanitize file names for images
 * - 6.3: Validate file types and store securely for videos
 * - 10.4: Validate file types and scan for malicious content
 */

declare(strict_types=1);

class FileUploadHandler
{
    /**
     * Allowed MIME types for images
     * Requirements: 6.2 - Validate file types for images
     */
    private const ALLOWED_IMAGE_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp'
    ];

    /**
     * Allowed MIME types for videos
     * Requirements: 6.3 - Validate file types for videos
     */
    private const ALLOWED_VIDEO_TYPES = [
        'video/mp4' => 'mp4',
        'video/webm' => 'webm',
        'video/ogg' => 'ogv'
    ];

    /**
     * Allowed MIME types for documents
     */
    private const ALLOWED_DOC_TYPES = [
        'application/pdf' => 'pdf'
    ];

    /**
     * Maximum file sizes in bytes
     */
    private const MAX_IMAGE_SIZE = 10485760;  // 10MB
    private const MAX_VIDEO_SIZE = 104857600; // 100MB
    private const MAX_DOC_SIZE = 20971520;    // 20MB

    /**
     * Upload directory base path
     */
    private string $uploadBasePath;

    /**
     * Last error message
     */
    private ?string $lastError = null;

    public function __construct(?string $uploadBasePath = null)
    {
        $this->uploadBasePath = $uploadBasePath ?? (defined('UPLOADS_PATH') ? UPLOADS_PATH : __DIR__ . '/../public/uploads');
    }

    /**
     * Upload an image file
     * Requirements: 6.2 - Validate file types and sanitize file names
     * 
     * @param array $file $_FILES array element
     * @param string $subDirectory Subdirectory within uploads
     * @param string|null $customName Custom filename (without extension)
     * @return string|null Relative URL path or null on failure
     */
    public function uploadImage(array $file, string $subDirectory = 'images', ?string $customName = null): ?string
    {
        return $this->upload($file, $subDirectory, self::ALLOWED_IMAGE_TYPES, self::MAX_IMAGE_SIZE, $customName);
    }

    /**
     * Upload a video file
     * Requirements: 6.3 - Validate file types and store securely
     * 
     * @param array $file $_FILES array element
     * @param string $subDirectory Subdirectory within uploads
     * @param string|null $customName Custom filename (without extension)
     * @return string|null Relative URL path or null on failure
     */
    public function uploadVideo(array $file, string $subDirectory = 'videos', ?string $customName = null): ?string
    {
        return $this->upload($file, $subDirectory, self::ALLOWED_VIDEO_TYPES, self::MAX_VIDEO_SIZE, $customName);
    }

    /**
     * Upload a document file
     * 
     * @param array $file $_FILES array element
     * @param string $subDirectory Subdirectory within uploads
     * @param string|null $customName Custom filename (without extension)
     * @return string|null Relative URL path or null on failure
     */
    public function uploadDocument(array $file, string $subDirectory = 'documents', ?string $customName = null): ?string
    {
        return $this->upload($file, $subDirectory, self::ALLOWED_DOC_TYPES, self::MAX_DOC_SIZE, $customName);
    }

    /**
     * Upload multiple images
     * 
     * @param array $files $_FILES array with multiple files
     * @param string $subDirectory Subdirectory within uploads
     * @param string|null $prefix Filename prefix
     * @return array Array of uploaded file paths (successful uploads only)
     */
    public function uploadMultipleImages(array $files, string $subDirectory = 'images', ?string $prefix = null): array
    {
        $uploaded = [];
        
        // Normalize files array structure
        $normalizedFiles = $this->normalizeFilesArray($files);
        
        foreach ($normalizedFiles as $index => $file) {
            $customName = $prefix ? $prefix . '_' . $index : null;
            $path = $this->uploadImage($file, $subDirectory, $customName);
            
            if ($path !== null) {
                $uploaded[] = $path;
            }
        }
        
        return $uploaded;
    }

    /**
     * Core upload method
     * Requirements: 10.4 - Validate file types and scan for malicious content
     * 
     * @param array $file $_FILES array element
     * @param string $subDirectory Subdirectory within uploads
     * @param array $allowedTypes Allowed MIME types with extensions
     * @param int $maxSize Maximum file size in bytes
     * @param string|null $customName Custom filename (without extension)
     * @return string|null Relative URL path or null on failure
     */
    private function upload(
        array $file,
        string $subDirectory,
        array $allowedTypes,
        int $maxSize,
        ?string $customName = null
    ): ?string {
        $this->lastError = null;

        // Check for upload errors
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            $this->lastError = $this->getUploadErrorMessage($file['error'] ?? UPLOAD_ERR_NO_FILE);
            return null;
        }

        // Validate file size
        if ($file['size'] > $maxSize) {
            $this->lastError = 'File size exceeds maximum allowed size of ' . $this->formatBytes($maxSize);
            return null;
        }

        // Validate MIME type using finfo (more secure than relying on $_FILES['type'])
        $mimeType = $this->detectMimeType($file['tmp_name']);
        if ($mimeType === null || !isset($allowedTypes[$mimeType])) {
            $this->lastError = 'Invalid file type. Allowed types: ' . implode(', ', array_values($allowedTypes));
            return null;
        }

        // Additional security check for images
        if (str_starts_with($mimeType, 'image/')) {
            if (!$this->isValidImage($file['tmp_name'])) {
                $this->lastError = 'Invalid or corrupted image file';
                return null;
            }
        }

        // Scan for malicious content (basic check)
        if (!$this->scanForMaliciousContent($file['tmp_name'])) {
            $this->lastError = 'File failed security scan';
            return null;
        }

        // Create upload directory if it doesn't exist
        $uploadDir = $this->uploadBasePath . '/' . $subDirectory;
        if (!$this->ensureDirectoryExists($uploadDir)) {
            $this->lastError = 'Failed to create upload directory';
            return null;
        }

        // Generate safe filename
        $extension = $allowedTypes[$mimeType];
        $filename = $this->generateSafeFilename($file['name'], $extension, $customName);
        $filepath = $uploadDir . '/' . $filename;

        // Ensure unique filename
        $filepath = $this->ensureUniqueFilename($filepath);
        $filename = basename($filepath);

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            $this->lastError = 'Failed to move uploaded file';
            return null;
        }

        // Set proper permissions
        chmod($filepath, 0644);

        // Return relative URL path
        return '/uploads/' . $subDirectory . '/' . $filename;
    }

    /**
     * Detect MIME type using finfo
     * Requirements: 10.4 - Validate file types
     * 
     * @param string $filepath Path to file
     * @return string|null MIME type or null
     */
    private function detectMimeType(string $filepath): ?string
    {
        if (!file_exists($filepath)) {
            return null;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            return null;
        }

        $mimeType = finfo_file($finfo, $filepath);
        finfo_close($finfo);

        return $mimeType !== false ? $mimeType : null;
    }

    /**
     * Validate image file integrity
     * 
     * @param string $filepath Path to image file
     * @return bool True if valid image
     */
    private function isValidImage(string $filepath): bool
    {
        // Try to get image info - this will fail for non-images
        $imageInfo = @getimagesize($filepath);
        
        if ($imageInfo === false) {
            return false;
        }

        // Check for valid image dimensions
        if ($imageInfo[0] <= 0 || $imageInfo[1] <= 0) {
            return false;
        }

        return true;
    }

    /**
     * Scan file for malicious content
     * Requirements: 10.4 - Scan for malicious content
     * 
     * @param string $filepath Path to file
     * @return bool True if file passes security scan
     */
    private function scanForMaliciousContent(string $filepath): bool
    {
        // Read file content for scanning
        $content = file_get_contents($filepath);
        if ($content === false) {
            return false;
        }

        // Check for PHP code injection attempts
        $dangerousPatterns = [
            '/<\?php/i',
            '/<\?=/i',
            '/<script[^>]*>/i',
            '/eval\s*\(/i',
            '/base64_decode\s*\(/i',
            '/exec\s*\(/i',
            '/system\s*\(/i',
            '/passthru\s*\(/i',
            '/shell_exec\s*\(/i',
            '/popen\s*\(/i',
            '/proc_open\s*\(/i'
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Generate a safe filename
     * Requirements: 6.2 - Sanitize file names
     * 
     * @param string $originalName Original filename
     * @param string $extension File extension
     * @param string|null $customName Custom name to use
     * @return string Safe filename
     */
    private function generateSafeFilename(string $originalName, string $extension, ?string $customName = null): string
    {
        if ($customName !== null) {
            // Sanitize custom name
            $baseName = $this->sanitizeFilename($customName);
        } else {
            // Get original name without extension
            $baseName = pathinfo($originalName, PATHINFO_FILENAME);
            $baseName = $this->sanitizeFilename($baseName);
        }

        // Ensure we have a valid base name
        if (empty($baseName)) {
            $baseName = 'file';
        }

        // Add timestamp for uniqueness
        $timestamp = date('Ymd_His');
        $uniqueId = substr(uniqid(), -6);

        return "{$baseName}_{$timestamp}_{$uniqueId}.{$extension}";
    }

    /**
     * Sanitize filename to remove dangerous characters
     * Requirements: 6.2 - Sanitize file names
     * 
     * @param string $filename Filename to sanitize
     * @return string Sanitized filename
     */
    public function sanitizeFilename(string $filename): string
    {
        // Remove any path components
        $filename = basename($filename);
        
        // Remove extension if present
        $filename = pathinfo($filename, PATHINFO_FILENAME);
        
        // Replace spaces with underscores
        $filename = str_replace(' ', '_', $filename);
        
        // Remove any character that isn't alphanumeric, underscore, or hyphen
        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '', $filename);
        
        // Limit length
        $filename = substr($filename, 0, 100);
        
        // Convert to lowercase
        $filename = strtolower($filename);
        
        return $filename;
    }

    /**
     * Ensure directory exists and is writable
     * 
     * @param string $directory Directory path
     * @return bool True if directory exists and is writable
     */
    private function ensureDirectoryExists(string $directory): bool
    {
        if (!is_dir($directory)) {
            if (!mkdir($directory, 0755, true)) {
                return false;
            }
        }

        return is_writable($directory);
    }

    /**
     * Ensure filename is unique by appending counter if needed
     * 
     * @param string $filepath Full file path
     * @return string Unique file path
     */
    private function ensureUniqueFilename(string $filepath): string
    {
        if (!file_exists($filepath)) {
            return $filepath;
        }

        $directory = dirname($filepath);
        $extension = pathinfo($filepath, PATHINFO_EXTENSION);
        $basename = pathinfo($filepath, PATHINFO_FILENAME);

        $counter = 1;
        do {
            $newFilepath = $directory . '/' . $basename . '_' . $counter . '.' . $extension;
            $counter++;
        } while (file_exists($newFilepath) && $counter < 1000);

        return $newFilepath;
    }

    /**
     * Normalize $_FILES array for multiple file uploads
     * 
     * @param array $files $_FILES array
     * @return array Normalized array of file arrays
     */
    private function normalizeFilesArray(array $files): array
    {
        $normalized = [];

        // Check if this is a multiple file upload
        if (isset($files['name']) && is_array($files['name'])) {
            $count = count($files['name']);
            for ($i = 0; $i < $count; $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    $normalized[] = [
                        'name' => $files['name'][$i],
                        'type' => $files['type'][$i],
                        'tmp_name' => $files['tmp_name'][$i],
                        'error' => $files['error'][$i],
                        'size' => $files['size'][$i]
                    ];
                }
            }
        } else {
            // Single file upload
            if (isset($files['error']) && $files['error'] === UPLOAD_ERR_OK) {
                $normalized[] = $files;
            }
        }

        return $normalized;
    }

    /**
     * Get human-readable upload error message
     * 
     * @param int $errorCode PHP upload error code
     * @return string Error message
     */
    private function getUploadErrorMessage(int $errorCode): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE => 'File exceeds the upload_max_filesize directive in php.ini',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds the MAX_FILE_SIZE directive in the HTML form',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload',
            default => 'Unknown upload error'
        };
    }

    /**
     * Format bytes to human-readable string
     * 
     * @param int $bytes Number of bytes
     * @return string Formatted string
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get the last error message
     * 
     * @return string|null Last error message or null
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Delete a file
     * 
     * @param string $relativePath Relative URL path (e.g., /uploads/images/file.jpg)
     * @return bool True if deleted successfully
     */
    public function deleteFile(string $relativePath): bool
    {
        // Convert relative URL to absolute path
        $absolutePath = $this->uploadBasePath . str_replace('/uploads', '', $relativePath);
        
        if (file_exists($absolutePath)) {
            return unlink($absolutePath);
        }
        
        return false;
    }

    /**
     * Get allowed image types
     * 
     * @return array Allowed MIME types
     */
    public function getAllowedImageTypes(): array
    {
        return self::ALLOWED_IMAGE_TYPES;
    }

    /**
     * Get allowed video types
     * 
     * @return array Allowed MIME types
     */
    public function getAllowedVideoTypes(): array
    {
        return self::ALLOWED_VIDEO_TYPES;
    }

    /**
     * Validate a file without uploading
     * 
     * @param array $file $_FILES array element
     * @param string $type Type of file ('image', 'video', 'document')
     * @return bool True if valid
     */
    public function validateFile(array $file, string $type = 'image'): bool
    {
        $this->lastError = null;

        $allowedTypes = match ($type) {
            'image' => self::ALLOWED_IMAGE_TYPES,
            'video' => self::ALLOWED_VIDEO_TYPES,
            'document' => self::ALLOWED_DOC_TYPES,
            default => self::ALLOWED_IMAGE_TYPES
        };

        $maxSize = match ($type) {
            'image' => self::MAX_IMAGE_SIZE,
            'video' => self::MAX_VIDEO_SIZE,
            'document' => self::MAX_DOC_SIZE,
            default => self::MAX_IMAGE_SIZE
        };

        // Check for upload errors
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            $this->lastError = $this->getUploadErrorMessage($file['error'] ?? UPLOAD_ERR_NO_FILE);
            return false;
        }

        // Validate file size
        if ($file['size'] > $maxSize) {
            $this->lastError = 'File size exceeds maximum allowed size of ' . $this->formatBytes($maxSize);
            return false;
        }

        // Validate MIME type
        $mimeType = $this->detectMimeType($file['tmp_name']);
        if ($mimeType === null || !isset($allowedTypes[$mimeType])) {
            $this->lastError = 'Invalid file type';
            return false;
        }

        return true;
    }
}
