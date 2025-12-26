<?php
/**
 * Admin Create Gallery Item View
 * Requirements: 8.3 - Gallery management for images and videos
 */
include VIEWS_PATH . '/admin/layouts/admin_header.php';
$old = $old ?? [];
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Add Gallery Item</h1>
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
                    <form method="POST" action="<?= url('/admin/content/gallery') ?>" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        
                        <div class="mb-3">
                            <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   value="<?= htmlspecialchars($old['title'] ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" 
                                      rows="3"><?= htmlspecialchars($old['description'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="file_type" class="form-label">File Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="file_type" name="file_type" onchange="updateAcceptTypes()">
                                <option value="image" <?= ($old['file_type'] ?? 'image') === 'image' ? 'selected' : '' ?>>Image</option>
                                <option value="video" <?= ($old['file_type'] ?? '') === 'video' ? 'selected' : '' ?>>Video</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="gallery_file" class="form-label">File <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" id="gallery_file" name="gallery_file" 
                                   accept="image/*" required onchange="previewFile(this)">
                            <small class="text-muted" id="file-hint">Allowed: JPEG, PNG, GIF, WebP</small>
                        </div>
                        <div id="file-preview" class="mb-3"></div>

                        <div class="mb-3">
                            <label for="sort_order" class="form-label">Sort Order</label>
                            <input type="number" class="form-control" id="sort_order" name="sort_order" 
                                   value="<?= htmlspecialchars($old['sort_order'] ?? '0') ?>" min="0">
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="is_active" name="is_active" 
                                       value="1" <?= ($old['is_active'] ?? 1) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Add Item
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateAcceptTypes() {
    const fileType = document.getElementById('file_type').value;
    const fileInput = document.getElementById('gallery_file');
    const hint = document.getElementById('file-hint');
    
    if (fileType === 'video') {
        fileInput.accept = 'video/mp4,video/webm,video/ogg';
        hint.textContent = 'Allowed: MP4, WebM, OGG';
    } else {
        fileInput.accept = 'image/*';
        hint.textContent = 'Allowed: JPEG, PNG, GIF, WebP';
    }
    
    document.getElementById('file-preview').innerHTML = '';
}

function previewFile(input) {
    const preview = document.getElementById('file-preview');
    const fileType = document.getElementById('file_type').value;
    
    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        if (fileType === 'video') {
            preview.innerHTML = '<div class="alert alert-info"><i class="bi bi-film"></i> Video: ' + file.name + '</div>';
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
