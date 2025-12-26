<?php
/**
 * Admin Edit Gallery Item View
 * Requirements: 8.3 - Gallery management
 */
include VIEWS_PATH . '/admin/layouts/admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Edit Gallery Item</h1>
        <a href="<?= url('/admin/content/gallery') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Gallery
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

    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="<?= url('/admin/content/gallery/' . $item['id']) ?>" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        
                        <div class="mb-3">
                            <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   value="<?= htmlspecialchars($item['title']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" 
                                      rows="3"><?= htmlspecialchars($item['description'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Current File</label>
                            <div class="p-3 bg-dark rounded">
                                <?php if ($item['file_type'] === 'image'): ?>
                                    <img src="<?= htmlspecialchars($item['file_url']) ?>" 
                                         class="img-fluid rounded" style="max-height: 200px;" 
                                         alt="<?= htmlspecialchars($item['title']) ?>">
                                <?php else: ?>
                                    <video controls class="w-100" style="max-height: 200px;">
                                        <source src="<?= htmlspecialchars($item['file_url']) ?>" type="video/mp4">
                                    </video>
                                <?php endif; ?>
                            </div>
                            <small class="text-muted">Type: <?= ucfirst($item['file_type']) ?></small>
                        </div>

                        <div class="mb-3">
                            <label for="gallery_file" class="form-label">Replace File</label>
                            <input type="file" class="form-control" id="gallery_file" name="gallery_file" 
                                   accept="<?= $item['file_type'] === 'video' ? 'video/mp4,video/webm,video/ogg' : 'image/*' ?>"
                                   onchange="previewFile(this)">
                            <small class="text-muted">
                                Leave empty to keep current file. 
                                <?= $item['file_type'] === 'video' ? 'Allowed: MP4, WebM, OGG' : 'Allowed: JPEG, PNG, GIF, WebP' ?>
                            </small>
                        </div>
                        <div id="file-preview" class="mb-3"></div>

                        <div class="mb-3">
                            <label for="sort_order" class="form-label">Sort Order</label>
                            <input type="number" class="form-control" id="sort_order" name="sort_order" 
                                   value="<?= htmlspecialchars($item['sort_order']) ?>" min="0">
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="is_active" name="is_active" 
                                       value="1" <?= $item['is_active'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Update Item
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function previewFile(input) {
    const preview = document.getElementById('file-preview');
    const fileType = '<?= $item['file_type'] ?>';
    
    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        if (fileType === 'video') {
            preview.innerHTML = '<div class="alert alert-info"><i class="bi bi-film"></i> New Video: ' + file.name + '</div>';
        } else {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.innerHTML = '<img src="' + e.target.result + '" class="img-fluid rounded" style="max-height: 200px;" alt="Preview">';
            };
            reader.readAsDataURL(file);
        }
    }
}
</script>

<?php include VIEWS_PATH . '/admin/layouts/admin_footer.php'; ?>
