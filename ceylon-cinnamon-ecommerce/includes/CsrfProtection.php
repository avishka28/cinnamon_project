<?php
/**
 * CSRF Protection Class
 * Provides centralized CSRF token generation and validation
 * 
 * Requirements:
 * - 10.2: CSRF token validation for all form submissions
 */

declare(strict_types=1);

class CsrfProtection
{
    /**
     * Token name used in forms and session
     */
    public const TOKEN_NAME = 'csrf_token';
    
    /**
     * Token length in bytes (32 bytes = 64 hex characters)
     */
    private const TOKEN_LENGTH = 32;
    
    /**
     * Session manager instance
     */
    private SessionManager $sessionManager;
    
    /**
     * Constructor
     * 
     * @param SessionManager|null $sessionManager Optional session manager instance
     */
    public function __construct(?SessionManager $sessionManager = null)
    {
        $this->sessionManager = $sessionManager ?? new SessionManager();
    }
    
    /**
     * Generate a new CSRF token
     * Requirements: 10.2 - Generate secure CSRF tokens
     * 
     * @return string The generated token
     */
    public function generateToken(): string
    {
        $token = bin2hex(random_bytes(self::TOKEN_LENGTH));
        $this->sessionManager->set(self::TOKEN_NAME, $token);
        return $token;
    }
    
    /**
     * Get the current CSRF token, generating one if it doesn't exist
     * 
     * @return string The current token
     */
    public function getToken(): string
    {
        $token = $this->sessionManager->get(self::TOKEN_NAME);
        
        if ($token === null) {
            $token = $this->generateToken();
        }
        
        return $token;
    }
    
    /**
     * Validate a CSRF token
     * Requirements: 10.2 - Validate CSRF tokens on form submission
     * 
     * @param string $token The token to validate
     * @return bool True if valid, false otherwise
     */
    public function validateToken(string $token): bool
    {
        if (empty($token)) {
            return false;
        }
        
        $storedToken = $this->sessionManager->get(self::TOKEN_NAME);
        
        if ($storedToken === null) {
            return false;
        }
        
        // Use timing-safe comparison to prevent timing attacks
        return hash_equals($storedToken, $token);
    }
    
    /**
     * Regenerate the CSRF token
     * Should be called after successful form submission for extra security
     * 
     * @return string The new token
     */
    public function regenerateToken(): string
    {
        return $this->generateToken();
    }
    
    /**
     * Get token from request (POST, GET, or header)
     * 
     * @return string|null The token or null if not found
     */
    public function getTokenFromRequest(): ?string
    {
        // Check POST data first
        if (isset($_POST[self::TOKEN_NAME])) {
            return $_POST[self::TOKEN_NAME];
        }
        
        // Check GET data
        if (isset($_GET[self::TOKEN_NAME])) {
            return $_GET[self::TOKEN_NAME];
        }
        
        // Check X-CSRF-TOKEN header (for AJAX requests)
        if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            return $_SERVER['HTTP_X_CSRF_TOKEN'];
        }
        
        // Check JSON body for AJAX requests
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') !== false) {
            $rawInput = file_get_contents('php://input');
            if (!empty($rawInput)) {
                $data = json_decode($rawInput, true);
                if (is_array($data) && isset($data[self::TOKEN_NAME])) {
                    return $data[self::TOKEN_NAME];
                }
            }
        }
        
        return null;
    }
    
    /**
     * Validate the CSRF token from the current request
     * 
     * @return bool True if valid, false otherwise
     */
    public function validateRequest(): bool
    {
        $token = $this->getTokenFromRequest();
        
        if ($token === null) {
            return false;
        }
        
        return $this->validateToken($token);
    }
    
    /**
     * Generate a hidden input field with the CSRF token
     * 
     * @return string HTML hidden input element
     */
    public function getHiddenInput(): string
    {
        $token = htmlspecialchars($this->getToken(), ENT_QUOTES, 'UTF-8');
        return sprintf(
            '<input type="hidden" name="%s" value="%s">',
            self::TOKEN_NAME,
            $token
        );
    }
    
    /**
     * Generate a meta tag with the CSRF token (for JavaScript access)
     * 
     * @return string HTML meta element
     */
    public function getMetaTag(): string
    {
        $token = htmlspecialchars($this->getToken(), ENT_QUOTES, 'UTF-8');
        return sprintf(
            '<meta name="csrf-token" content="%s">',
            $token
        );
    }
    
    /**
     * Get the token name constant
     * 
     * @return string Token name
     */
    public static function getTokenName(): string
    {
        return self::TOKEN_NAME;
    }
}
