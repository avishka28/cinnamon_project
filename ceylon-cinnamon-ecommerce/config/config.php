<?php
/**
 * Application Configuration
 * Requirements: 10.5
 */

declare(strict_types=1);

// Load environment variables
require_once __DIR__ . '/env.php';
Env::load(__DIR__ . '/../.env');

// Application settings
define('APP_NAME', $_ENV['APP_NAME'] ?? 'Ceylon Cinnamon');
define('APP_URL', $_ENV['APP_URL'] ?? 'http://localhost');
define('APP_ENV', $_ENV['APP_ENV'] ?? 'development');
define('APP_DEBUG', filter_var($_ENV['APP_DEBUG'] ?? true, FILTER_VALIDATE_BOOLEAN));

// Path constants
define('ROOT_PATH', dirname(__DIR__));
define('CONFIG_PATH', ROOT_PATH . '/config');
define('CONTROLLERS_PATH', ROOT_PATH . '/controllers');
define('MODELS_PATH', ROOT_PATH . '/models');
define('VIEWS_PATH', ROOT_PATH . '/views');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('UPLOADS_PATH', PUBLIC_PATH . '/uploads');
define('INCLUDES_PATH', ROOT_PATH . '/includes');

// Session configuration
define('SESSION_LIFETIME', (int)($_ENV['SESSION_LIFETIME'] ?? 3600));
define('SESSION_NAME', $_ENV['SESSION_NAME'] ?? 'ceylon_session');

// Security settings
define('CSRF_TOKEN_NAME', 'csrf_token');
define('PASSWORD_ALGO', PASSWORD_BCRYPT);
define('PASSWORD_OPTIONS', ['cost' => 12]);

// File upload settings
define('MAX_UPLOAD_SIZE', (int)($_ENV['MAX_UPLOAD_SIZE'] ?? 10485760)); // 10MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_VIDEO_TYPES', ['video/mp4', 'video/webm']);
define('ALLOWED_DOC_TYPES', ['application/pdf']);

// Pagination
define('ITEMS_PER_PAGE', (int)($_ENV['ITEMS_PER_PAGE'] ?? 20));

// Base URL path (for subdirectory installations)
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$basePath = dirname($scriptName);
define('BASE_PATH', ($basePath !== '/' && $basePath !== '\\') ? $basePath : '');

/**
 * Generate URL with base path
 * @param string $path The path to append
 * @return string Full URL path
 */
function url(string $path = ''): string
{
    $path = '/' . ltrim($path, '/');
    return BASE_PATH . $path;
}

// Payment gateways
define('STRIPE_PUBLIC_KEY', $_ENV['STRIPE_PUBLIC_KEY'] ?? '');
define('STRIPE_SECRET_KEY', $_ENV['STRIPE_SECRET_KEY'] ?? '');
define('PAYPAL_CLIENT_ID', $_ENV['PAYPAL_CLIENT_ID'] ?? '');
define('PAYPAL_SECRET', $_ENV['PAYPAL_SECRET'] ?? '');
define('PAYPAL_MODE', $_ENV['PAYPAL_MODE'] ?? 'sandbox');

// Email configuration
define('SMTP_HOST', $_ENV['SMTP_HOST'] ?? 'localhost');
define('SMTP_PORT', (int)($_ENV['SMTP_PORT'] ?? 587));
define('SMTP_USER', $_ENV['SMTP_USER'] ?? '');
define('SMTP_PASS', $_ENV['SMTP_PASS'] ?? '');
define('SMTP_FROM_EMAIL', $_ENV['SMTP_FROM_EMAIL'] ?? 'noreply@ceyloncinnamon.com');
define('SMTP_FROM_NAME', $_ENV['SMTP_FROM_NAME'] ?? 'Ceylon Cinnamon');

// Error handling
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Timezone
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'Asia/Colombo');
