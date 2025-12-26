<?php
/**
 * Admin Gallery List View
 * Requirements: 8.3 - Gallery management for images and videos
 */
include VIEWS_PATH . '/admin/layouts/admin_header.php';
$successFlash = $sessionManager->getFlash('success');
$errorFlash = $sessionManager->getFlash('error');
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Gallery</h1>
        <a href="<?= url('/admin/content/gallery/create') ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Add Item
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
            <?php if (empty($items)): ?>
                <p class="text-muted text-center py-4">No gallery items found.</p>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($items as $item): ?>
                        <div class="col-md-4 col-lg-3 mb-4">
                            <div class="card h-100">
                                <div class="card-img-top bg-dark d-flex align-items-center justify-content-center" style="height: 180px; overflow: hidden;">
                                    <?php if ($item['file_type'] === 'image'): ?>
                                        <img src="<?= htmlspecialchars($item['file_url']) ?>" 
                                             alt="<?= htmlspecialchars($item['title']) ?>"
                                             class="img-fluid" style="object-fit: cover; width: 100%; height: 100%;">
                                    <?php else: ?>
                                        <div class="text-center text-white">
                                            <i class="bi bi-play-circle" style="font-size: 3rem;"></i>
                                            <p class="mb-0 small">Video</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body">
                                    <h6 class="card-title"><?= htmlspecialchars($item['title']) ?></h6>
                                    <p class="card-text small text-muted">
                                        <span class="badge bg-<?= $item['file_type'] === 'image' ? 'info' : 'warning' ?>">
                                            <?= ucfirst($item['file_type']) ?>
                                        </span>
                                        <?php if (!$item['is_active']): ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <?php if ($item['file_type'] === 'image'): ?>
                                        <a href="<?= htmlspecialchars($item['file_url']) ?>" 
                                           class="btn btn-sm btn-outline-secondary" target="_blank" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" 
                                                onclick="playVideo('<?= htmlspecialchars($item['file_url']) ?>')" title="Play">
                                            <i class="bi bi-play"></i>
                                        </button>
                                    <?php endif; ?>
                                    <a href="<?= url('/admin/content/gallery/' . $item['id'] . '/edit') ?>" 
                                       class="btn btn-sm btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                            onclick="deleteItem(<?= $item['id'] ?>)" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Video Modal -->
<div class="modal fade" id="videoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Video Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <video id="videoPlayer" controls class="w-100">
                    <source src="" type="video/mp4">
                </video>
            </div>
        </div>
    </div>
</div>

<script>
function deleteItem(id) {
    if (confirm('Are you sure you want to delete this item?')) {
        fetch('<?= url('/admin/content/gallery/') ?>' + id, {
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
                alert(data.error || 'Failed to delete item');
            }
        })
        .catch(() => alert('Failed to delete item'));
    }
}

function playVideo(url) {
    const video = document.getElementById('videoPlayer');
    video.querySelector('source').src = url;
    video.load();
    new bootstrap.Modal(document.getElementById('videoModal')).show();
}

document.getElementById('videoModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('videoPlayer').pause();
});
</script>

<?php include VIEWS_PATH . '/admin/layouts/admin_footer.php'; ?>
