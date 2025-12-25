<?php
/**
 * Session Manager
 * Handles secure session management with proper cookie flags
 * 
 * Requirements:
 * - 2.3: Secure cookie flags (HttpOnly, Secure, SameSite)
 * - 10.6: Secure session management with appropriate timeouts
 */

declare(strict_types=1);

class SessionManager
{
    /**
     * Session configuration
     */
    private const SESSION_LIFETIME = 3600; // 1 hour default
    private const SESSION_NAME = 'ceylon_session';
    private const REGENERATE_INTERVAL = 300; // Regenerate ID every 5 minutes

    /**
     * Session cookie parameters for security
     * Requirements: 2.3 - HttpOnly, Secure, SameSite flags
     */
    private array $cookieParams;

    /**
     * Whether session has been started
     */
    private bool $started = false;

    public function __construct()
    {
        $this->cookieParams = $this->getSecureCookieParams();
    }

    /**
     * Get secure cookie parameters
     * Requirements: 2.3 - Secure cookie flags
     * 
     * @return array Cookie parameters
     */
    private function getSecureCookieParams(): array
    {
        $isSecure = $this->isHttps();
        
        return [
            'lifetime' => defined('SESSION_LIFETIME') ? SESSION_LIFETIME : self::SESSION_LIFETIME,
            'path' => '/',
            'domain' => '',
            'secure' => $isSecure,      // Only send over HTTPS
            'httponly' => true,          // Prevent JavaScript access
            'samesite' => 'Strict'       // Prevent CSRF attacks
        ];
    }

    /**
     * Check if connection is HTTPS
     * 
     * @return bool True if HTTPS
     */
    private function isHttps(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    }

    /**
     * Start a secure session
     * Requirements: 2.3, 10.6 - Secure session management
     * 
     * @return bool True if session started successfully
     */
    public function start(): bool
    {
        if ($this->started || session_status() === PHP_SESSION_ACTIVE) {
            $this->started = true;
            return true;
        }

        // Configure session before starting
        $this->configureSession();

        // Start session
        if (!session_start()) {
            return false;
        }

        $this->started = true;

        // Check for session timeout
        $this->checkTimeout();

        // Regenerate session ID periodically
        $this->maybeRegenerateId();

        return true;
    }

    /**
     * Configure session settings
     * Requirements: 2.3 - Secure cookie flags
     */
    private function configureSession(): void
    {
        // Set session name
        $sessionName = defined('SESSION_NAME') ? SESSION_NAME : self::SESSION_NAME;
        session_name($sessionName);

        // Set cookie parameters with security flags
        session_set_cookie_params($this->cookieParams);

        // Additional security settings
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        
        if ($this->cookieParams['secure']) {
            ini_set('session.cookie_secure', '1');
        }
        
        ini_set('session.cookie_samesite', $this->cookieParams['samesite']);
        
        // Set garbage collection
        $lifetime = $this->cookieParams['lifetime'];
        ini_set('session.gc_maxlifetime', (string)$lifetime);
    }

    /**
     * Check for session timeout
     * Requirements: 10.6 - Session timeout
     */
    private function checkTimeout(): void
    {
        $lifetime = $this->cookieParams['lifetime'];
        
        if (isset($_SESSION['last_activity'])) {
            $inactive = time() - $_SESSION['last_activity'];
            
            if ($inactive > $lifetime) {
                $this->destroy();
                $this->start();
                return;
            }
        }

        $_SESSION['last_activity'] = time();
    }

    /**
     * Regenerate session ID periodically
     * Requirements: 10.6 - Session security
     */
    private function maybeRegenerateId(): void
    {
        if (!isset($_SESSION['regenerate_time'])) {
            $_SESSION['regenerate_time'] = time();
            return;
        }

        if (time() - $_SESSION['regenerate_time'] > self::REGENERATE_INTERVAL) {
            $this->regenerateId();
        }
    }

    /**
     * Regenerate session ID
     * 
     * @param bool $deleteOld Whether to delete old session
     * @return bool True on success
     */
    public function regenerateId(bool $deleteOld = true): bool
    {
        $this->ensureStarted();
        
        if (session_regenerate_id($deleteOld)) {
            $_SESSION['regenerate_time'] = time();
            return true;
        }
        
        return false;
    }

    /**
     * Login a user - create authenticated session
     * 
     * @param array $user User data
     */
    public function login(array $user): void
    {
        $this->ensureStarted();
        
        // Regenerate session ID on login to prevent session fixation
        $this->regenerateId();

        // Store user data in session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
    }

    /**
     * Logout user - destroy session
     */
    public function logout(): void
    {
        $this->ensureStarted();
        $this->destroy();
    }

    /**
     * Destroy the current session
     */
    public function destroy(): void
    {
        // Unset all session variables
        $_SESSION = [];

        // Delete session cookie
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        // Destroy the session
        session_destroy();
        $this->started = false;
    }

    /**
     * Check if user is logged in
     * 
     * @return bool True if logged in
     */
    public function isLoggedIn(): bool
    {
        $this->ensureStarted();
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    /**
     * Get current user ID
     * 
     * @return int|null User ID or null if not logged in
     */
    public function getUserId(): ?int
    {
        $this->ensureStarted();
        return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    }

    /**
     * Get current user role
     * 
     * @return string|null User role or null if not logged in
     */
    public function getUserRole(): ?string
    {
        $this->ensureStarted();
        return $_SESSION['user_role'] ?? null;
    }

    /**
     * Get current user email
     * 
     * @return string|null User email or null if not logged in
     */
    public function getUserEmail(): ?string
    {
        $this->ensureStarted();
        return $_SESSION['user_email'] ?? null;
    }

    /**
     * Get current user name
     * 
     * @return string|null User name or null if not logged in
     */
    public function getUserName(): ?string
    {
        $this->ensureStarted();
        return $_SESSION['user_name'] ?? null;
    }

    /**
     * Check if user has a specific role
     * 
     * @param string $role Role to check
     * @return bool True if user has role
     */
    public function hasRole(string $role): bool
    {
        return $this->getUserRole() === $role;
    }

    /**
     * Check if user is admin
     * 
     * @return bool True if admin
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Check if user is content manager
     * 
     * @return bool True if content manager
     */
    public function isContentManager(): bool
    {
        return $this->hasRole('content_manager');
    }

    /**
     * Generate CSRF token
     * 
     * @return string CSRF token
     */
    public function getCsrfToken(): string
    {
        $this->ensureStarted();
        
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }

    /**
     * Validate CSRF token
     * 
     * @param string $token Token to validate
     * @return bool True if valid
     */
    public function validateCsrfToken(string $token): bool
    {
        $this->ensureStarted();
        
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Regenerate CSRF token
     * 
     * @return string New CSRF token
     */
    public function regenerateCsrfToken(): string
    {
        $this->ensureStarted();
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf_token'];
    }

    /**
     * Set a session value
     * 
     * @param string $key Session key
     * @param mixed $value Value to store
     */
    public function set(string $key, mixed $value): void
    {
        $this->ensureStarted();
        $_SESSION[$key] = $value;
    }

    /**
     * Get a session value
     * 
     * @param string $key Session key
     * @param mixed $default Default value if not found
     * @return mixed Session value or default
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $this->ensureStarted();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Remove a session value
     * 
     * @param string $key Session key
     */
    public function remove(string $key): void
    {
        $this->ensureStarted();
        unset($_SESSION[$key]);
    }

    /**
     * Check if session has a key
     * 
     * @param string $key Session key
     * @return bool True if key exists
     */
    public function has(string $key): bool
    {
        $this->ensureStarted();
        return isset($_SESSION[$key]);
    }

    /**
     * Flash a message to session (available for one request)
     * 
     * @param string $key Flash key
     * @param mixed $value Flash value
     */
    public function flash(string $key, mixed $value): void
    {
        $this->ensureStarted();
        $_SESSION['_flash'][$key] = $value;
    }

    /**
     * Set a flash message (alias for flash)
     * 
     * @param string $key Flash key
     * @param mixed $value Flash value
     */
    public function setFlash(string $key, mixed $value): void
    {
        $this->flash($key, $value);
    }

    /**
     * Get current user data
     * 
     * @return array|null User data or null if not logged in
     */
    public function getUser(): ?array
    {
        $this->ensureStarted();
        
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'] ?? null,
            'email' => $_SESSION['user_email'] ?? null,
            'role' => $_SESSION['user_role'] ?? null,
            'first_name' => explode(' ', $_SESSION['user_name'] ?? '')[0] ?? '',
            'last_name' => explode(' ', $_SESSION['user_name'] ?? '')[1] ?? '',
            'name' => $_SESSION['user_name'] ?? ''
        ];
    }

    /**
     * Get and remove a flash message
     * 
     * @param string $key Flash key
     * @param mixed $default Default value
     * @return mixed Flash value or default
     */
    public function getFlash(string $key, mixed $default = null): mixed
    {
        $this->ensureStarted();
        
        if (isset($_SESSION['_flash'][$key])) {
            $value = $_SESSION['_flash'][$key];
            unset($_SESSION['_flash'][$key]);
            return $value;
        }
        
        return $default;
    }

    /**
     * Get the current cookie parameters
     * Useful for testing
     * 
     * @return array Cookie parameters
     */
    public function getCookieParams(): array
    {
        return $this->cookieParams;
    }

    /**
     * Ensure session is started
     */
    private function ensureStarted(): void
    {
        if (!$this->started && session_status() !== PHP_SESSION_ACTIVE) {
            $this->start();
        }
    }
}
