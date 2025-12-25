<?php
/**
 * Input Sanitizer Class
 * Provides comprehensive input sanitization and XSS protection
 * 
 * Requirements:
 * - 10.3: Sanitize and validate all user input server-side
 */

declare(strict_types=1);

class InputSanitizer
{
    /**
     * Sanitize a string for safe output (XSS protection)
     * Requirements: 10.3 - XSS protection
     * 
     * @param string $input Input string
     * @return string Sanitized string
     */
    public static function sanitizeString(string $input): string
    {
        // Trim whitespace
        $input = trim($input);
        
        // Convert special characters to HTML entities
        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * Sanitize input for HTML output (alias for sanitizeString)
     * 
     * @param string $input Input string
     * @return string Sanitized string
     */
    public static function html(string $input): string
    {
        return self::sanitizeString($input);
    }
    
    /**
     * Sanitize input for use in HTML attributes
     * 
     * @param string $input Input string
     * @return string Sanitized string
     */
    public static function attribute(string $input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * Sanitize input for use in JavaScript
     * 
     * @param string $input Input string
     * @return string JSON-encoded string (safe for JS)
     */
    public static function js(string $input): string
    {
        return json_encode($input, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    }
    
    /**
     * Sanitize input for use in URLs
     * 
     * @param string $input Input string
     * @return string URL-encoded string
     */
    public static function url(string $input): string
    {
        return urlencode(trim($input));
    }
    
    /**
     * Sanitize email address
     * 
     * @param string $email Email address
     * @return string|false Sanitized email or false if invalid
     */
    public static function email(string $email): string|false
    {
        $email = trim($email);
        $sanitized = filter_var($email, FILTER_SANITIZE_EMAIL);
        
        if ($sanitized === false || !filter_var($sanitized, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize integer
     * 
     * @param mixed $input Input value
     * @return int Sanitized integer
     */
    public static function int(mixed $input): int
    {
        return (int) filter_var($input, FILTER_SANITIZE_NUMBER_INT);
    }
    
    /**
     * Sanitize float
     * 
     * @param mixed $input Input value
     * @return float Sanitized float
     */
    public static function float(mixed $input): float
    {
        return (float) filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }
    
    /**
     * Sanitize boolean
     * 
     * @param mixed $input Input value
     * @return bool Sanitized boolean
     */
    public static function bool(mixed $input): bool
    {
        return filter_var($input, FILTER_VALIDATE_BOOLEAN);
    }
    
    /**
     * Sanitize filename (remove dangerous characters)
     * Requirements: 10.4 - File name sanitization
     * 
     * @param string $filename Filename
     * @return string Sanitized filename
     */
    public static function filename(string $filename): string
    {
        // Remove path traversal attempts
        $filename = basename($filename);
        
        // Remove null bytes
        $filename = str_replace("\0", '', $filename);
        
        // Remove dangerous characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
        
        // Prevent empty filename
        if (empty($filename)) {
            $filename = 'unnamed';
        }
        
        // Limit length
        if (strlen($filename) > 255) {
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            $name = pathinfo($filename, PATHINFO_FILENAME);
            $filename = substr($name, 0, 250 - strlen($ext)) . '.' . $ext;
        }
        
        return $filename;
    }
    
    /**
     * Sanitize slug (URL-friendly string)
     * 
     * @param string $input Input string
     * @return string Sanitized slug
     */
    public static function slug(string $input): string
    {
        // Convert to lowercase
        $slug = strtolower(trim($input));
        
        // Replace spaces with hyphens
        $slug = preg_replace('/\s+/', '-', $slug);
        
        // Remove non-alphanumeric characters except hyphens
        $slug = preg_replace('/[^a-z0-9-]/', '', $slug);
        
        // Remove multiple consecutive hyphens
        $slug = preg_replace('/-+/', '-', $slug);
        
        // Trim hyphens from ends
        return trim($slug, '-');
    }
    
    /**
     * Strip all HTML tags
     * 
     * @param string $input Input string
     * @return string String without HTML tags
     */
    public static function stripTags(string $input): string
    {
        return strip_tags(trim($input));
    }
    
    /**
     * Strip HTML tags but allow specific tags
     * 
     * @param string $input Input string
     * @param array $allowedTags Allowed HTML tags
     * @return string Sanitized string
     */
    public static function stripTagsExcept(string $input, array $allowedTags = []): string
    {
        $allowed = implode('', array_map(fn($tag) => "<{$tag}>", $allowedTags));
        return strip_tags(trim($input), $allowed);
    }
    
    /**
     * Sanitize rich text (allow safe HTML tags)
     * 
     * @param string $input Input string
     * @return string Sanitized HTML
     */
    public static function richText(string $input): string
    {
        // Allow only safe HTML tags
        $allowedTags = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 'ul', 'ol', 'li', 'a', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote'];
        
        $input = self::stripTagsExcept($input, $allowedTags);
        
        // Remove dangerous attributes (onclick, onerror, etc.)
        $input = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/i', '', $input);
        
        // Remove javascript: URLs
        $input = preg_replace('/href\s*=\s*["\']javascript:[^"\']*["\']/i', 'href="#"', $input);
        
        return $input;
    }
    
    /**
     * Sanitize phone number
     * 
     * @param string $phone Phone number
     * @return string Sanitized phone number
     */
    public static function phone(string $phone): string
    {
        // Keep only digits, spaces, dashes, parentheses, and plus sign
        return preg_replace('/[^\d\s\-\(\)\+]/', '', trim($phone));
    }
    
    /**
     * Sanitize array of inputs
     * 
     * @param array $input Input array
     * @param callable $sanitizer Sanitizer function
     * @return array Sanitized array
     */
    public static function array(array $input, callable $sanitizer): array
    {
        return array_map($sanitizer, $input);
    }
    
    /**
     * Sanitize all string values in an array recursively
     * 
     * @param array $input Input array
     * @return array Sanitized array
     */
    public static function arrayRecursive(array $input): array
    {
        $result = [];
        
        foreach ($input as $key => $value) {
            // Sanitize the key
            $sanitizedKey = is_string($key) ? self::sanitizeString($key) : $key;
            
            if (is_array($value)) {
                $result[$sanitizedKey] = self::arrayRecursive($value);
            } elseif (is_string($value)) {
                $result[$sanitizedKey] = self::sanitizeString($value);
            } else {
                $result[$sanitizedKey] = $value;
            }
        }
        
        return $result;
    }
    
    /**
     * Remove null bytes from input
     * 
     * @param string $input Input string
     * @return string String without null bytes
     */
    public static function removeNullBytes(string $input): string
    {
        return str_replace("\0", '', $input);
    }
    
    /**
     * Sanitize SQL identifier (table/column name)
     * Note: This is NOT for values - use prepared statements for values
     * 
     * @param string $identifier SQL identifier
     * @return string Sanitized identifier
     */
    public static function sqlIdentifier(string $identifier): string
    {
        // Only allow alphanumeric and underscore
        return preg_replace('/[^a-zA-Z0-9_]/', '', $identifier);
    }
    
    /**
     * Sanitize input based on type
     * 
     * @param mixed $input Input value
     * @param string $type Type (string, int, float, bool, email, url, phone, filename, slug)
     * @return mixed Sanitized value
     */
    public static function sanitize(mixed $input, string $type = 'string'): mixed
    {
        if ($input === null) {
            return match ($type) {
                'int' => 0,
                'float' => 0.0,
                'bool' => false,
                default => ''
            };
        }
        
        return match ($type) {
            'string', 'html' => self::sanitizeString((string) $input),
            'int', 'integer' => self::int($input),
            'float', 'double' => self::float($input),
            'bool', 'boolean' => self::bool($input),
            'email' => self::email((string) $input),
            'url' => self::url((string) $input),
            'phone' => self::phone((string) $input),
            'filename' => self::filename((string) $input),
            'slug' => self::slug((string) $input),
            'strip_tags' => self::stripTags((string) $input),
            'rich_text' => self::richText((string) $input),
            default => self::sanitizeString((string) $input)
        };
    }
    
    /**
     * Check if input contains potential XSS attack patterns
     * 
     * @param string $input Input string
     * @return bool True if potential XSS detected
     */
    public static function containsXss(string $input): bool
    {
        // Common XSS patterns
        $patterns = [
            '/<script\b[^>]*>/i',
            '/javascript\s*:/i',
            '/on\w+\s*=/i',
            '/<\s*iframe/i',
            '/<\s*object/i',
            '/<\s*embed/i',
            '/<\s*link/i',
            '/<\s*style/i',
            '/expression\s*\(/i',
            '/url\s*\(/i',
            '/data\s*:/i',
            '/vbscript\s*:/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if input contains potential SQL injection patterns
     * Note: Always use prepared statements - this is just an additional check
     * 
     * @param string $input Input string
     * @return bool True if potential SQL injection detected
     */
    public static function containsSqlInjection(string $input): bool
    {
        // Common SQL injection patterns
        $patterns = [
            '/\b(union|select|insert|update|delete|drop|truncate|alter|create|exec|execute)\b/i',
            '/--/',
            '/\/\*/',
            '/\*\//',
            '/;\s*(select|insert|update|delete|drop)/i',
            '/\bor\b\s+\d+\s*=\s*\d+/i',
            '/\band\b\s+\d+\s*=\s*\d+/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        return false;
    }
}
