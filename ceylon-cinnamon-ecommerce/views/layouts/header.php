<!DOCTYPE html>
<html lang="<?= current_language() ?>">
<head>
    <meta charset="UTF-8">
    <!-- Mobile-first viewport (Requirement 11.5) -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <meta name="theme-color" content="#8B4513">
    <title><?= htmlspecialchars($title ?? APP_NAME) ?></title>
    
    <?php
    // SEO Meta Tags (Requirement 11.1)
    if (isset($seo) && $seo instanceof SeoHelper): ?>
    <?= $seo->generateMetaTags() ?>
    <?php else: ?>
    <meta name="description" content="<?= htmlspecialchars($metaDescription ?? 'Premium Ceylon cinnamon products from Sri Lanka') ?>">
    <meta property="og:site_name" content="<?= APP_NAME ?>">
    <meta property="og:title" content="<?= htmlspecialchars($title ?? APP_NAME) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($metaDescription ?? 'Premium Ceylon cinnamon products from Sri Lanka') ?>">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    <?php endif; ?>
    
    <?php // Performance: Preconnect to external resources (Requirement 11.6) ?>
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <?php if (class_exists('AssetHelper') && AssetHelper::isCdnEnabled()): ?>
    <?= AssetHelper::preconnect(AssetHelper::getCdnUrl()) ?>
    <?php endif; ?>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Open+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <!-- Custom Styles -->
    <link rel="stylesheet" href="<?= class_exists('AssetHelper') ? AssetHelper::css('style.css') : url('/assets/css/style.css') ?>">
    
    <?php
    // JSON-LD Structured Data (Requirement 11.2)
    if (isset($seo) && $seo instanceof SeoHelper && $seo->hasStructuredData()): ?>
    <?= $seo->generateStructuredData() ?>
    <?php endif; ?>
</head>
<body class="d-flex flex-column min-vh-100">
    <!-- Skip to main content for accessibility -->
    <a href="#main-content" class="visually-hidden-focusable skip-link">Skip to main content</a>
    
    <!-- Top Bar (hidden on mobile) -->
    <div class="top-bar bg-dark text-light py-2 d-none d-md-block">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <small>
                        <i class="bi bi-envelope me-2"></i>info@ceyloncinnamon.com
                        <span class="mx-3">|</span>
                        <i class="bi bi-telephone me-2"></i>+94 11 234 5678
                    </small>
                </div>
                <div class="col-md-6 text-end">
                    <small>
                        <i class="bi bi-truck me-2"></i><?= t('common.free_shipping', ['amount' => '$50']) ?? 'Free shipping on orders over $50' ?>
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container">
            <!-- Brand Logo -->
            <a class="navbar-brand d-flex align-items-center" href="<?= url('/') ?>">
                <span class="brand-icon me-2">🌿</span>
                <span class="brand-text"><?= APP_NAME ?></span>
            </a>
            
            <!-- Mobile Cart & Toggle -->
            <div class="d-flex d-lg-none align-items-center">
                <a href="<?= url('/cart') ?>" class="btn btn-link position-relative me-2 p-2">
                    <i class="bi bi-cart3 fs-5"></i>
                    <span class="cart-count position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary" id="mobile-cart-count">
                        <?= isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0 ?>
                    </span>
                </a>
                <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>
            
            <!-- Navigation Links -->
            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link <?= ($_SERVER['REQUEST_URI'] ?? '') === '/' ? 'active' : '' ?>" href="<?= url('/') ?>">
                            <?= t('nav.home') ?>
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?= strpos($_SERVER['REQUEST_URI'] ?? '', '/products') !== false ? 'active' : '' ?>" href="<?= url('/products') ?>" id="productsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?= t('nav.products') ?>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="productsDropdown">
                            <li><a class="dropdown-item" href="<?= url('/products') ?>"><?= t('products.all_products') ?? 'All Products' ?></a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= url('/products?category=sticks') ?>"><?= t('products.category.sticks') ?? 'Cinnamon Sticks' ?></a></li>
                            <li><a class="dropdown-item" href="<?= url('/products?category=powder') ?>"><?= t('products.category.powder') ?? 'Cinnamon Powder' ?></a></li>
                            <li><a class="dropdown-item" href="<?= url('/products?category=oil') ?>"><?= t('products.category.oil') ?? 'Cinnamon Oil' ?></a></li>
                            <li><a class="dropdown-item" href="<?= url('/products?category=tea') ?>"><?= t('products.category.tea') ?? 'Cinnamon Tea' ?></a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'] ?? '', '/wholesale') !== false ? 'active' : '' ?>" href="<?= url('/wholesale') ?>">
                            <?= t('nav.wholesale') ?? 'Wholesale' ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'] ?? '', '/blog') !== false ? 'active' : '' ?>" href="<?= url('/blog') ?>">
                            <?= t('nav.blog') ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'] ?? '', '/about') !== false ? 'active' : '' ?>" href="<?= url('/about') ?>">
                            <?= t('nav.about') ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'] ?? '', '/contact') !== false ? 'active' : '' ?>" href="<?= url('/contact') ?>">
                            <?= t('nav.contact') ?>
                        </a>
                    </li>
                </ul>
                
                <!-- Right Side Navigation -->
                <ul class="navbar-nav align-items-lg-center">
                    <!-- Search (Desktop) -->
                    <li class="nav-item d-none d-lg-block me-2">
                        <form action="<?= url('/products') ?>" method="GET" class="d-flex">
                            <div class="input-group input-group-sm">
                                <input type="search" name="search" class="form-control" placeholder="<?= t('action.search') ?>" aria-label="Search">
                                <button class="btn btn-outline-primary" type="submit">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </form>
                    </li>
                    
                    <!-- Cart (Desktop) -->
                    <li class="nav-item d-none d-lg-block">
                        <a href="<?= url('/cart') ?>" class="nav-link position-relative">
                            <i class="bi bi-cart3 fs-5"></i>
                            <span class="cart-count position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary" id="desktop-cart-count">
                                <?= isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0 ?>
                            </span>
                        </a>
                    </li>
                    
                    <!-- User Account -->
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle me-1"></i>
                                <span class="d-lg-none d-xl-inline"><?= htmlspecialchars($_SESSION['user_name'] ?? t('nav.account')) ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="<?= url('/dashboard') ?>"><i class="bi bi-speedometer2 me-2"></i><?= t('nav.dashboard') ?></a></li>
                                <li><a class="dropdown-item" href="<?= url('/dashboard/orders') ?>"><i class="bi bi-box-seam me-2"></i><?= t('nav.orders') ?? 'My Orders' ?></a></li>
                                <li><a class="dropdown-item" href="<?= url('/dashboard/profile') ?>"><i class="bi bi-person me-2"></i><?= t('nav.profile') ?? 'Profile' ?></a></li>
                                <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'content_manager'])): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="<?= url('/admin') ?>"><i class="bi bi-gear me-2"></i><?= t('nav.admin') ?></a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?= url('/logout') ?>"><i class="bi bi-box-arrow-right me-2"></i><?= t('nav.logout') ?></a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= url('/login') ?>">
                                <i class="bi bi-person me-1"></i><?= t('nav.login') ?>
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <!-- Language Switcher -->
                    <li class="nav-item ms-lg-2">
                        <?php include __DIR__ . '/../components/language_switcher.php'; ?>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Mobile Search Bar -->
    <div class="mobile-search bg-light py-2 d-lg-none">
        <div class="container">
            <form action="<?= url('/products') ?>" method="GET">
                <div class="input-group">
                    <input type="search" name="search" class="form-control" placeholder="<?= t('action.search') ?> products..." aria-label="Search">
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Main Content -->
    <main id="main-content" class="flex-grow-1">
