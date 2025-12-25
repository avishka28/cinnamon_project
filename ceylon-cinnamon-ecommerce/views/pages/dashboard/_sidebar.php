<?php
/**
 * Dashboard Sidebar Component
 */
$currentPath = $_SERVER['REQUEST_URI'] ?? '';
?>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <nav class="nav flex-column dashboard-nav">
            <a class="nav-link <?= $currentPath === '/dashboard' ? 'active' : '' ?>" href="<?= url('/dashboard') ?>">
                <i class="bi bi-speedometer2 me-2"></i>
                <?= t('dashboard.overview') ?? 'Dashboard' ?>
            </a>
            <a class="nav-link <?= strpos($currentPath, '/dashboard/orders') !== false ? 'active' : '' ?>" href="<?= url('/dashboard/orders') ?>">
                <i class="bi bi-box-seam me-2"></i>
                <?= t('dashboard.my_orders') ?? 'My Orders' ?>
            </a>
            <a class="nav-link <?= strpos($currentPath, '/dashboard/profile') !== false ? 'active' : '' ?>" href="<?= url('/dashboard/profile') ?>">
                <i class="bi bi-person me-2"></i>
                <?= t('dashboard.profile') ?? 'Profile' ?>
            </a>
            <a class="nav-link <?= strpos($currentPath, '/dashboard/addresses') !== false ? 'active' : '' ?>" href="<?= url('/dashboard/addresses') ?>">
                <i class="bi bi-geo-alt me-2"></i>
                <?= t('dashboard.addresses') ?? 'Addresses' ?>
            </a>
            <hr class="my-2">
            <a class="nav-link text-danger" href="<?= url('/logout') ?>">
                <i class="bi bi-box-arrow-right me-2"></i>
                <?= t('nav.logout') ?>
            </a>
        </nav>
    </div>
</div>

<style>
.dashboard-nav .nav-link {
    padding: 0.75rem 1.25rem;
    color: var(--cc-gray-700);
    border-left: 3px solid transparent;
    transition: all 0.2s ease;
}

.dashboard-nav .nav-link:hover {
    background-color: var(--cc-gray-100);
    color: var(--cc-primary);
}

.dashboard-nav .nav-link.active {
    background-color: rgba(139, 69, 19, 0.1);
    color: var(--cc-primary);
    border-left-color: var(--cc-primary);
    font-weight: 500;
}
</style>
