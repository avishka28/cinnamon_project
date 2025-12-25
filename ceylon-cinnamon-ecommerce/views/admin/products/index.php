<?php
/**
 * Admin Products List
 * Requirements: 6.1, 6.5 - Product management
 */

include VIEWS_PATH . '/admin/layouts/admin_header.php';

$flash = $sessionManager->getFlash('success') ?? $sessionManager->getFlash('error');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Products</h1>
    <a href="<?= url('/admin/products/create') ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg me-2"></i>Add Product
    </a>
</div>

<?php if ($flash): ?>
    <div class="alert alert-<?= isset($sessionManager->getFlash('error')) ? 'danger' : 'success' ?> alert-dismissible fade show">
        <?= htmlspecialchars($flash) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?= url('/admin/products') ?>" class="row g-3">
            <div class="col-md-4">
                <input type="text" class="form-control" name="search" 
                       placeholder="Search by name or SKU..." 
                       value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
            </div>
            <div class="col-md-3">
                <select class="form-select" name="category">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['id'] ?>" 
                                <?= ($filters['category_id'] ?? '') == $category['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" name="status">
                    <option value="">All Status</option>
                    <option value="1" <?= ($filters['status'] ?? '') === '1' ? 'selected' : '' ?>>Active</option>
                    <option value="0" <?= ($filters['status'] ?? '') === '0' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-outline-primary me-2">
                    <i class="bi bi-search me-1"></i>Filter
                </button>
                <a href="<?= url('/admin/products') ?>" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Products Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 60px;">Image</th>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th style="width: 120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($products)): ?>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <?php
                                    $productModel = new Product();
                                    $image = $productModel->getPrimaryImage($product['id']);
                                    ?>
                                    <?php if ($image): ?>
                                        <img src="<?= htmlspecialchars($image['image_url']) ?>" 
                                             alt="<?= htmlspecialchars($product['name']) ?>"
                                             class="rounded" style="width: 50px; height: 50px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-light rounded d-flex align-items-center justify-content-center" 
                                             style="width: 50px; height: 50px;">
                                            <i class="bi bi-image text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($product['name']) ?></strong>
                                    <?php if ($product['is_organic']): ?>
                                        <span class="badge bg-success ms-1">Organic</span>
                                    <?php endif; ?>
                                </td>
                                <td><code><?= htmlspecialchars($product['sku']) ?></code></td>
                                <td><?= htmlspecialchars($product['category_name'] ?? 'N/A') ?></td>
                                <td>
                                    <?php if ($product['sale_price']): ?>
                                        <span class="text-decoration-line-through text-muted">
                                            $<?= number_format($product['price'], 2) ?>
                                        </span>
                                        <br>
                                        <span class="text-danger fw-bold">
                                            $<?= number_format($product['sale_price'], 2) ?>
                                        </span>
                                    <?php else: ?>
                                        $<?= number_format($product['price'], 2) ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($product['stock_quantity'] <= 0): ?>
                                        <span class="badge bg-danger">Out of Stock</span>
                                    <?php elseif ($product['stock_quantity'] <= 5): ?>
                                        <span class="badge bg-warning"><?= $product['stock_quantity'] ?> left</span>
                                    <?php else: ?>
                                        <span class="text-success"><?= $product['stock_quantity'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($product['is_active']): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= url('/admin/products/' . $product['id'] . '/edit') ?>" 
                                           class="btn btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="<?= url('/products/' . $product['slug']) ?>" 
                                           class="btn btn-outline-secondary" title="View" target="_blank">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-danger" 
                                                onclick="deleteProduct(<?= $product['id'] ?>, '<?= htmlspecialchars($product['name']) ?>')"
                                                title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-box-seam fs-1 d-block mb-3"></i>
                                No products found
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <?php if ($pages > 1): ?>
        <div class="card-footer">
            <nav>
                <ul class="pagination mb-0 justify-content-center">
                    <?php for ($i = 1; $i <= $pages; $i++): ?>
                        <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                            <a class="page-link" href="<?= url('/admin/products?page=' . $i . '&search=' . urlencode($filters['search'] ?? '') . '&category=' . ($filters['category_id'] ?? '') . '&status=' . ($filters['status'] ?? '')) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<script>
function deleteProduct(id, name) {
    if (!confirm('Are you sure you want to delete "' + name + '"? This action cannot be undone.')) {
        return;
    }
    
    fetch('<?= url('/admin/products/') ?>' + id, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '<?= $csrf_token ?>',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Failed to delete product');
        }
    })
    .catch(error => {
        alert('An error occurred');
        console.error(error);
    });
}
</script>

<?php include VIEWS_PATH . '/admin/layouts/admin_footer.php'; ?>
