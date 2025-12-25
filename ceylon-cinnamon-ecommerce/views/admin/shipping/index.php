<?php
/**
 * Admin Shipping Management Index
 * Displays all shipping zones and methods
 * Requirement 14.5: Admin shipping rule management
 */
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Shipping Management</h1>
        <a href="/admin/shipping/zones/create" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Add Shipping Zone
        </a>
    </div>

    <?php if (isset($flash['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($flash['success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($flash['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($flash['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (empty($zones)): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-truck display-4 text-muted mb-3"></i>
                <h5>No Shipping Zones</h5>
                <p class="text-muted">Create your first shipping zone to start configuring shipping rates.</p>
                <a href="/admin/shipping/zones/create" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i> Add Shipping Zone
                </a>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($zones as $zone): ?>
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">
                            <?= htmlspecialchars($zone['name']) ?>
                            <?php if (!$zone['is_active']): ?>
                                <span class="badge bg-secondary ms-2">Inactive</span>
                            <?php endif; ?>
                        </h5>
                        <small class="text-muted">
                            Countries: <?= implode(', ', $zone['countries_array'] ?? []) ?>
                        </small>
                    </div>
                    <div class="btn-group">
                        <a href="/admin/shipping/zones/<?= $zone['id'] ?>/methods/create" 
                           class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-plus"></i> Add Method
                        </a>
                        <a href="/admin/shipping/zones/<?= $zone['id'] ?>/edit" 
                           class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                onclick="deleteZone(<?= $zone['id'] ?>)">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
                
                <?php if (!empty($zone['methods'])): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Method Name</th>
                                    <th>Base Cost</th>
                                    <th>Per Kg</th>
                                    <th>Free Shipping</th>
                                    <th>Delivery Time</th>
                                    <th>Status</th>
                                    <th width="120">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($zone['methods'] as $method): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($method['name']) ?></strong>
                                            <?php if ($method['description']): ?>
                                                <br><small class="text-muted"><?= htmlspecialchars($method['description']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>$<?= number_format((float)$method['base_cost'], 2) ?></td>
                                        <td>$<?= number_format((float)$method['cost_per_kg'], 2) ?></td>
                                        <td>
                                            <?php if ($method['free_shipping_threshold']): ?>
                                                Orders over $<?= number_format((float)$method['free_shipping_threshold'], 2) ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($method['estimated_days_min']): ?>
                                                <?= $method['estimated_days_min'] ?>
                                                <?php if ($method['estimated_days_max'] && $method['estimated_days_max'] != $method['estimated_days_min']): ?>
                                                    - <?= $method['estimated_days_max'] ?>
                                                <?php endif; ?>
                                                days
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($method['is_active']): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="/admin/shipping/methods/<?= $method['id'] ?>/edit" 
                                               class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    onclick="deleteMethod(<?= $method['id'] ?>)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="card-body text-center py-4">
                        <p class="text-muted mb-2">No shipping methods configured for this zone.</p>
                        <a href="/admin/shipping/zones/<?= $zone['id'] ?>/methods/create" class="btn btn-sm btn-primary">
                            Add Shipping Method
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
function deleteZone(id) {
    if (!confirm('Are you sure you want to delete this shipping zone? All associated methods will also be deleted.')) {
        return;
    }
    
    fetch('/admin/shipping/zones/' + id, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ csrf_token: '<?= $csrf_token ?>' })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Failed to delete zone');
        }
    })
    .catch(() => alert('An error occurred'));
}

function deleteMethod(id) {
    if (!confirm('Are you sure you want to delete this shipping method?')) {
        return;
    }
    
    fetch('/admin/shipping/methods/' + id, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ csrf_token: '<?= $csrf_token ?>' })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Failed to delete method');
        }
    })
    .catch(() => alert('An error occurred'));
}
</script>
