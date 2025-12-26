<?php
/**
 * Admin Blog Categories List View
 * Requirements: 8.1 - Blog category management
 */
include VIEWS_PATH . '/admin/layouts/admin_header.php';
$successFlash = $sessionManager->getFlash('success');
$errorFlash = $sessionManager->getFlash('error');
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Blog Categories</h1>
        <a href="<?= url('/admin/content/categories/create') ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> New Category
        </a>
    </div>

    <?php if ($successFlash): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($successFlash) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($errorFlash): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($errorFlash) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <?php if (empty($categories)): ?>
                <p class="text-muted text-center py-4">No categories found.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Slug</th>
                                <th>Posts</th>
                                <th>Status</th>
                                <th>Order</th>
                                <th width="120">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($category['name']) ?></strong></td>
                                    <td><code><?= htmlspecialchars($category['slug']) ?></code></td>
                                    <td><?= $category['post_count'] ?></td>
                                    <td>
                                        <?php if ($category['is_active']): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $category['sort_order'] ?></td>
                                    <td>
                                        <a href="<?= url('/admin/content/categories/' . $category['id'] . '/edit') ?>" 
                                           class="btn btn-sm btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if ($category['post_count'] == 0): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    onclick="deleteCategory(<?= $category['id'] ?>)" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" 
                                                    disabled title="Cannot delete - has posts">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function deleteCategory(id) {
    if (confirm('Are you sure you want to delete this category?')) {
        fetch('<?= url('/admin/content/categories/') ?>' + id, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '<?= $csrf_token ?>',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || 'Failed to delete category');
            }
        })
        .catch(() => alert('Failed to delete category'));
    }
}
</script>

<?php include VIEWS_PATH . '/admin/layouts/admin_footer.php'; ?>
