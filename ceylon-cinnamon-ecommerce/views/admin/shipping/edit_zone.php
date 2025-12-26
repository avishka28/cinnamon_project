<?php
/**
 * Admin Edit Shipping Zone Form
 * Requirement 14.5: Admin shipping rule management
 */

include VIEWS_PATH . '/admin/layouts/admin_header.php';

$successMsg = $sessionManager->getFlash('success');
$errorMsg = $sessionManager->getFlash('error');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Edit Shipping Zone</h1>
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

<div class="card">
    <div class="card-body">
        <form action="<?= url('/admin/shipping/zones/' . $zone['id']) ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

            <div class="mb-3">
                <label for="name" class="form-label">Zone Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="name" name="name" required
                       value="<?= htmlspecialchars($zone['name']) ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Countries <span class="text-danger">*</span></label>
                <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                    <div class="mb-2 border-bottom pb-2">
                        <button type="button" class="btn btn-sm btn-outline-primary me-2" onclick="selectAll()">Select All</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAll()">Deselect All</button>
                    </div>
                    <div class="row">
                        <?php foreach ($countries as $code => $name): ?>
                            <div class="col-md-4 col-sm-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" 
                                           name="countries[]" value="<?= $code ?>" 
                                           id="country_<?= $code ?>"
                                           <?= in_array($code, $zone['countries_array']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="country_<?= $code ?>">
                                        <?= htmlspecialchars($name) ?> (<?= $code ?>)
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="sort_order" class="form-label">Sort Order</label>
                        <input type="number" class="form-control" id="sort_order" name="sort_order" 
                               value="<?= (int)$zone['sort_order'] ?>" min="0">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" id="is_active" 
                                   name="is_active" value="1" <?= $zone['is_active'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>
                    </div>
                </div>
            </div>

            <hr>

            <div class="d-flex justify-content-end gap-2">
                <a href="<?= url('/admin/shipping') ?>" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Zone</button>
            </div>
        </form>
    </div>
</div>

<script>
function selectAll() {
    document.querySelectorAll('input[name="countries[]"]').forEach(cb => cb.checked = true);
}

function deselectAll() {
    document.querySelectorAll('input[name="countries[]"]').forEach(cb => cb.checked = false);
}
</script>

<?php include VIEWS_PATH . '/admin/layouts/admin_footer.php'; ?>
