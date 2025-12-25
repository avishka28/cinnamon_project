<?php
/**
 * Customer Dashboard - Profile Management
 */
include VIEWS_PATH . '/layouts/header.php';
?>

<section class="py-5 bg-light">
    <div class="container">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3 mb-4">
                <?php include __DIR__ . '/_sidebar.php'; ?>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-9">
                <?php if (!empty($_SESSION['flash']['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['flash']['success']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['flash']['success']); endif; ?>
                
                <?php if (!empty($_SESSION['flash']['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['flash']['error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['flash']['error']); endif; ?>
                
                <!-- Profile Information -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0"><?= t('dashboard.profile_info') ?? 'Profile Information' ?></h5>
                    </div>
                    <div class="card-body">
                        <form action="<?= url('/dashboard/profile') ?>" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="first_name" class="form-label"><?= t('label.first_name') ?> *</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" 
                                           value="<?= htmlspecialchars($user['first_name'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="last_name" class="form-label"><?= t('label.last_name') ?> *</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" 
                                           value="<?= htmlspecialchars($user['last_name'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label"><?= t('label.email') ?></label>
                                    <input type="email" class="form-control" id="email" 
                                           value="<?= htmlspecialchars($user['email'] ?? '') ?>" disabled>
                                    <small class="text-muted"><?= t('dashboard.email_cannot_change') ?? 'Email cannot be changed' ?></small>
                                </div>
                                <div class="col-md-6">
                                    <label for="phone" class="form-label"><?= t('label.phone') ?></label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check-lg me-2"></i><?= t('action.save') ?? 'Save Changes' ?>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Change Password -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0"><?= t('dashboard.change_password') ?? 'Change Password' ?></h5>
                    </div>
                    <div class="card-body">
                        <form action="<?= url('/dashboard/password') ?>" method="POST">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                            
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label for="current_password" class="form-label"><?= t('dashboard.current_password') ?? 'Current Password' ?> *</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="new_password" class="form-label"><?= t('dashboard.new_password') ?? 'New Password' ?> *</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" 
                                           minlength="8" required>
                                    <small class="text-muted"><?= t('dashboard.password_hint') ?? 'Minimum 8 characters' ?></small>
                                </div>
                                <div class="col-md-6">
                                    <label for="confirm_password" class="form-label"><?= t('dashboard.confirm_password') ?? 'Confirm New Password' ?> *</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                           minlength="8" required>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-shield-lock me-2"></i><?= t('dashboard.update_password') ?? 'Update Password' ?>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include VIEWS_PATH . '/layouts/footer.php'; ?>
