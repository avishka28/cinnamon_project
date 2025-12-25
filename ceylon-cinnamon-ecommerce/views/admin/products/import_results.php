<?php
/**
 * Admin CSV Import Results
 * Requirements: 6.4 - Bulk product import with data validation
 */

include VIEWS_PATH . '/admin/layouts/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?= url('/admin/products') ?>" class="text-decoration-none text-muted">
            <i class="bi bi-arrow-left me-2"></i>Back to Products
        </a>
        <h1 class="h3 mb-0 mt-2">Import Results</h1>
    </div>
    <a href="<?= url('/admin/products/import') ?>" class="btn btn-outline-primary">
        <i class="bi bi-upload me-2"></i>Import More
    </a>
</div>

<!-- Summary Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card border-success">
            <div class="card-body text-center">
                <h2 class="text-success mb-0"><?= $results['imported_count'] ?></h2>
                <p class="text-muted mb-0">Products Imported</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-warning">
            <div class="card-body text-center">
                <h2 class="text-warning mb-0"><?= $results['skipped_count'] ?></h2>
                <p class="text-muted mb-0">Rows Skipped</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-<?= empty($results['errors']) ? 'success' : 'danger' ?>">
            <div class="card-body text-center">
                <h2 class="<?= empty($results['errors']) ? 'text-success' : 'text-danger' ?> mb-0">
                    <?= empty($results['errors']) ? '✓' : count($results['errors']) ?>
                </h2>
                <p class="text-muted mb-0"><?= empty($results['errors']) ? 'No Errors' : 'Errors' ?></p>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($results['errors'])): ?>
    <div class="alert alert-danger">
        <h5 class="alert-heading"><i class="bi bi-exclamation-triangle me-2"></i>Import Errors</h5>
        <ul class="mb-0">
            <?php foreach ($results['errors'] as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if (!empty($results['imported'])): ?>
    <div class="card mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-success">
                <i class="bi bi-check-circle me-2"></i>Successfully Imported (<?= count($results['imported']) ?>)
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Row</th>
                            <th>SKU</th>
                            <th>Product Name</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results['imported'] as $item): ?>
                            <tr>
                                <td><?= $item['row'] ?></td>
                                <td><code><?= htmlspecialchars($item['sku']) ?></code></td>
                                <td><?= htmlspecialchars($item['name']) ?></td>
                                <td>
                                    <a href="<?= url('/admin/products/' . $item['id'] . '/edit') ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($results['skipped'])): ?>
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-warning">
                <i class="bi bi-exclamation-triangle me-2"></i>Skipped Rows (<?= count($results['skipped']) ?>)
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Row</th>
                            <th>SKU</th>
                            <th>Errors</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results['skipped'] as $item): ?>
                            <tr>
                                <td><?= $item['row'] ?></td>
                                <td><code><?= htmlspecialchars($item['sku']) ?></code></td>
                                <td>
                                    <ul class="mb-0 ps-3">
                                        <?php foreach ($item['errors'] as $error): ?>
                                            <li class="text-danger"><?= htmlspecialchars($error) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include VIEWS_PATH . '/admin/layouts/admin_footer.php'; ?>
