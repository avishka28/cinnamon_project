<?php
/**
 * Admin CSV Import Form
 * Requirements: 6.4 - Bulk product import with data validation
 */

include VIEWS_PATH . '/admin/layouts/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?= url('/admin/products') ?>" class="text-decoration-none text-muted">
            <i class="bi bi-arrow-left me-2"></i>Back to Products
        </a>
        <h1 class="h3 mb-0 mt-2">Import Products from CSV</h1>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Upload CSV File</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="<?= url('/admin/products/import') ?>" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    
                    <div class="mb-4">
                        <label for="csv_file" class="form-label">Select CSV File</label>
                        <input type="file" class="form-control" id="csv_file" name="csv_file" 
                               accept=".csv,text/csv" required>
                        <div class="form-text">
                            Maximum file size: 10MB. File must be in CSV format.
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-upload me-2"></i>Import Products
                        </button>
                        <a href="<?= url('/admin/products/template') ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-download me-2"></i>Download Template
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">CSV Format Requirements</h5>
            </div>
            <div class="card-body">
                <h6>Required Columns:</h6>
                <ul>
                    <li><code>sku</code> - Unique product identifier</li>
                    <li><code>name</code> - Product name</li>
                    <li><code>price</code> - Regular price (numeric)</li>
                    <li><code>category</code> - Category name, slug, or ID</li>
                </ul>

                <h6 class="mt-4">Optional Columns:</h6>
                <ul>
                    <li><code>description</code> - Full product description</li>
                    <li><code>short_description</code> - Brief description</li>
                    <li><code>sale_price</code> - Sale price (must be less than regular price)</li>
                    <li><code>stock_quantity</code> - Available stock (default: 0)</li>
                    <li><code>weight</code> - Product weight in kg</li>
                    <li><code>dimensions</code> - Product dimensions (e.g., "10x5x3")</li>
                    <li><code>is_organic</code> - 1 for organic, 0 for non-organic</li>
                    <li><code>origin</code> - Country of origin</li>
                    <li><code>tags</code> - Comma-separated tags</li>
                    <li><code>meta_title</code> - SEO title</li>
                    <li><code>meta_description</code> - SEO description</li>
                    <li><code>is_active</code> - 1 for active, 0 for inactive (default: 1)</li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="mb-0">Import Tips</h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li class="mb-3">
                        <i class="bi bi-check-circle text-success me-2"></i>
                        Download the template to see the correct format
                    </li>
                    <li class="mb-3">
                        <i class="bi bi-check-circle text-success me-2"></i>
                        Ensure SKUs are unique - duplicates will be skipped
                    </li>
                    <li class="mb-3">
                        <i class="bi bi-check-circle text-success me-2"></i>
                        Categories must exist before import
                    </li>
                    <li class="mb-3">
                        <i class="bi bi-check-circle text-success me-2"></i>
                        Use quotes for fields containing commas
                    </li>
                    <li class="mb-3">
                        <i class="bi bi-info-circle text-info me-2"></i>
                        First row should contain column headers
                    </li>
                    <li>
                        <i class="bi bi-exclamation-triangle text-warning me-2"></i>
                        Invalid rows will be skipped with error details
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include VIEWS_PATH . '/admin/layouts/admin_footer.php'; ?>
