<?php
/**
 * Admin Certificates List View
 * Requirements: 8.2 - Certificate management
 */
include VIEWS_PATH . '/admin/layouts/admin_header.php';
$successFlash = $sessionManager->getFlash('success');
$errorFlash = $sessionManager->getFlash('error');
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Certificates</h1>
        <a href="<?= url('/admin/content/certificates/create') ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Add Certificate
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
            <?php if (empty($certificates)): ?>
                <p class="text-muted text-center py-4">No certificates found.</p>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($certificates as $cert): ?>
                        <div class="col-md-4 col-lg-3 mb-4">
                            <div class="card h-100">
                                <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 150px;">
                                    <?php if ($cert['file_type'] === 'image'): ?>
                                        <img src="<?= htmlspecialchars($cert['file_url']) ?>" 
                                             alt="<?= htmlspecialchars($cert['title']) ?>"
                                             class="img-fluid" style="max-height: 150px;">
                                    <?php else: ?>
                                        <i class="bi bi-file-pdf text-danger" style="font-size: 4rem;"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body">
                                    <h6 class="card-title"><?= htmlspecialchars($cert['title']) ?></h6>
                                    <p class="card-text small text-muted">
                                        <?= $cert['file_type'] === 'pdf' ? 'PDF Document' : 'Image' ?>
                                    </p>
                                    <?php if (!$cert['is_active']): ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <a href="<?= htmlspecialchars($cert['file_url']) ?>" 
                                       class="btn btn-sm btn-outline-secondary" target="_blank" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="<?= url('/admin/content/certificates/' . $cert['id'] . '/edit') ?>" 
                                       class="btn btn-sm btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                            onclick="deleteCertificate(<?= $cert['id'] ?>)" title="Delete">
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

<script>
function deleteCertificate(id) {
    if (confirm('Are you sure you want to delete this certificate?')) {
        fetch('<?= url('/admin/content/certificates/') ?>' + id, {
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
                alert(data.error || 'Failed to delete certificate');
            }
        })
        .catch(() => alert('Failed to delete certificate'));
    }
}
</script>

<?php include VIEWS_PATH . '/admin/layouts/admin_footer.php'; ?>
