<?php
/**
 * Admin Edit Certificate View
 * Requirements: 8.2 - Certificate management
 */
include VIEWS_PATH . '/admin/layouts/admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Edit Certificate</h1>
        <a href="<?= url('/admin/content/certificates') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Certificates
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
                    <form method="POST" action="<?= url('/admin/content/certificates/' . $certificate['id']) ?>" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        
                        <div class="mb-3">
                            <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   value="<?= htmlspecialchars($certificate['title']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" 
                                      rows="3"><?= htmlspecialchars($certificate['description'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Current File</label>
                            <div class="p-3 bg-light rounded">
                                <?php if ($certificate['file_type'] === 'image'): ?>
                                    <img src="<?= htmlspecialchars($certificate['file_url']) ?>" 
                                         class="img-fluid rounded" style="max-height: 200px;" 
                                         alt="<?= htmlspecialchars($certificate['title']) ?>">
                                <?php else: ?>
                                    <a href="<?= htmlspecialchars($certificate['file_url']) ?>" target="_blank" class="btn btn-outline-primary">
                                        <i class="bi bi-file-pdf"></i> View PDF
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="certificate_file" class="form-label">Replace File</label>
                            <input type="file" class="form-control" id="certificate_file" name="certificate_file" 
                                   accept=".pdf,.jpg,.jpeg,.png,.gif" onchange="previewFile(this)">
                            <small class="text-muted">Leave empty to keep current file. Allowed: PDF, JPEG, PNG, GIF</small>
                        </div>
                        <div id="file-preview" class="mb-3"></div>

                        <div class="mb-3">
                            <label for="sort_order" class="form-label">Sort Order</label>
                            <input type="number" class="form-control" id="sort_order" name="sort_order" 
                                   value="<?= htmlspecialchars($certificate['sort_order']) ?>" min="0">
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="is_active" name="is_active" 
                                       value="1" <?= $certificate['is_active'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Update Certificate
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
    if (input.files && input.files[0]) {
        const file = input.files[0];
        if (file.type === 'application/pdf') {
            preview.innerHTML = '<div class="alert alert-info"><i class="bi bi-file-pdf"></i> New PDF: ' + file.name + '</div>';
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
