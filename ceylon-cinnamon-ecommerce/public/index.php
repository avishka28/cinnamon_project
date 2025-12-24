<?php
/**
 * Application Entry Point
 * All requests are routed through this file
 */

declare(strict_types=1);

// Load autoloader and configuration
require_once __DIR__ . '/../includes/autoload.php';

// Initialize router
$router = new Router();

// Define routes
require_once __DIR__ . '/routes.php';

// Get request URI and method
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Dispatch the request
$router->dispatch($uri, $method);
