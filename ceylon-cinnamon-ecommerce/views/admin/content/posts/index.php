<?php
/**
 * Admin Blog Posts List View
 * Requirements: 8.1 - Blog post management
 */
include VIEWS_PATH . '/admin/layouts/admin_header.php';

$successMsg = $sessionManager->getFlash('success');
$errorMsg = $sessionManager->getFlash('error');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Blog Posts</h1>
    <a href="<?= url('/admin/content/posts/create') ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> New Post
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

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?= url('/admin/content/posts') ?>" class="row g-3">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control" 
                       placeholder="Search posts..." value="<?= htmlspecialchars($filters['search']) ?>">
            </div>
            <div class="col-md-3">
                <select name="category" class="form-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['id'] ?>" 
                                <?= $filters['category_id'] == $category['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="draft" <?= $filters['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="published" <?= $filters['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                    <option value="scheduled" <?= $filters['status'] === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-secondary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- Posts Table -->
<div class="card">
    <div class="card-body">
        <?php if (empty($posts)): ?>
            <p class="text-muted text-center py-4">No blog posts found.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Author</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th width="120">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($posts as $post): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($post['title']) ?></strong>
                                    <?php if ($post['featured_image']): ?>
                                        <i class="bi bi-image text-muted ms-1" title="Has featured image"></i>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($post['category_name'] ?? 'Uncategorized') ?></td>
                                <td><?= htmlspecialchars($post['author_name']) ?></td>
                                <td>
                                    <?php
                                    $statusClass = match($post['status']) {
                                        'published' => 'success',
                                        'scheduled' => 'warning',
                                        default => 'secondary'
                                    };
                                    ?>
                                    <span class="badge bg-<?= $statusClass ?>">
                                        <?= ucfirst($post['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($post['published_at']): ?>
                                        <?= date('M j, Y', strtotime($post['published_at'])) ?>
                                    <?php else: ?>
                                        <?= date('M j, Y', strtotime($post['created_at'])) ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?= url('/admin/content/posts/' . $post['id'] . '/edit') ?>" 
                                       class="btn btn-sm btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                            onclick="deletePost(<?= $post['id'] ?>)" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($pages > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $pages; $i++): ?>
                            <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                                <a class="page-link" href="<?= url('/admin/content/posts') ?>?page=<?= $i ?>&search=<?= urlencode($filters['search']) ?>&category=<?= $filters['category_id'] ?>&status=<?= $filters['status'] ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function deletePost(id) {
    if (confirm('Are you sure you want to delete this post?')) {
        fetch('<?= url('/admin/content/posts/') ?>' + id, {
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
                alert(data.error || 'Failed to delete post');
            }
        })
        .catch(() => alert('Failed to delete post'));
    }
}
</script>

<?php include VIEWS_PATH . '/admin/layouts/admin_footer.php'; ?>
