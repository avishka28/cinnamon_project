<?php
/**
 * Application Configuration
 * Requirements: 10.5 (HTTPS enforcement and secure configuration)
 * 
 * This file loads environment variables and defines application constants.
 * All sensitive configuration should be in the .env file, not here.
 */

declare(strict_types=1);

// Load environment variables
require_once __DIR__ . '/env.php';
Env::load(__DIR__ . '/../.env');

// ============================================================================
// APPLICATION SETTINGS
// ============================================================================
define('APP_NAME', $_ENV['APP_NAME'] ?? 'Ceylon Cinnamon');
define('APP_URL', $_ENV['APP_URL'] ?? 'http://localhost');
define('APP_ENV', $_ENV['APP_ENV'] ?? 'development');
define('APP_DEBUG', filter_var($_ENV['APP_DEBUG'] ?? true, FILTER_VALIDATE_BOOLEAN));
define('APP_LOCALE', $_ENV['APP_LOCALE'] ?? 'en');

// ============================================================================
// PATH CONSTANTS
// ============================================================================
define('ROOT_PATH', dirname(__DIR__));
define('CONFIG_PATH', ROOT_PATH . '/config');
define('CONTROLLERS_PATH', ROOT_PATH . '/controllers');
define('MODELS_PATH', ROOT_PATH . '/models');
define('VIEWS_PATH', ROOT_PATH . '/views');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('UPLOADS_PATH', PUBLIC_PATH . '/uploads');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('LANG_PATH', ROOT_PATH . '/lang');
define('LOG_PATH', ROOT_PATH . '/' . ($_ENV['LOG_PATH'] ?? 'logs'));
define('CACHE_PATH', ROOT_PATH . '/' . ($_ENV['CACHE_PATH'] ?? 'cache'));

// ============================================================================
// SESSION CONFIGURATION
// ============================================================================
define('SESSION_LIFETIME', (int)($_ENV['SESSION_LIFETIME'] ?? 3600));
define('SESSION_NAME', $_ENV['SESSION_NAME'] ?? 'ceylon_session');
define('SESSION_SECURE', filter_var($_ENV['SESSION_SECURE'] ?? false, FILTER_VALIDATE_BOOLEAN));
define('SESSION_HTTPONLY', filter_var($_ENV['SESSION_HTTPONLY'] ?? true, FILTER_VALIDATE_BOOLEAN));
define('SESSION_SAMESITE', $_ENV['SESSION_SAMESITE'] ?? 'Strict');

// ============================================================================
// SECURITY SETTINGS
// ============================================================================
define('CSRF_TOKEN_NAME', 'csrf_token');
define('CSRF_TOKEN_LIFETIME', (int)($_ENV['CSRF_TOKEN_LIFETIME'] ?? 3600));
define('PASSWORD_ALGO', PASSWORD_BCRYPT);
define('PASSWORD_OPTIONS', ['cost' => 12]);
define('FORCE_HTTPS', filter_var($_ENV['FORCE_HTTPS'] ?? false, FILTER_VALIDATE_BOOLEAN));
define('ALLOWED_HOSTS', array_filter(array_map('trim', explode(',', $_ENV['ALLOWED_HOSTS'] ?? ''))));

// ============================================================================
// FILE UPLOAD SETTINGS
// ============================================================================
define('MAX_UPLOAD_SIZE', (int)($_ENV['MAX_UPLOAD_SIZE'] ?? 10485760)); // 10MB
define('UPLOAD_PATH', $_ENV['UPLOAD_PATH'] ?? 'uploads');
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_VIDEO_TYPES', ['video/mp4', 'video/webm']);
define('ALLOWED_DOC_TYPES', ['application/pdf']);

// ============================================================================
// PAGINATION
// ============================================================================
define('ITEMS_PER_PAGE', (int)($_ENV['ITEMS_PER_PAGE'] ?? 20));

// ============================================================================
// CDN CONFIGURATION
// ============================================================================
define('CDN_ENABLED', filter_var($_ENV['CDN_ENABLED'] ?? false, FILTER_VALIDATE_BOOLEAN));
define('CDN_URL', $_ENV['CDN_URL'] ?? '');
define('CDN_TYPE', $_ENV['CDN_TYPE'] ?? 'generic');
define('CDN_SUPPORTS_RESIZE', filter_var($_ENV['CDN_SUPPORTS_RESIZE'] ?? false, FILTER_VALIDATE_BOOLEAN));
define('CDN_AUTO_WEBP', filter_var($_ENV['CDN_AUTO_WEBP'] ?? false, FILTER_VALIDATE_BOOLEAN));

// ============================================================================
// PAYMENT GATEWAYS
// ============================================================================
// Stripe Configuration (Requirements: 4.1)
define('STRIPE_PUBLIC_KEY', $_ENV['STRIPE_PUBLIC_KEY'] ?? '');
define('STRIPE_SECRET_KEY', $_ENV['STRIPE_SECRET_KEY'] ?? '');
define('STRIPE_WEBHOOK_SECRET', $_ENV['STRIPE_WEBHOOK_SECRET'] ?? '');
define('STRIPE_CURRENCY', $_ENV['STRIPE_CURRENCY'] ?? 'usd');

// PayPal Configuration (Requirements: 4.2)
define('PAYPAL_CLIENT_ID', $_ENV['PAYPAL_CLIENT_ID'] ?? '');
define('PAYPAL_SECRET', $_ENV['PAYPAL_SECRET'] ?? '');
define('PAYPAL_MODE', $_ENV['PAYPAL_MODE'] ?? 'sandbox');
define('PAYPAL_CURRENCY', $_ENV['PAYPAL_CURRENCY'] ?? 'USD');

// Bank Transfer Configuration (Requirements: 4.3)
define('BANK_NAME', $_ENV['BANK_NAME'] ?? 'Bank of Ceylon');
define('BANK_ACCOUNT_NAME', $_ENV['BANK_ACCOUNT_NAME'] ?? 'Ceylon Cinnamon Exports Ltd');
define('BANK_ACCOUNT_NUMBER', $_ENV['BANK_ACCOUNT_NUMBER'] ?? '');
define('BANK_SWIFT_CODE', $_ENV['BANK_SWIFT_CODE'] ?? '');
define('BANK_BRANCH', $_ENV['BANK_BRANCH'] ?? '');

// ============================================================================
// EMAIL CONFIGURATION
// ============================================================================
define('SMTP_HOST', $_ENV['SMTP_HOST'] ?? 'localhost');
define('SMTP_PORT', (int)($_ENV['SMTP_PORT'] ?? 587));
define('SMTP_USER', $_ENV['SMTP_USER'] ?? '');
define('SMTP_PASS', $_ENV['SMTP_PASS'] ?? '');
define('SMTP_FROM_EMAIL', $_ENV['SMTP_FROM_EMAIL'] ?? 'noreply@ceyloncinnamon.com');
define('SMTP_FROM_NAME', $_ENV['SMTP_FROM_NAME'] ?? 'Ceylon Cinnamon');
define('SMTP_ENCRYPTION', $_ENV['SMTP_ENCRYPTION'] ?? 'tls');
define('SMTP_DEBUG', (int)($_ENV['SMTP_DEBUG'] ?? 0));

// Admin Notifications
define('ADMIN_EMAIL', $_ENV['ADMIN_EMAIL'] ?? 'admin@ceyloncinnamon.com');
define('ADMIN_NOTIFICATION_ENABLED', filter_var($_ENV['ADMIN_NOTIFICATION_ENABLED'] ?? true, FILTER_VALIDATE_BOOLEAN));

// ============================================================================
// LOGGING CONFIGURATION
// ============================================================================
define('LOG_LEVEL', $_ENV['LOG_LEVEL'] ?? 'debug');
define('LOG_MAX_FILES', (int)($_ENV['LOG_MAX_FILES'] ?? 30));

// ============================================================================
// CACHE CONFIGURATION
// ============================================================================
define('CACHE_ENABLED', filter_var($_ENV['CACHE_ENABLED'] ?? false, FILTER_VALIDATE_BOOLEAN));
define('CACHE_DRIVER', $_ENV['CACHE_DRIVER'] ?? 'file');
define('CACHE_TTL', (int)($_ENV['CACHE_TTL'] ?? 3600));

// ============================================================================
// ANALYTICS
// ============================================================================
define('GA_TRACKING_ID', $_ENV['GA_TRACKING_ID'] ?? '');

// ============================================================================
// SOCIAL MEDIA
// ============================================================================
define('SOCIAL_FACEBOOK', $_ENV['SOCIAL_FACEBOOK'] ?? '');
define('SOCIAL_INSTAGRAM', $_ENV['SOCIAL_INSTAGRAM'] ?? '');
define('SOCIAL_TWITTER', $_ENV['SOCIAL_TWITTER'] ?? '');
define('SOCIAL_YOUTUBE', $_ENV['SOCIAL_YOUTUBE'] ?? '');
define('SOCIAL_LINKEDIN', $_ENV['SOCIAL_LINKEDIN'] ?? '');

// ============================================================================
// COMPANY INFORMATION
// ============================================================================
define('COMPANY_NAME', $_ENV['COMPANY_NAME'] ?? 'Ceylon Cinnamon Exports Ltd');
define('COMPANY_ADDRESS', $_ENV['COMPANY_ADDRESS'] ?? '123 Cinnamon Gardens, Colombo 07, Sri Lanka');
define('COMPANY_PHONE', $_ENV['COMPANY_PHONE'] ?? '+94 11 234 5678');
define('COMPANY_EMAIL', $_ENV['COMPANY_EMAIL'] ?? 'info@ceyloncinnamon.com');
define('COMPANY_VAT_NUMBER', $_ENV['COMPANY_VAT_NUMBER'] ?? '');

// ============================================================================
// BASE URL PATH CALCULATION
// ============================================================================
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$basePath = dirname($scriptName);

// Normalize path separators and ensure we get the correct base path
$basePath = str_replace('\\', '/', $basePath);

// For local development, if we detect we're in a subdirectory structure,
// use a fixed base path to avoid issues with varying directory structures
if (APP_ENV === 'development' && strpos($basePath, '/ceylon-cinnamon-ecommerce/public') !== false) {
    $basePath = '/Cinnamon/cinnamon_project/ceylon-cinnamon-ecommerce/public';
}

define('BASE_PATH', ($basePath !== '/' && $basePath !== '') ? $basePath : '');

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

/**
 * Generate full URL including domain
 * @param string $path The path to append
 * @return string Full URL with domain
 */
function fullUrl(string $path = ''): string
{
    return rtrim(APP_URL, '/') . url($path);
}

/**
 * Get asset URL (with CDN support)
 * @param string $path Asset path relative to public/assets
 * @return string Asset URL
 */
function asset(string $path): string
{
    $path = ltrim($path, '/');
    
    if (CDN_ENABLED && CDN_URL) {
        return rtrim(CDN_URL, '/') . '/assets/' . $path;
    }
    
    return url('assets/' . $path);
}

/**
 * Get upload URL
 * @param string $path Upload path relative to public/uploads
 * @return string Upload URL
 */
function uploadUrl(string $path): string
{
    $path = ltrim($path, '/');
    
    if (CDN_ENABLED && CDN_URL) {
        return rtrim(CDN_URL, '/') . '/uploads/' . $path;
    }
    
    return url('uploads/' . $path);
}

// ============================================================================
// ERROR HANDLING
// ============================================================================
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', LOG_PATH . '/php_errors.log');
}

// ============================================================================
// TIMEZONE
// ============================================================================
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'Asia/Colombo');

// ============================================================================
// HTTPS ENFORCEMENT
// ============================================================================
if (FORCE_HTTPS && !empty($_SERVER['HTTP_HOST'])) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        || (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    
    if (!$isHttps) {
        $redirectUrl = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        header('Location: ' . $redirectUrl, true, 301);
        exit;
    }
}
