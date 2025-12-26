<?php
/**
 * Admin Layout Header
 * Requirements: 2.6 - Admin access to all administrative functions
 */

$sessionManager = new SessionManager();
$sessionManager->start();
$currentUser = $sessionManager->getUser();
$userRole = $sessionManager->getUserRole();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Admin - ' . APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 250px;
        }
        body {
            min-height: 100vh;
        }
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            background: #212529;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 100;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,.8);
            padding: 0.75rem 1rem;
            border-radius: 0;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: #fff;
            background: rgba(255,255,255,.1);
        }
        .sidebar .nav-link i {
            margin-right: 0.5rem;
            width: 20px;
            text-align: center;
        }
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
        }
        .admin-navbar {
            background: #fff;
            border-bottom: 1px solid #dee2e6;
        }
        .sidebar-brand {
            padding: 1rem;
            color: #fff;
            font-size: 1.25rem;
            font-weight: 600;
            border-bottom: 1px solid rgba(255,255,255,.1);
        }
        .sidebar-section {
            padding: 0.5rem 1rem;
            color: rgba(255,255,255,.5);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .stat-card {
            border-radius: 0.5rem;
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,.075);
        }
        .stat-card .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body class="bg-light">
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="sidebar-brand">
            <i class="bi bi-shop"></i> <?= APP_NAME ?>
        </div>
        
        <div class="sidebar-section mt-3">Main</div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin') !== false && $_SERVER['REQUEST_URI'] === '/admin' ? 'active' : '' ?>" href="<?= url('/admin') ?>">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
        </ul>

        <div class="sidebar-section mt-3">Catalog</div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/products') !== false ? 'active' : '' ?>" href="<?= url('/admin/products') ?>">
                    <i class="bi bi-box-seam"></i> Products
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/categories') !== false && strpos($_SERVER['REQUEST_URI'], '/content/categories') === false ? 'active' : '' ?>" href="<?= url('/admin/categories') ?>">
                    <i class="bi bi-tags"></i> Categories
                </a>
            </li>
        </ul>

        <div class="sidebar-section mt-3">Sales</div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/orders') !== false ? 'active' : '' ?>" href="<?= url('/admin/orders') ?>">
                    <i class="bi bi-cart3"></i> Orders
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/shipping') !== false ? 'active' : '' ?>" href="<?= url('/admin/shipping') ?>">
                    <i class="bi bi-truck"></i> Shipping
                </a>
            </li>
        </ul>

        <?php if ($userRole === 'admin'): ?>
        <div class="sidebar-section mt-3">Content</div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/content/posts') !== false ? 'active' : '' ?>" href="<?= url('/admin/content/posts') ?>">
                    <i class="bi bi-file-text"></i> Blog Posts
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/content/categories') !== false ? 'active' : '' ?>" href="<?= url('/admin/content/categories') ?>">
                    <i class="bi bi-tags"></i> Blog Categories
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/content/certificates') !== false ? 'active' : '' ?>" href="<?= url('/admin/content/certificates') ?>">
                    <i class="bi bi-award"></i> Certificates
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/admin/content/gallery') !== false ? 'active' : '' ?>" href="<?= url('/admin/content/gallery') ?>">
                    <i class="bi bi-images"></i> Gallery
                </a>
            </li>
        </ul>
        <?php endif; ?>

        <div class="mt-auto p-3 border-top border-secondary">
            <a class="nav-link text-danger" href="<?= url('/admin/logout') ?>">
                <i class="bi bi-box-arrow-left"></i> Logout
            </a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navbar -->
        <nav class="navbar admin-navbar px-4">
            <button class="btn btn-link d-md-none" type="button" onclick="document.querySelector('.sidebar').classList.toggle('show')">
                <i class="bi bi-list fs-4"></i>
            </button>
            <div class="ms-auto d-flex align-items-center">
                <a href="<?= url('/') ?>" class="btn btn-outline-secondary btn-sm me-3" target="_blank">
                    <i class="bi bi-eye"></i> View Site
                </a>
                <div class="dropdown">
                    <button class="btn btn-link text-dark dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i>
                        <?= htmlspecialchars($currentUser['first_name'] ?? 'Admin') ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?= url('/admin/profile') ?>"><i class="bi bi-person me-2"></i>Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?= url('/admin/logout') ?>"><i class="bi bi-box-arrow-left me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <div class="p-4">
