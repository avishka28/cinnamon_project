<?php
/**
 * PHPUnit Bootstrap File
 */

declare(strict_types=1);

// Set test environment
$_ENV['APP_ENV'] = 'testing';
$_ENV['APP_DEBUG'] = 'true';

// Load autoloader
require_once __DIR__ . '/../includes/autoload.php';

// Load Composer autoloader if available
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}
