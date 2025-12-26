<?php
/**
 * Authentication Middleware
 * Ensures user is logged in before accessing protected routes
 * 
 * Requirements:
 * - 2.4: Redirect unauthorized access to login page
 * - 2.5: Support role-based access control
 */

declare(strict_types=1);

class AuthMiddleware implements MiddlewareInterface
{
    private SessionManager $sessionManager;

    public function __construct()
    {
        $this->sessionManager = new SessionManager();
    }

    /**
     * Handle the middleware check
     * Requirements: 2.4 - Redirect unauthorized access to login page
     * 
     * @return bool True if user is authenticated
     */
    public function handle(): bool
    {
        $this->sessionManager->start();

        if (!$this->sessionManager->isLoggedIn()) {
            $redirect = urlencode($_SERVER['REQUEST_URI'] ?? '/dashboard');
            http_response_code(302);
            header('Location: ' . url('/login') . '?redirect=' . $redirect);
            exit;
        }

        return true;
    }

    /**
     * Get the session manager instance
     * 
     * @return SessionManager
     */
    public function getSessionManager(): SessionManager
    {
        return $this->sessionManager;
    }
}
