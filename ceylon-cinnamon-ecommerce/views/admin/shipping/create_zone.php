<?php
/**
 * Admin Create Shipping Zone Form
 * Requirement 14.5: Admin shipping rule management
 */
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Add Shipping Zone</h1>
                <a href="/admin/shipping" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>

            <?php if (isset($flash['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($flash['error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <form action="/admin/shipping/zones" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                        <div class="mb-3">
                            <label for="name" class="form-label">Zone Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required
                                   placeholder="e.g., North America, Europe, Asia Pacific">
                            <div class="form-text">A descriptive name for this shipping zone.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Countries <span class="text-danger">*</span></label>
                            <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                                <div class="row">
                                    <?php foreach ($countries as $code => $name): ?>
                                        <div class="col-md-4 col-sm-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" 
                                                       name="countries[]" value="<?= $code ?>" 
                                                       id="country_<?= $code ?>">
                                                <label class="form-check-label" for="country_<?= $code ?>">
                                                    <?= htmlspecialchars($name) ?> (<?= $code ?>)
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="form-text">Select the countries included in this shipping zone.</div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="sort_order" class="form-label">Sort Order</label>
                                    <input type="number" class="form-control" id="sort_order" name="sort_order" 
                                           value="0" min="0">
                                    <div class="form-text">Lower numbers appear first.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" id="is_active" 
                                               name="is_active" value="1" checked>
                                        <label class="form-check-label" for="is_active">Active</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="/admin/shipping" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Create Zone</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Quick select buttons
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('input[name="countries[]"]');
    
    // Add select all / deselect all buttons
    const container = document.querySelector('.border.rounded.p-3');
    const buttonDiv = document.createElement('div');
    buttonDiv.className = 'mb-3 border-bottom pb-2';
    buttonDiv.innerHTML = `
        <button type="button" class="btn btn-sm btn-outline-primary me-2" onclick="selectAll()">Select All</button>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAll()">Deselect All</button>
    `;
    container.insertBefore(buttonDiv, container.firstChild);
});

function selectAll() {
    document.querySelectorAll('input[name="countries[]"]').forEach(cb => cb.checked = true);
}

function deselectAll() {
    document.querySelectorAll('input[name="countries[]"]').forEach(cb => cb.checked = false);
}
</script>
