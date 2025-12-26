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
    
    <!-- Base URL for JavaScript -->
    <script>
        window.APP_BASE_URL = '<?= BASE_PATH ?>';
    </script>
    
    <?php
    // Ensure session is started and CSRF token is available
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    ?>
    <!-- CSRF Token for AJAX requests -->
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    
    <!-- Modern Navbar Styles -->
    <style>
        /* Top Bar Enhancement */
        .top-bar {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            font-size: 0.85rem;
            letter-spacing: 0.3px;
        }
        .top-bar a {
            color: rgba(255,255,255,0.85);
            text-decoration: none;
            transition: color 0.2s ease;
        }
        .top-bar a:hover {
            color: #fff;
        }
        .top-bar .highlight {
            background: rgba(255,255,255,0.1);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
        }
        
        /* Main Navigation Enhancement */
        .main-navbar {
            background: #fff;
            box-shadow: 0 2px 20px rgba(0,0,0,0.08);
            padding: 0.75rem 0;
            transition: all 0.3s ease;
        }
        .main-navbar.scrolled {
            padding: 0.5rem 0;
            box-shadow: 0 4px 30px rgba(0,0,0,0.12);
        }
        
        /* Brand Styling */
        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .brand-logo {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, #8B4513 0%, #A0522D 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.25rem;
        }
        .brand-name {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            font-size: 1.35rem;
            color: #2C1810;
            line-height: 1.2;
        }
        .brand-tagline {
            font-size: 0.65rem;
            color: #8B4513;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }
        
        /* Nav Links */
        .main-navbar .nav-link {
            font-weight: 500;
            color: #495057 !important;
            padding: 0.5rem 1rem !important;
            position: relative;
            transition: color 0.2s ease;
        }
        .main-navbar .nav-link:hover,
        .main-navbar .nav-link.active {
            color: #8B4513 !important;
        }
        .main-navbar .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 1rem;
            right: 1rem;
            height: 2px;
            background: #8B4513;
            border-radius: 2px;
        }
        
        /* Dropdown Enhancement */
        .main-navbar .dropdown-menu {
            border: none;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            border-radius: 12px;
            padding: 0.75rem 0;
            min-width: 220px;
            animation: dropdownFade 0.2s ease;
        }
        @keyframes dropdownFade {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .main-navbar .dropdown-item {
            padding: 0.6rem 1.25rem;
            font-weight: 500;
            color: #495057;
            transition: all 0.2s ease;
        }
        .main-navbar .dropdown-item:hover {
            background: linear-gradient(135deg, #FFF8DC 0%, #fff 100%);
            color: #8B4513;
            padding-left: 1.5rem;
        }
        .main-navbar .dropdown-item i {
            width: 20px;
            margin-right: 0.5rem;
            color: #8B4513;
        }
        
        /* Right Side Icons */
        .nav-icons {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        .nav-icon-btn {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #495057;
            background: transparent;
            border: none;
            transition: all 0.2s ease;
            position: relative;
        }
        .nav-icon-btn:hover {
            background: #f8f9fa;
            color: #8B4513;
        }
        .nav-icon-btn i {
            font-size: 1.25rem;
        }
        .nav-icon-btn .badge {
            position: absolute;
            top: 2px;
            right: 2px;
            font-size: 0.65rem;
            min-width: 18px;
            height: 18px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #8B4513;
            border: 2px solid #fff;
        }
        
        /* User Dropdown */
        .user-dropdown-toggle {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 0.75rem !important;
            border-radius: 25px;
            background: #f8f9fa;
            transition: all 0.2s ease;
        }
        .user-dropdown-toggle:hover {
            background: #e9ecef;
        }
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, #8B4513 0%, #A0522D 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .user-name {
            font-weight: 500;
            color: #495057;
            max-width: 100px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        /* Language Switcher Enhancement */
        .lang-switcher {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            background: #f8f9fa;
            padding: 0.25rem;
            border-radius: 8px;
        }
        .lang-btn {
            padding: 0.35rem 0.6rem;
            border: none;
            background: transparent;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            color: #6c757d;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .lang-btn:hover {
            background: #e9ecef;
        }
        .lang-btn.active {
            background: #8B4513;
            color: #fff;
        }
        
        /* Mobile Enhancements */
        @media (max-width: 991.98px) {
            .main-navbar {
                padding: 0.5rem 0;
            }
            .brand-logo {
                width: 36px;
                height: 36px;
            }
            .brand-name {
                font-size: 1.15rem;
            }
            .brand-tagline {
                display: none;
            }
            .navbar-collapse {
                background: #fff;
                margin: 0.75rem -0.75rem -0.5rem;
                padding: 1rem;
                border-top: 1px solid #e9ecef;
                max-height: calc(100vh - 80px);
                overflow-y: auto;
            }
            .main-navbar .nav-link {
                padding: 0.75rem 0 !important;
                border-bottom: 1px solid #f1f1f1;
            }
            .main-navbar .nav-link.active::after {
                display: none;
            }
            .nav-icons {
                margin-top: 1rem;
                padding-top: 1rem;
                border-top: 1px solid #e9ecef;
                justify-content: center;
            }
        }
        
        /* Mobile Toggle Button */
        .navbar-toggler {
            border: none;
            padding: 0.5rem;
            border-radius: 8px;
        }
        .navbar-toggler:focus {
            box-shadow: none;
        }
        .navbar-toggler-icon {
            width: 1.25em;
            height: 1.25em;
        }
        
        /* Search Modal */
        .search-modal .modal-content {
            border: none;
            border-radius: 16px;
            overflow: hidden;
        }
        .search-modal .modal-header {
            border: none;
            padding: 1.5rem 1.5rem 0.5rem;
        }
        .search-modal .modal-body {
            padding: 1rem 1.5rem 1.5rem;
        }
        .search-input-wrapper {
            position: relative;
        }
        .search-input-wrapper input {
            padding: 1rem 1rem 1rem 3rem;
            border-radius: 12px;
            border: 2px solid #e9ecef;
            font-size: 1.1rem;
            transition: border-color 0.2s ease;
        }
        .search-input-wrapper input:focus {
            border-color: #8B4513;
            box-shadow: 0 0 0 4px rgba(139, 69, 19, 0.1);
        }
        .search-input-wrapper i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #8B4513;
            font-size: 1.25rem;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">
    <!-- Skip to main content for accessibility -->
    <a href="#main-content" class="visually-hidden-focusable skip-link">Skip to main content</a>
    
    <!-- Top Bar -->
    <div class="top-bar text-light py-2 d-none d-md-block">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <div class="d-flex align-items-center gap-4">
                        <a href="mailto:info@ceyloncinnamon.com" class="d-flex align-items-center gap-2">
                            <i class="bi bi-envelope"></i>
                            <span>info@ceyloncinnamon.com</span>
                        </a>
                        <a href="tel:+94112345678" class="d-flex align-items-center gap-2">
                            <i class="bi bi-telephone"></i>
                            <span>+94 11 234 5678</span>
                        </a>
                    </div>
                </div>
                <div class="col-md-5 text-end">
                    <span class="highlight">
                        <i class="bi bi-truck me-2"></i><?= t('common.free_shipping', ['amount' => '$50']) ?? 'Free shipping on orders over $50' ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Navigation -->
    <nav class="navbar navbar-expand-lg main-navbar sticky-top" id="mainNavbar">
        <div class="container">
            <!-- Brand Logo -->
            <a class="navbar-brand" href="<?= url('/') ?>">
                <div class="brand-logo">
                    <i class="bi bi-flower1"></i>
                </div>
                <div>
                    <div class="brand-name"><?= APP_NAME ?></div>
                    <div class="brand-tagline">Premium Spices</div>
                </div>
            </a>
            
            <!-- Mobile Icons & Toggle -->
            <div class="d-flex d-lg-none align-items-center gap-2">
                <a href="<?= url('/cart') ?>" class="nav-icon-btn">
                    <i class="bi bi-bag"></i>
                    <span class="badge rounded-pill" id="mobile-cart-count">
                        <?= isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0 ?>
                    </span>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>
            
            <!-- Navigation Links -->
            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link <?= ($_SERVER['REQUEST_URI'] ?? '') === '/' || ($_SERVER['REQUEST_URI'] ?? '') === BASE_PATH . '/' ? 'active' : '' ?>" href="<?= url('/') ?>">
                            <?= t('nav.home') ?>
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?= strpos($_SERVER['REQUEST_URI'] ?? '', '/products') !== false ? 'active' : '' ?>" href="<?= url('/products') ?>" id="productsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?= t('nav.products') ?>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="productsDropdown">
                            <li><a class="dropdown-item" href="<?= url('/products') ?>"><i class="bi bi-grid-3x3-gap"></i><?= t('products.all_products') ?? 'All Products' ?></a></li>
                            <li><hr class="dropdown-divider mx-3"></li>
                            <li><a class="dropdown-item" href="<?= url('/products?category=cinnamon-sticks') ?>"><i class="bi bi-flower2"></i><?= t('products.category.sticks') ?? 'Cinnamon Sticks' ?></a></li>
                            <li><a class="dropdown-item" href="<?= url('/products?category=cinnamon-powder') ?>"><i class="bi bi-snow"></i><?= t('products.category.powder') ?? 'Cinnamon Powder' ?></a></li>
                            <li><a class="dropdown-item" href="<?= url('/products?category=cinnamon-oil') ?>"><i class="bi bi-droplet"></i><?= t('products.category.oil') ?? 'Cinnamon Oil' ?></a></li>
                            <li><a class="dropdown-item" href="<?= url('/products?category=cinnamon-tea') ?>"><i class="bi bi-cup-hot"></i><?= t('products.category.tea') ?? 'Cinnamon Tea' ?></a></li>
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
                <div class="nav-icons">
                    <!-- Search Button -->
                    <button class="nav-icon-btn d-none d-lg-flex" type="button" data-bs-toggle="modal" data-bs-target="#searchModal" aria-label="Search">
                        <i class="bi bi-search"></i>
                    </button>
                    
                    <!-- Cart (Desktop) -->
                    <a href="<?= url('/cart') ?>" class="nav-icon-btn d-none d-lg-flex">
                        <i class="bi bi-bag"></i>
                        <span class="badge rounded-pill" id="desktop-cart-count">
                            <?= isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0 ?>
                        </span>
                    </a>
                    
                    <!-- User Account -->
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="dropdown">
                            <a class="nav-link dropdown-toggle user-dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <div class="user-avatar">
                                    <?= strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 1)) ?>
                                </div>
                                <span class="user-name d-none d-xl-inline"><?= htmlspecialchars($_SESSION['user_name'] ?? t('nav.account')) ?></span>
                                <i class="bi bi-chevron-down d-none d-xl-inline" style="font-size: 0.7rem;"></i>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="<?= url('/dashboard') ?>"><i class="bi bi-speedometer2"></i><?= t('nav.dashboard') ?></a></li>
                                <li><a class="dropdown-item" href="<?= url('/dashboard/orders') ?>"><i class="bi bi-box-seam"></i><?= t('nav.orders') ?? 'My Orders' ?></a></li>
                                <li><a class="dropdown-item" href="<?= url('/dashboard/profile') ?>"><i class="bi bi-person"></i><?= t('nav.profile') ?? 'Profile' ?></a></li>
                                <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'content_manager'])): ?>
                                    <li><hr class="dropdown-divider mx-3"></li>
                                    <li><a class="dropdown-item" href="<?= url('/admin') ?>"><i class="bi bi-gear"></i><?= t('nav.admin') ?></a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider mx-3"></li>
                                <li><a class="dropdown-item text-danger" href="<?= url('/logout') ?>"><i class="bi bi-box-arrow-right"></i><?= t('nav.logout') ?></a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="<?= url('/login') ?>" class="nav-icon-btn" title="<?= t('nav.login') ?>">
                            <i class="bi bi-person"></i>
                        </a>
                    <?php endif; ?>
                    
                    <!-- Language Switcher -->
                    <div class="ms-2">
                        <?php include __DIR__ . '/../components/language_switcher.php'; ?>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Search Modal -->
    <div class="modal fade search-modal" id="searchModal" tabindex="-1" aria-labelledby="searchModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="searchModalLabel"><?= t('action.search') ?? 'Search' ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="<?= url('/products') ?>" method="GET">
                        <div class="search-input-wrapper">
                            <i class="bi bi-search"></i>
                            <input type="search" name="search" class="form-control form-control-lg" placeholder="<?= t('action.search_placeholder') ?? 'Search for products...' ?>" autofocus>
                        </div>
                        <div class="mt-3 text-center">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-search me-2"></i><?= t('action.search') ?? 'Search' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main id="main-content" class="flex-grow-1">
    
    <!-- Navbar Scroll Effect Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const navbar = document.getElementById('mainNavbar');
            if (navbar) {
                window.addEventListener('scroll', function() {
                    if (window.scrollY > 50) {
                        navbar.classList.add('scrolled');
                    } else {
                        navbar.classList.remove('scrolled');
                    }
                });
            }
            
            // Auto-focus search input when modal opens
            const searchModal = document.getElementById('searchModal');
            if (searchModal) {
                searchModal.addEventListener('shown.bs.modal', function() {
                    const searchInput = searchModal.querySelector('input[type="search"]');
                    if (searchInput) searchInput.focus();
                });
            }
        });
    </script>

