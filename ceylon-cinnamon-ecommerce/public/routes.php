<?php
/**
 * Application Routes
 * Define all application routes here
 */

declare(strict_types=1);

// Public routes
$router->get('/', [HomeController::class, 'index']);
$router->get('/products', [ProductController::class, 'index']);
$router->get('/products/{slug}', [ProductController::class, 'show']);
$router->get('/category/{slug}', [ProductController::class, 'category']);

// Authentication routes
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);
$router->get('/logout', [AuthController::class, 'logout']);

// Cart routes
$router->get('/cart', [CartController::class, 'index']);
$router->post('/cart/add', [CartController::class, 'add']);
$router->post('/cart/update', [CartController::class, 'update']);
$router->post('/cart/remove', [CartController::class, 'remove']);
$router->post('/cart/clear', [CartController::class, 'clear']);
$router->get('/cart/count', [CartController::class, 'getCount']);
$router->get('/cart/data', [CartController::class, 'getCart']);

// Checkout routes
$router->get('/checkout', [CheckoutController::class, 'index']);
$router->post('/checkout', [CheckoutController::class, 'process']);
$router->get('/checkout/success', [CheckoutController::class, 'success']);

// Order tracking
$router->get('/order/track', [OrderController::class, 'showTrack']);
$router->post('/order/track', [OrderController::class, 'track']);

// Customer dashboard
$router->get('/dashboard', [DashboardController::class, 'index'], [AuthMiddleware::class]);
$router->get('/dashboard/orders', [DashboardController::class, 'orders'], [AuthMiddleware::class]);
$router->get('/dashboard/orders/{id}', [DashboardController::class, 'orderDetail'], [AuthMiddleware::class]);

// API routes
$router->get('/api/products', [ApiProductController::class, 'index']);
$router->get('/api/products/{id}', [ApiProductController::class, 'show']);
$router->get('/api/categories', [ApiCategoryController::class, 'index']);
$router->post('/api/cart/add', [ApiCartController::class, 'add']);
$router->get('/api/cart', [ApiCartController::class, 'index']);
$router->post('/api/cart/update', [ApiCartController::class, 'update']);
$router->delete('/api/cart/{id}', [ApiCartController::class, 'remove']);

// Admin routes
$router->get('/admin', [AdminController::class, 'index'], [AdminMiddleware::class]);
$router->get('/admin/login', [AdminController::class, 'showLogin']);
$router->post('/admin/login', [AdminController::class, 'login']);

// Admin product management
$router->get('/admin/products', [ProductAdminController::class, 'index'], [AdminMiddleware::class]);
$router->get('/admin/products/create', [ProductAdminController::class, 'create'], [AdminMiddleware::class]);
$router->post('/admin/products', [ProductAdminController::class, 'store'], [AdminMiddleware::class]);
$router->get('/admin/products/{id}/edit', [ProductAdminController::class, 'edit'], [AdminMiddleware::class]);
$router->post('/admin/products/{id}', [ProductAdminController::class, 'update'], [AdminMiddleware::class]);
$router->delete('/admin/products/{id}', [ProductAdminController::class, 'destroy'], [AdminMiddleware::class]);

// Admin order management
$router->get('/admin/orders', [OrderAdminController::class, 'index'], [AdminMiddleware::class]);
$router->get('/admin/orders/{id}', [OrderAdminController::class, 'show'], [AdminMiddleware::class]);
$router->post('/admin/orders/{id}/status', [OrderAdminController::class, 'updateStatus'], [AdminMiddleware::class]);

// Content pages
$router->get('/about', [PageController::class, 'about']);
$router->get('/contact', [PageController::class, 'contact']);
$router->post('/contact', [PageController::class, 'submitContact']);
$router->get('/wholesale', [PageController::class, 'wholesale']);
$router->post('/wholesale', [PageController::class, 'submitWholesale']);
