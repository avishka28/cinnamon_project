<?php
/**
 * Admin Edit Category View
 * Requirements: 6.5 - Product categories with CRUD operations
 */
include VIEWS_PATH . '/admin/layouts/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Edit Category</h1>
    <a href="<?= url('/admin/categories') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Categories
    </a>
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

<div class="card">
    <div class="card-body">
        <form action="<?= url('/admin/categories/' . $category['id']) ?>" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            
            <div class="row">
                <div class="col-md-8">
                    <div class="mb-3">
                        <label for="name" class="form-label">Category Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" 
                               value="<?= htmlspecialchars($category['name']) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="slug" class="form-label">Slug</label>
                        <input type="text" class="form-control" id="slug" name="slug" 
                               value="<?= htmlspecialchars($category['slug']) ?>">
                        <div class="form-text">URL-friendly version of the name.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="4"><?= htmlspecialchars($category['description'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="image_url" class="form-label">Image URL</label>
                        <input type="text" class="form-control" id="image_url" name="image_url" 
                               value="<?= htmlspecialchars($category['image_url'] ?? '') ?>"
                               placeholder="/uploads/categories/image.jpg">
                        <?php if (!empty($category['image_url'])): ?>
                        <div class="mt-2">
                            <img src="<?= url($category['image_url']) ?>" alt="Category image" 
                                 style="max-width: 200px; max-height: 100px;" class="rounded">
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6 class="card-title">Settings</h6>
                            
                            <div class="mb-3">
                                <label for="parent_id" class="form-label">Parent Category</label>
                                <select class="form-select" id="parent_id" name="parent_id">
                                    <option value="">None (Top Level)</option>
                                    <?php foreach ($parentCategories as $parent): ?>
                                    <option value="<?= $parent['id'] ?>" 
                                            <?= $category['parent_id'] == $parent['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($parent['name']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="sort_order" class="form-label">Sort Order</label>
                                <input type="number" class="form-control" id="sort_order" name="sort_order" 
                                       value="<?= htmlspecialchars($category['sort_order'] ?? '0') ?>" min="0">
                                <div class="form-text">Lower numbers appear first.</div>
                            </div>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                           value="1" <?= $category['is_active'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="is_active">Active</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mt-3">
                        <div class="card-body">
                            <h6 class="card-title">Info</h6>
                            <small class="text-muted">
                                Created: <?= date('M j, Y', strtotime($category['created_at'])) ?><br>
                                Updated: <?= date('M j, Y', strtotime($category['updated_at'])) ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            
            <hr>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> Update Category
                </button>
                <a href="<?= url('/admin/categories') ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include VIEWS_PATH . '/admin/layouts/admin_footer.php'; ?>
