<?php
/**
 * Admin Create Blog Post View
 * Requirements: 8.1 - Blog post creation with categories and tags
 */
include VIEWS_PATH . '/admin/layouts/admin_header.php';
$old = $old ?? [];
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Create Blog Post</h1>
        <a href="<?= url('/admin/content/posts') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Posts
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

    <form method="POST" action="<?= url('/admin/content/posts') ?>" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
        
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Post Content</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   value="<?= htmlspecialchars($old['title'] ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="slug" class="form-label">Slug</label>
                            <input type="text" class="form-control" id="slug" name="slug" 
                                   value="<?= htmlspecialchars($old['slug'] ?? '') ?>"
                                   placeholder="Leave empty to auto-generate">
                        </div>

                        <div class="mb-3">
                            <label for="excerpt" class="form-label">Excerpt</label>
                            <textarea class="form-control" id="excerpt" name="excerpt" rows="3"
                                      placeholder="Brief summary of the post"><?= htmlspecialchars($old['excerpt'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="content" class="form-label">Content <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="content" name="content" rows="15" required><?= htmlspecialchars($old['content'] ?? '') ?></textarea>
                            <small class="text-muted">HTML is allowed for formatting.</small>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">SEO Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="meta_title" class="form-label">Meta Title</label>
                            <input type="text" class="form-control" id="meta_title" name="meta_title" 
                                   value="<?= htmlspecialchars($old['meta_title'] ?? '') ?>"
                                   maxlength="255">
                        </div>

                        <div class="mb-3">
                            <label for="meta_description" class="form-label">Meta Description</label>
                            <textarea class="form-control" id="meta_description" name="meta_description" 
                                      rows="2" maxlength="500"><?= htmlspecialchars($old['meta_description'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Publish</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" onchange="toggleScheduleDate()">
                                <option value="draft" <?= ($old['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                                <option value="published" <?= ($old['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
                                <option value="scheduled" <?= ($old['status'] ?? '') === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                            </select>
                        </div>

                        <div class="mb-3" id="schedule-date-group" style="display: none;">
                            <label for="published_at" class="form-label">Publish Date</label>
                            <input type="datetime-local" class="form-control" id="published_at" name="published_at" 
                                   value="<?= htmlspecialchars($old['published_at'] ?? '') ?>">
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-save"></i> Save Post
                        </button>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Category & Tags</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="category_id" class="form-label">Category</label>
                            <select class="form-select" id="category_id" name="category_id">
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= $category['id'] ?>" 
                                            <?= ($old['category_id'] ?? '') == $category['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($category['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="tags" class="form-label">Tags</label>
                            <input type="text" class="form-control" id="tags" name="tags" 
                                   value="<?= htmlspecialchars($old['tags'] ?? '') ?>"
                                   placeholder="Comma-separated tags">
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Featured Image</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <input type="file" class="form-control" id="featured_image" name="featured_image" 
                                   accept="image/*" onchange="previewImage(this)">
                            <small class="text-muted">Recommended: 1200x630 pixels</small>
                        </div>
                        <div id="image-preview" class="mt-2"></div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function toggleScheduleDate() {
    const status = document.getElementById('status').value;
    const scheduleGroup = document.getElementById('schedule-date-group');
    scheduleGroup.style.display = status === 'scheduled' ? 'block' : 'none';
}

function previewImage(input) {
    const preview = document.getElementById('image-preview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = '<img src="' + e.target.result + '" class="img-fluid rounded" alt="Preview">';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Initialize on page load
toggleScheduleDate();
</script>

<?php include VIEWS_PATH . '/admin/layouts/admin_footer.php'; ?>
