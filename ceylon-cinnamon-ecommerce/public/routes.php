<?php
/**
 * Application Routes
 * Define all application routes here
 */

declare(strict_types=1);

// SEO routes (Requirement 11.3)
$router->get('/sitemap.xml', [SeoController::class, 'sitemap']);
$router->get('/robots.txt', [SeoController::class, 'robots']);

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
$router->post('/checkout/paypal/create', [CheckoutController::class, 'createPayPalOrder']);
$router->get('/checkout/shipping-methods', [CheckoutController::class, 'getShippingMethods']);
$router->post('/checkout/calculate-shipping', [CheckoutController::class, 'calculateShipping']);

// Order tracking
$router->get('/order/track', [OrderController::class, 'showTrack']);
$router->post('/order/track', [OrderController::class, 'track']);

// Customer dashboard
$router->get('/dashboard', [DashboardController::class, 'index'], [AuthMiddleware::class]);
$router->get('/dashboard/orders', [DashboardController::class, 'orders'], [AuthMiddleware::class]);
$router->get('/dashboard/orders/{id}', [DashboardController::class, 'orderDetail'], [AuthMiddleware::class]);
$router->get('/dashboard/profile', [DashboardController::class, 'profile'], [AuthMiddleware::class]);
$router->post('/dashboard/profile', [DashboardController::class, 'updateProfile'], [AuthMiddleware::class]);
$router->post('/dashboard/password', [DashboardController::class, 'changePassword'], [AuthMiddleware::class]);
$router->get('/dashboard/addresses', [DashboardController::class, 'addresses'], [AuthMiddleware::class]);

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
$router->get('/admin/logout', [AdminController::class, 'logout']);
$router->get('/admin/profile', [AdminController::class, 'profile'], [AdminMiddleware::class]);
$router->post('/admin/profile', [AdminController::class, 'updateProfile'], [AdminMiddleware::class]);

// Admin product management
$router->get('/admin/products', [ProductAdminController::class, 'index'], [AdminMiddleware::class]);
$router->get('/admin/products/create', [ProductAdminController::class, 'create'], [AdminMiddleware::class]);
$router->get('/admin/products/import', [ProductAdminController::class, 'showImport'], [AdminMiddleware::class]);
$router->post('/admin/products/import', [ProductAdminController::class, 'import'], [AdminMiddleware::class]);
$router->get('/admin/products/template', [ProductAdminController::class, 'downloadTemplate'], [AdminMiddleware::class]);
$router->post('/admin/products', [ProductAdminController::class, 'store'], [AdminMiddleware::class]);
$router->get('/admin/products/{id}/edit', [ProductAdminController::class, 'edit'], [AdminMiddleware::class]);
$router->post('/admin/products/{id}', [ProductAdminController::class, 'update'], [AdminMiddleware::class]);
$router->delete('/admin/products/{id}', [ProductAdminController::class, 'destroy'], [AdminMiddleware::class]);

// Admin category management (Requirements: 6.5)
$router->get('/admin/categories', [CategoryAdminController::class, 'index'], [AdminMiddleware::class]);
$router->get('/admin/categories/create', [CategoryAdminController::class, 'create'], [AdminMiddleware::class]);
$router->post('/admin/categories', [CategoryAdminController::class, 'store'], [AdminMiddleware::class]);
$router->get('/admin/categories/{id}/edit', [CategoryAdminController::class, 'edit'], [AdminMiddleware::class]);
$router->post('/admin/categories/{id}', [CategoryAdminController::class, 'update'], [AdminMiddleware::class]);
$router->delete('/admin/categories/{id}', [CategoryAdminController::class, 'destroy'], [AdminMiddleware::class]);

// Admin order management
$router->get('/admin/orders', [OrderAdminController::class, 'index'], [AdminMiddleware::class]);
$router->get('/admin/orders/{id}', [OrderAdminController::class, 'show'], [AdminMiddleware::class]);
$router->post('/admin/orders/{id}/status', [OrderAdminController::class, 'updateStatus'], [AdminMiddleware::class]);
$router->post('/admin/orders/{id}/note', [OrderAdminController::class, 'addNote'], [AdminMiddleware::class]);
$router->get('/admin/orders/{id}/invoice', [OrderAdminController::class, 'invoice'], [AdminMiddleware::class]);

// Admin shipping management (Requirements: 14.1, 14.5)
$router->get('/admin/shipping', [ShippingAdminController::class, 'index'], [AdminMiddleware::class]);
$router->get('/admin/shipping/zones/create', [ShippingAdminController::class, 'createZone'], [AdminMiddleware::class]);
$router->post('/admin/shipping/zones', [ShippingAdminController::class, 'storeZone'], [AdminMiddleware::class]);
$router->get('/admin/shipping/zones/{id}/edit', [ShippingAdminController::class, 'editZone'], [AdminMiddleware::class]);
$router->post('/admin/shipping/zones/{id}', [ShippingAdminController::class, 'updateZone'], [AdminMiddleware::class]);
$router->delete('/admin/shipping/zones/{id}', [ShippingAdminController::class, 'destroyZone'], [AdminMiddleware::class]);
$router->get('/admin/shipping/zones/{id}/methods/create', [ShippingAdminController::class, 'createMethod'], [AdminMiddleware::class]);
$router->post('/admin/shipping/zones/{id}/methods', [ShippingAdminController::class, 'storeMethod'], [AdminMiddleware::class]);
$router->get('/admin/shipping/methods/{id}/edit', [ShippingAdminController::class, 'editMethod'], [AdminMiddleware::class]);
$router->post('/admin/shipping/methods/{id}', [ShippingAdminController::class, 'updateMethod'], [AdminMiddleware::class]);
$router->delete('/admin/shipping/methods/{id}', [ShippingAdminController::class, 'destroyMethod'], [AdminMiddleware::class]);

// Admin content management - Blog posts
$router->get('/admin/content/posts', [ContentAdminController::class, 'index'], [ContentManagerMiddleware::class]);
$router->get('/admin/content/posts/create', [ContentAdminController::class, 'create'], [ContentManagerMiddleware::class]);
$router->post('/admin/content/posts', [ContentAdminController::class, 'store'], [ContentManagerMiddleware::class]);
$router->get('/admin/content/posts/{id}/edit', [ContentAdminController::class, 'edit'], [ContentManagerMiddleware::class]);
$router->post('/admin/content/posts/{id}', [ContentAdminController::class, 'update'], [ContentManagerMiddleware::class]);
$router->delete('/admin/content/posts/{id}', [ContentAdminController::class, 'destroy'], [ContentManagerMiddleware::class]);

// Admin content management - Blog categories
$router->get('/admin/content/categories', [ContentAdminController::class, 'categories'], [ContentManagerMiddleware::class]);
$router->get('/admin/content/categories/create', [ContentAdminController::class, 'createCategory'], [ContentManagerMiddleware::class]);
$router->post('/admin/content/categories', [ContentAdminController::class, 'storeCategory'], [ContentManagerMiddleware::class]);
$router->get('/admin/content/categories/{id}/edit', [ContentAdminController::class, 'editCategory'], [ContentManagerMiddleware::class]);
$router->post('/admin/content/categories/{id}', [ContentAdminController::class, 'updateCategory'], [ContentManagerMiddleware::class]);
$router->delete('/admin/content/categories/{id}', [ContentAdminController::class, 'destroyCategory'], [ContentManagerMiddleware::class]);

// Admin content management - Certificates
$router->get('/admin/content/certificates', [ContentAdminController::class, 'certificates'], [ContentManagerMiddleware::class]);
$router->get('/admin/content/certificates/create', [ContentAdminController::class, 'createCertificate'], [ContentManagerMiddleware::class]);
$router->post('/admin/content/certificates', [ContentAdminController::class, 'storeCertificate'], [ContentManagerMiddleware::class]);
$router->get('/admin/content/certificates/{id}/edit', [ContentAdminController::class, 'editCertificate'], [ContentManagerMiddleware::class]);
$router->post('/admin/content/certificates/{id}', [ContentAdminController::class, 'updateCertificate'], [ContentManagerMiddleware::class]);
$router->delete('/admin/content/certificates/{id}', [ContentAdminController::class, 'destroyCertificate'], [ContentManagerMiddleware::class]);

// Admin content management - Gallery
$router->get('/admin/content/gallery', [ContentAdminController::class, 'gallery'], [ContentManagerMiddleware::class]);
$router->get('/admin/content/gallery/create', [ContentAdminController::class, 'createGalleryItem'], [ContentManagerMiddleware::class]);
$router->post('/admin/content/gallery', [ContentAdminController::class, 'storeGalleryItem'], [ContentManagerMiddleware::class]);
$router->get('/admin/content/gallery/{id}/edit', [ContentAdminController::class, 'editGalleryItem'], [ContentManagerMiddleware::class]);
$router->post('/admin/content/gallery/{id}', [ContentAdminController::class, 'updateGalleryItem'], [ContentManagerMiddleware::class]);
$router->delete('/admin/content/gallery/{id}', [ContentAdminController::class, 'destroyGalleryItem'], [ContentManagerMiddleware::class]);

// Content pages
$router->get('/about', [HomeController::class, 'about']);
$router->get('/contact', [HomeController::class, 'contact']);
$router->post('/contact', [HomeController::class, 'contactSubmit']);
$router->get('/privacy', [HomeController::class, 'privacy']);
$router->get('/terms', [HomeController::class, 'terms']);
$router->get('/shipping', [ShippingController::class, 'index']);

// Shipping API (Requirements: 14.1, 14.4)
$router->get('/api/shipping/rates', [ShippingController::class, 'getRates']);
$router->get('/api/shipping/delivery-estimate', [ShippingController::class, 'getDeliveryEstimate']);
// Wholesale routes (Requirements: 13.1, 13.2, 13.3)
$router->get('/wholesale', [WholesaleController::class, 'index']);
$router->post('/wholesale', [WholesaleController::class, 'submitInquiry']);
$router->get('/api/wholesale/pricing/{id}', [WholesaleController::class, 'getProductPricing']);

// Public blog routes
$router->get('/blog', [BlogController::class, 'index']);
$router->get('/blog/category/{slug}', [BlogController::class, 'category']);
$router->get('/blog/{slug}', [BlogController::class, 'show']);

// Public content pages
$router->get('/certificates', [ContentController::class, 'certificates']);
$router->get('/gallery', [ContentController::class, 'gallery']);
