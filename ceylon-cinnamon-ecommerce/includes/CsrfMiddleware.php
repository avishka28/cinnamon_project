<?php
/**
 * CSRF Middleware
 * Validates CSRF tokens on POST, PUT, PATCH, DELETE requests
 * 
 * Requirements:
 * - 10.2: CSRF token validation for all form submissions
 */

declare(strict_types=1);

class CsrfMiddleware implements MiddlewareInterface
{
    /**
     * CSRF protection instance
     */
    private CsrfProtection $csrfProtection;
    
    /**
     * Session manager instance
     */
    private SessionManager $sessionManager;
    
    /**
     * HTTP methods that require CSRF validation
     */
    private const PROTECTED_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];
    
    /**
     * Routes to exclude from CSRF validation (e.g., webhooks)
     */
    private array $excludedRoutes = [];
    
    /**
     * Constructor
     * 
     * @param array $excludedRoutes Routes to exclude from CSRF validation
     */
    public function __construct(array $excludedRoutes = [])
    {
        $this->sessionManager = new SessionManager();
        $this->csrfProtection = new CsrfProtection($this->sessionManager);
        $this->excludedRoutes = $excludedRoutes;
    }
    
    /**
     * Handle the middleware
     * Requirements: 10.2 - Validate CSRF tokens on form submission
     * 
     * @return bool True if request should continue, false otherwise
     */
    public function handle(): bool
    {
        // Start session if not already started
        $this->sessionManager->start();
        
        // Get request method
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        
        // Only validate protected methods
        if (!in_array($method, self::PROTECTED_METHODS, true)) {
            return true;
        }
        
        // Check if route is excluded
        $currentPath = $this->getCurrentPath();
        if ($this->isExcludedRoute($currentPath)) {
            return true;
        }
        
        // Validate CSRF token
        if (!$this->csrfProtection->validateRequest()) {
            $this->handleInvalidToken();
            return false;
        }
        
        return true;
    }
    
    /**
     * Get the current request path
     * 
     * @return string Current path
     */
    private function getCurrentPath(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);
        return $path ?: '/';
    }
    
    /**
     * Check if the current route is excluded from CSRF validation
     * 
     * @param string $path Current path
     * @return bool True if excluded
     */
    private function isExcludedRoute(string $path): bool
    {
        foreach ($this->excludedRoutes as $excludedRoute) {
            // Support wildcard patterns
            if (strpos($excludedRoute, '*') !== false) {
                $pattern = str_replace('*', '.*', $excludedRoute);
                if (preg_match('#^' . $pattern . '$#', $path)) {
                    return true;
                }
            } elseif ($path === $excludedRoute) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Handle invalid CSRF token
     */
    private function handleInvalidToken(): void
    {
        // Check if this is an AJAX request
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) 
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        
        if ($isAjax) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Invalid security token. Please refresh the page and try again.'
            ]);
            exit;
        }
        
        // For regular requests, set flash message and redirect
        $this->sessionManager->flash('error', 'Invalid security token. Please try again.');
        
        // Redirect to referrer or home
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        http_response_code(302);
        header('Location: ' . $referer);
        exit;
    }
    
    /**
     * Add a route to the exclusion list
     * 
     * @param string $route Route to exclude
     * @return self
     */
    public function exclude(string $route): self
    {
        $this->excludedRoutes[] = $route;
        return $this;
    }
    
    /**
     * Get the CSRF protection instance
     * 
     * @return CsrfProtection
     */
    public function getCsrfProtection(): CsrfProtection
    {
        return $this->csrfProtection;
    }
    
    /**
     * Static method to validate CSRF token
     * Convenience method for use in controllers
     * 
     * @param string $token Token to validate
     * @return bool True if valid
     */
    public static function validate(string $token): bool
    {
        $sessionManager = new SessionManager();
        $csrfProtection = new CsrfProtection($sessionManager);
        return $csrfProtection->validateToken($token);
    }
    
    /**
     * Static method to get current CSRF token
     * Convenience method for use in views
     * 
     * @return string Current token
     */
    public static function token(): string
    {
        $sessionManager = new SessionManager();
        $csrfProtection = new CsrfProtection($sessionManager);
        return $csrfProtection->getToken();
    }
    
    /**
     * Static method to get hidden input field
     * Convenience method for use in views
     * 
     * @return string HTML hidden input
     */
    public static function field(): string
    {
        $sessionManager = new SessionManager();
        $csrfProtection = new CsrfProtection($sessionManager);
        return $csrfProtection->getHiddenInput();
    }
}
