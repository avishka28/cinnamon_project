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
