<?php
/**
 * Admin Create Shipping Method Form
 * Requirements: 14.2 (shipping cost calculation), 14.3 (multiple methods), 14.4 (delivery estimation)
 */

include VIEWS_PATH . '/admin/layouts/admin_header.php';

$successMsg = $sessionManager->getFlash('success');
$errorMsg = $sessionManager->getFlash('error');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Add Shipping Method</h1>
        <small class="text-muted">Zone: <?= htmlspecialchars($zone['name']) ?></small>
    </div>
    <a href="<?= url('/admin/shipping') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>

<?php if ($errorMsg): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($errorMsg) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<form action="<?= url('/admin/shipping/zones/' . $zone['id'] . '/methods') ?>" method="POST">
    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

    <div class="row">
        <div class="col-lg-8">
            <!-- Basic Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Basic Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Method Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required
                               placeholder="e.g., Standard Shipping, Express Delivery">
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="2"
                                  placeholder="Brief description of this shipping method"></textarea>
                    </div>
                </div>
            </div>

            <!-- Pricing -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Pricing</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="base_cost" class="form-label">Base Cost ($)</label>
                                <input type="number" class="form-control" id="base_cost" name="base_cost" 
                                       value="0.00" min="0" step="0.01">
                                <div class="form-text">Fixed cost for this shipping method.</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="cost_per_kg" class="form-label">Cost Per Kg ($)</label>
                                <input type="number" class="form-control" id="cost_per_kg" name="cost_per_kg" 
                                       value="0.00" min="0" step="0.01">
                                <div class="form-text">Additional cost per kilogram.</div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="free_shipping_threshold" class="form-label">Free Shipping Threshold ($)</label>
                        <input type="number" class="form-control" id="free_shipping_threshold" 
                               name="free_shipping_threshold" min="0" step="0.01" placeholder="Leave empty for no free shipping">
                        <div class="form-text">Orders above this amount get free shipping.</div>
                    </div>

                    <!-- Weight Brackets -->
                    <div class="mb-3">
                        <label class="form-label">Weight Brackets (Optional)</label>
                        <div class="form-text mb-2">Define specific costs for weight ranges.</div>
                        
                        <div id="weight-brackets"></div>
                        
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addWeightBracket()">
                            <i class="bi bi-plus"></i> Add Weight Bracket
                        </button>
                    </div>
                </div>
            </div>

            <!-- Restrictions -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Restrictions</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="min_weight" class="form-label">Minimum Weight (kg)</label>
                                <input type="number" class="form-control" id="min_weight" name="min_weight" 
                                       min="0" step="0.001" placeholder="No minimum">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="max_weight" class="form-label">Maximum Weight (kg)</label>
                                <input type="number" class="form-control" id="max_weight" name="max_weight" 
                                       min="0" step="0.001" placeholder="No maximum">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="min_order_amount" class="form-label">Minimum Order Amount ($)</label>
                        <input type="number" class="form-control" id="min_order_amount" name="min_order_amount" 
                               min="0" step="0.01" placeholder="No minimum">
                        <div class="form-text">Minimum order value required to use this method.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Delivery Time -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Delivery Time</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="estimated_days_min" class="form-label">Minimum Days</label>
                        <input type="number" class="form-control" id="estimated_days_min" 
                               name="estimated_days_min" min="1" placeholder="e.g., 3">
                    </div>
                    <div class="mb-3">
                        <label for="estimated_days_max" class="form-label">Maximum Days</label>
                        <input type="number" class="form-control" id="estimated_days_max" 
                               name="estimated_days_max" min="1" placeholder="e.g., 7">
                    </div>
                    <div class="form-text">Estimated delivery time in business days.</div>
                </div>
            </div>

            <!-- Status -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Status</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="is_active" 
                                   name="is_active" value="1" checked>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="sort_order" class="form-label">Sort Order</label>
                        <input type="number" class="form-control" id="sort_order" name="sort_order" 
                               value="0" min="0">
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">Create Method</button>
                <a href="<?= url('/admin/shipping') ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </div>
    </div>
</form>

<script>
function addWeightBracket() {
    const container = document.getElementById('weight-brackets');
    const div = document.createElement('div');
    div.className = 'row mb-2 weight-bracket';
    div.innerHTML = `
        <div class="col-4">
            <input type="number" class="form-control form-control-sm" 
                   name="bracket_min_weight[]" placeholder="Min kg" min="0" step="0.001">
        </div>
        <div class="col-4">
            <input type="number" class="form-control form-control-sm" 
                   name="bracket_max_weight[]" placeholder="Max kg" min="0" step="0.001">
        </div>
        <div class="col-3">
            <input type="number" class="form-control form-control-sm" 
                   name="bracket_cost[]" placeholder="Cost $" min="0" step="0.01">
        </div>
        <div class="col-1">
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.weight-bracket').remove()">
                <i class="bi bi-x"></i>
            </button>
        </div>
    `;
    container.appendChild(div);
}
</script>

<?php include VIEWS_PATH . '/admin/layouts/admin_footer.php'; ?>
