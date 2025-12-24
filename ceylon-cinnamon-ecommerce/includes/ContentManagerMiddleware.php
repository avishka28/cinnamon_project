<?php
/**
 * Content Manager Middleware
 * Ensures user has content_manager or admin role for content management
 * 
 * Requirements:
 * - 2.7: Content manager limited access to content management only
 */

declare(strict_types=1);

class ContentManagerMiddleware implements MiddlewareInterface
{
    private SessionManager $sessionManager;
    
    /**
     * Roles allowed to manage content
     */
    private const ALLOWED_ROLES = ['admin', 'content_manager'];

    public function __construct()
    {
        $this->sessionManager = new SessionManager();
    }

    /**
     * Handle the middleware check
     * Requirements: 2.7 - Content manager access to content management
     * 
     * @return bool True if user can manage content
     */
    public function handle(): bool
    {
        $this->sessionManager->start();

        // Check if user is logged in
        if (!$this->sessionManager->isLoggedIn()) {
            http_response_code(302);
            header('Location: /admin/login');
            exit;
        }

        // Check if user has content management role
        $userRole = $this->sessionManager->getUserRole();
        if (!in_array($userRole, self::ALLOWED_ROLES, true)) {
            http_response_code(403);
            $this->showAccessDenied();
            exit;
        }

        return true;
    }

    /**
     * Show access denied page
     */
    private function showAccessDenied(): void
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
                <p class="lead">You do not have permission to manage content.</p>
                <a href="/admin" class="btn btn-primary">Return to Admin Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>';
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
