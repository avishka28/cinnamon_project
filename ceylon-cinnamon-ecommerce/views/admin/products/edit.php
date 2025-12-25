<?php
/**
 * Admin Edit Product Form
 * Requirements: 6.1 - Store product details (SKU, stock, price, weight, dimensions)
 */

include VIEWS_PATH . '/admin/layouts/admin_header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <a href="<?= url('/admin/products') ?>" class="text-decoration-none text-muted">
            <i class="bi bi-arrow-left me-2"></i>Back to Products
        </a>
        <h1 class="h3 mb-0 mt-2">Edit Product</h1>
    </div>
    <a href="<?= url('/products/' . $product['slug']) ?>" class="btn btn-outline-secondary" target="_blank">
        <i class="bi bi-eye me-2"></i>View Product
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

<form method="POST" action="<?= url('/admin/products/' . $product['id']) ?>" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
    
    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Basic Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Product Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" 
                               value="<?= htmlspecialchars($product['name'] ?? '') ?>" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="sku" class="form-label">SKU <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="sku" name="sku" 
                                   value="<?= htmlspecialchars($product['sku'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="slug" class="form-label">URL Slug</label>
                            <input type="text" class="form-control" id="slug" name="slug" 
                                   value="<?= htmlspecialchars($product['slug'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="short_description" class="form-label">Short Description</label>
                        <textarea class="form-control" id="short_description" name="short_description" 
                                  rows="2" maxlength="500"><?= htmlspecialchars($product['short_description'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Full Description</label>
                        <textarea class="form-control" id="description" name="description" 
                                  rows="6"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Pricing & Inventory</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="price" class="form-label">Price ($) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="price" name="price" 
                                   step="0.01" min="0" value="<?= htmlspecialchars($product['price'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="sale_price" class="form-label">Sale Price ($)</label>
                            <input type="number" class="form-control" id="sale_price" name="sale_price" 
                                   step="0.01" min="0" value="<?= htmlspecialchars($product['sale_price'] ?? '') ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="stock_quantity" class="form-label">Stock Quantity <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" 
                                   min="0" value="<?= htmlspecialchars($product['stock_quantity'] ?? '0') ?>" required>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Shipping Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="weight" class="form-label">Weight (kg)</label>
                            <input type="number" class="form-control" id="weight" name="weight" 
                                   step="0.001" min="0" value="<?= htmlspecialchars($product['weight'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="dimensions" class="form-label">Dimensions (L x W x H cm)</label>
                            <input type="text" class="form-control" id="dimensions" name="dimensions" 
                                   placeholder="e.g., 10 x 5 x 3" value="<?= htmlspecialchars($product['dimensions'] ?? '') ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Product Images</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($images)): ?>
                        <div class="row g-3 mb-3">
                            <?php foreach ($images as $image): ?>
                                <div class="col-md-3">
                                    <div class="position-relative">
                                        <img src="<?= htmlspecialchars($image['image_url']) ?>" 
                                             class="img-fluid rounded" 
                                             style="height: 120px; width: 100%; object-fit: cover;">
                                        <?php if ($image['is_primary']): ?>
                                            <span class="badge bg-primary position-absolute top-0 start-0 m-1">Primary</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label for="images" class="form-label">Add More Images</label>
                        <input type="file" class="form-control" id="images" name="images[]" 
                               accept="image/jpeg,image/png,image/gif,image/webp" multiple>
                        <div class="form-text">Accepted formats: JPEG, PNG, GIF, WebP. Max 10MB per file.</div>
                    </div>
                    <div id="image-preview" class="row g-2"></div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">SEO</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="meta_title" class="form-label">Meta Title</label>
                        <input type="text" class="form-control" id="meta_title" name="meta_title" 
                               maxlength="255" value="<?= htmlspecialchars($product['meta_title'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="meta_description" class="form-label">Meta Description</label>
                        <textarea class="form-control" id="meta_description" name="meta_description" 
                                  rows="2" maxlength="500"><?= htmlspecialchars($product['meta_description'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Publish</h5>
                </div>
                <div class="card-body">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                               value="1" <?= ($product['is_active'] ?? 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_active">Active (visible on store)</label>
                    </div>
                    <div class="text-muted small mb-3">
                        Created: <?= date('M j, Y g:i A', strtotime($product['created_at'])) ?>
                        <?php if (!empty($product['updated_at'])): ?>
                            <br>Updated: <?= date('M j, Y g:i A', strtotime($product['updated_at'])) ?>
                        <?php endif; ?>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-check-lg me-2"></i>Update Product
                    </button>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Category <span class="text-danger">*</span></h5>
                </div>
                <div class="card-body">
                    <select class="form-select" id="category_id" name="category_id" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>" 
                                    <?= ($product['category_id'] ?? '') == $category['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($category['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Product Attributes</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="origin" class="form-label">Origin</label>
                        <input type="text" class="form-control" id="origin" name="origin" 
                               placeholder="e.g., Sri Lanka" value="<?= htmlspecialchars($product['origin'] ?? '') ?>">
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="is_organic" name="is_organic" 
                               value="1" <?= ($product['is_organic'] ?? 0) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_organic">Certified Organic</label>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Tags</h5>
                </div>
                <div class="card-body">
                    <input type="text" class="form-control" id="tags" name="tags" 
                           placeholder="Comma-separated tags" value="<?= htmlspecialchars($product['tags'] ?? '') ?>">
                    <div class="form-text">e.g., premium, gift, bestseller</div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
// Image preview
document.getElementById('images').addEventListener('change', function(e) {
    const preview = document.getElementById('image-preview');
    preview.innerHTML = '';
    
    Array.from(e.target.files).forEach((file, index) => {
        if (!file.type.startsWith('image/')) return;
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const col = document.createElement('div');
            col.className = 'col-4';
            col.innerHTML = `
                <div class="position-relative">
                    <img src="${e.target.result}" class="img-fluid rounded" style="height: 100px; width: 100%; object-fit: cover;">
                </div>
            `;
            preview.appendChild(col);
        };
        reader.readAsDataURL(file);
    });
});
</script>

<?php include VIEWS_PATH . '/admin/layouts/admin_footer.php'; ?>
