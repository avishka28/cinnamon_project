<?php
/**
 * Role Middleware
 * Provides granular role-based access control for routes
 * 
 * Requirements:
 * - 2.5: Support three user roles (customer, admin, content_manager)
 * - 2.6: Admin access to all administrative functions
 * - 2.7: Content manager limited access to content management only
 */

declare(strict_types=1);

class RoleMiddleware implements MiddlewareInterface
{
    private SessionManager $sessionManager;
    
    /**
     * Roles required to pass this middleware
     * @var array<string>
     */
    private array $allowedRoles;

    /**
     * Role hierarchy - higher roles include permissions of lower roles
     */
    private const ROLE_HIERARCHY = [
        'admin' => ['admin', 'content_manager', 'customer'],
        'content_manager' => ['content_manager', 'customer'],
        'customer' => ['customer']
    ];

    /**
     * Create a new RoleMiddleware instance
     * 
     * @param array<string> $allowedRoles Roles allowed to access the route
     */
    public function __construct(array $allowedRoles = [])
    {
        $this->sessionManager = new SessionManager();
        $this->allowedRoles = $allowedRoles;
    }

    /**
     * Handle the middleware check
     * Requirements: 2.5, 2.6, 2.7 - Role-based access control
     * 
     * @return bool True if user has required role
     */
    public function handle(): bool
    {
        $this->sessionManager->start();

        // Check if user is logged in
        if (!$this->sessionManager->isLoggedIn()) {
            $this->redirectToLogin();
            return false;
        }

        // If no roles specified, just require authentication
        if (empty($this->allowedRoles)) {
            return true;
        }

        // Check if user has any of the allowed roles
        $userRole = $this->sessionManager->getUserRole();
        
        if (!$this->hasRequiredRole($userRole)) {
            $this->denyAccess();
            return false;
        }

        return true;
    }

    /**
     * Check if user role satisfies the required roles
     * Uses role hierarchy - admin can access everything
     * 
     * @param string|null $userRole User's current role
     * @return bool True if user has required role
     */
    private function hasRequiredRole(?string $userRole): bool
    {
        if ($userRole === null) {
            return false;
        }

        // Get all roles the user effectively has based on hierarchy
        $effectiveRoles = self::ROLE_HIERARCHY[$userRole] ?? [$userRole];

        // Check if any of the user's effective roles match allowed roles
        foreach ($this->allowedRoles as $allowedRole) {
            if (in_array($allowedRole, $effectiveRoles, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Redirect to login page
     */
    private function redirectToLogin(): void
    {
        $redirect = urlencode($_SERVER['REQUEST_URI'] ?? '/');
        http_response_code(302);
        header('Location: /login?redirect=' . $redirect);
        exit;
    }

    /**
     * Deny access with 403 response
     */
    private function denyAccess(): void
    {
        http_response_code(403);
        
        // Check if this is an API request
        if ($this->isApiRequest()) {
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Access denied',
                'message' => 'You do not have permission to access this resource'
            ]);
        } else {
            $this->showAccessDeniedPage();
        }
        
        exit;
    }

    /**
     * Check if request is an API request
     * 
     * @return bool True if API request
     */
    private function isApiRequest(): bool
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        return str_starts_with($uri, '/api/') 
            || (!empty($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));
    }

    /**
     * Show access denied HTML page
     */
    private function showAccessDeniedPage(): void
    {
        echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied - Ceylon Cinnamon</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 text-center">
                <h1 class="display-1 text-danger">403</h1>
                <h2>Access Denied</h2>
                <p class="lead">You do not have permission to access this page.</p>
                <div class="mt-4">
                    <a href="/" class="btn btn-primary me-2">Return to Home</a>
                    <a href="/dashboard" class="btn btn-outline-secondary">Go to Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>';
    }

    /**
     * Create middleware for admin-only access
     * Requirements: 2.6 - Admin access to all administrative functions
     * 
     * @return self
     */
    public static function adminOnly(): self
    {
        return new self(['admin']);
    }

    /**
     * Create middleware for content manager access
     * Requirements: 2.7 - Content manager limited access
     * 
     * @return self
     */
    public static function contentManager(): self
    {
        return new self(['admin', 'content_manager']);
    }

    /**
     * Create middleware for customer access
     * 
     * @return self
     */
    public static function customer(): self
    {
        return new self(['admin', 'content_manager', 'customer']);
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

    /**
     * Get the allowed roles
     * 
     * @return array<string>
     */
    public function getAllowedRoles(): array
    {
        return $this->allowedRoles;
    }

    /**
     * Check if a specific role is allowed
     * 
     * @param string $role Role to check
     * @return bool True if role is allowed
     */
    public function isRoleAllowed(string $role): bool
    {
        return $this->hasRequiredRole($role);
    }
}
