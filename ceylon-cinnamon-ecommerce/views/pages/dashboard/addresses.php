<?php
/**
 * Customer Dashboard - Address Book
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
                
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><?= t('dashboard.addresses') ?? 'My Addresses' ?></h5>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addAddressModal">
                            <i class="bi bi-plus-lg me-1"></i><?= t('dashboard.add_address') ?? 'Add Address' ?>
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if (empty($addresses)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-geo-alt display-4 text-muted"></i>
                            <p class="text-muted mt-3"><?= t('dashboard.no_addresses') ?? 'No saved addresses yet' ?></p>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAddressModal">
                                <i class="bi bi-plus-lg me-2"></i><?= t('dashboard.add_first_address') ?? 'Add Your First Address' ?>
                            </button>
                        </div>
                        <?php else: ?>
                        <div class="row g-4">
                            <?php foreach ($addresses as $address): ?>
                            <div class="col-md-6">
                                <div class="card h-100 <?= $address['is_default'] ? 'border-primary' : '' ?>">
                                    <div class="card-body">
                                        <?php if ($address['is_default']): ?>
                                        <span class="badge bg-primary mb-2"><?= t('dashboard.default') ?? 'Default' ?></span>
                                        <?php endif; ?>
                                        <h6 class="card-title"><?= htmlspecialchars($address['label'] ?? 'Address') ?></h6>
                                        <p class="card-text text-muted small">
                                            <?= htmlspecialchars($address['first_name'] . ' ' . $address['last_name']) ?><br>
                                            <?= htmlspecialchars($address['address_line1']) ?><br>
                                            <?php if (!empty($address['address_line2'])): ?>
                                            <?= htmlspecialchars($address['address_line2']) ?><br>
                                            <?php endif; ?>
                                            <?= htmlspecialchars($address['city'] . ', ' . $address['state'] . ' ' . $address['postal_code']) ?><br>
                                            <?= htmlspecialchars($address['country']) ?>
                                        </p>
                                        <div class="d-flex gap-2">
                                            <button type="button" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil"></i> <?= t('action.edit') ?>
                                            </button>
                                            <?php if (!$address['is_default']): ?>
                                            <button type="button" class="btn btn-sm btn-outline-secondary">
                                                <?= t('dashboard.set_default') ?? 'Set as Default' ?>
                                            </button>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Add Address Modal -->
<div class="modal fade" id="addAddressModal" tabindex="-1" aria-labelledby="addAddressModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addAddressModalLabel"><?= t('dashboard.add_address') ?? 'Add New Address' ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= url('/dashboard/addresses') ?>" method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="label" class="form-label"><?= t('dashboard.address_label') ?? 'Address Label' ?></label>
                            <input type="text" class="form-control" id="label" name="label" placeholder="e.g., Home, Office">
                        </div>
                        <div class="col-md-6">
                            <label for="addr_first_name" class="form-label"><?= t('label.first_name') ?> *</label>
                            <input type="text" class="form-control" id="addr_first_name" name="first_name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="addr_last_name" class="form-label"><?= t('label.last_name') ?> *</label>
                            <input type="text" class="form-control" id="addr_last_name" name="last_name" required>
                        </div>
                        <div class="col-12">
                            <label for="address_line1" class="form-label"><?= t('dashboard.address_line1') ?? 'Address Line 1' ?> *</label>
                            <input type="text" class="form-control" id="address_line1" name="address_line1" required>
                        </div>
                        <div class="col-12">
                            <label for="address_line2" class="form-label"><?= t('dashboard.address_line2') ?? 'Address Line 2' ?></label>
                            <input type="text" class="form-control" id="address_line2" name="address_line2">
                        </div>
                        <div class="col-md-6">
                            <label for="addr_city" class="form-label"><?= t('label.city') ?> *</label>
                            <input type="text" class="form-control" id="addr_city" name="city" required>
                        </div>
                        <div class="col-md-6">
                            <label for="addr_state" class="form-label"><?= t('dashboard.state') ?? 'State/Province' ?></label>
                            <input type="text" class="form-control" id="addr_state" name="state">
                        </div>
                        <div class="col-md-6">
                            <label for="postal_code" class="form-label"><?= t('dashboard.postal_code') ?? 'Postal Code' ?> *</label>
                            <input type="text" class="form-control" id="postal_code" name="postal_code" required>
                        </div>
                        <div class="col-md-6">
                            <label for="addr_country" class="form-label"><?= t('label.country') ?> *</label>
                            <select class="form-select" id="addr_country" name="country" required>
                                <option value="">Select Country</option>
                                <option value="US">United States</option>
                                <option value="UK">United Kingdom</option>
                                <option value="CA">Canada</option>
                                <option value="AU">Australia</option>
                                <option value="LK">Sri Lanka</option>
                                <option value="DE">Germany</option>
                                <option value="FR">France</option>
                                <option value="JP">Japan</option>
                                <option value="SG">Singapore</option>
                                <option value="AE">United Arab Emirates</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_default" name="is_default" value="1">
                                <label class="form-check-label" for="is_default">
                                    <?= t('dashboard.set_as_default') ?? 'Set as default address' ?>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= t('action.cancel') ?></button>
                    <button type="submit" class="btn btn-primary"><?= t('action.save') ?? 'Save Address' ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include VIEWS_PATH . '/layouts/footer.php'; ?>
