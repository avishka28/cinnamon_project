<?php
/**
 * PSR-4 Style Autoloader
 * Automatically loads classes from models, controllers, and includes directories
 */

declare(strict_types=1);

spl_autoload_register(function (string $class): void {
    // Define class to directory mappings
    $directories = [
        'models',
        'controllers',
        'controllers/admin',
        'includes',
        'config'
    ];

    $basePath = dirname(__DIR__);

    foreach ($directories as $dir) {
        $file = $basePath . '/' . $dir . '/' . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Load core configuration
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';

// Load translation helper functions
require_once dirname(__DIR__) . '/includes/TranslationHelper.php';

// Load CSRF helper functions
require_once dirname(__DIR__) . '/includes/CsrfHelper.php';

// Load sanitization helper functions
require_once dirname(__DIR__) . '/includes/SanitizationHelper.php';

// Initialize language manager
// Requirements: 9.1, 9.2, 9.4 - Multi-language support
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$sessionManager = new SessionManager();
$languageManager = new LanguageManager($sessionManager);
initializeLanguageManager($languageManager);
