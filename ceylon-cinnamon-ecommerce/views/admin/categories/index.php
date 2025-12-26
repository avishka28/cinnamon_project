<?php
/**
 * Admin Categories List View
 * Requirements: 6.5 - Product categories with CRUD operations
 */
include VIEWS_PATH . '/admin/layouts/admin_header.php';

$successMsg = $sessionManager->getFlash('success');
$errorMsg = $sessionManager->getFlash('error');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Categories</h1>
    <a href="<?= url('/admin/categories/create') ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Add Category
    </a>
</div>

<?php if ($successMsg): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <?= htmlspecialchars($successMsg) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($errorMsg): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?= htmlspecialchars($errorMsg) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <?php if (empty($categories)): ?>
        <div class="text-center py-5">
            <i class="bi bi-tags display-4 text-muted"></i>
            <p class="text-muted mt-3">No categories found.</p>
            <a href="<?= url('/admin/categories/create') ?>" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Add First Category
            </a>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Products</th>
                        <th>Order</th>
                        <th>Status</th>
                        <th width="150">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $category): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($category['name']) ?></strong>
                            <?php if (!empty($category['description'])): ?>
                            <br><small class="text-muted"><?= htmlspecialchars(substr($category['description'], 0, 50)) ?>...</small>
                            <?php endif; ?>
                        </td>
                        <td><code><?= htmlspecialchars($category['slug']) ?></code></td>
                        <td>
                            <span class="badge bg-secondary"><?= $category['product_count'] ?></span>
                        </td>
                        <td><?= $category['sort_order'] ?></td>
                        <td>
                            <?php if ($category['is_active']): ?>
                            <span class="badge bg-success">Active</span>
                            <?php else: ?>
                            <span class="badge bg-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="<?= url('/category/' . $category['slug']) ?>" 
                                   class="btn btn-outline-secondary" target="_blank" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="<?= url('/admin/categories/' . $category['id'] . '/edit') ?>" 
                                   class="btn btn-outline-primary" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button type="button" class="btn btn-outline-danger" 
                                        onclick="deleteCategory(<?= $category['id'] ?>, '<?= htmlspecialchars($category['name']) ?>')"
                                        title="Delete" <?= $category['product_count'] > 0 ? 'disabled' : '' ?>>
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="deleteCategoryName"></strong>?</p>
                <p class="text-danger mb-0"><small>This action cannot be undone.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function deleteCategory(id, name) {
    document.getElementById('deleteCategoryName').textContent = name;
    document.getElementById('deleteForm').action = '<?= url('/admin/categories/') ?>' + id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php include VIEWS_PATH . '/admin/layouts/admin_footer.php'; ?>
