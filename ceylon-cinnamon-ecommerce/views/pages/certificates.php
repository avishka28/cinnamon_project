<?php
/**
 * Public Certificates Page
 * Requirements: 8.2 - Certificate display
 */
include VIEWS_PATH . '/layouts/header.php';
?>

<div class="container py-5">
    <div class="text-center mb-5">
        <h1>Our Certificates</h1>
        <p class="lead text-muted">Quality certifications that guarantee the authenticity and purity of our Ceylon cinnamon products.</p>
    </div>

    <?php if (empty($certificates)): ?>
        <div class="alert alert-info text-center">
            Certificates will be displayed here soon.
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($certificates as $cert): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                            <?php if ($cert['file_type'] === 'image'): ?>
                                <a href="<?= htmlspecialchars($cert['file_url']) ?>" target="_blank">
                                    <img src="<?= htmlspecialchars($cert['file_url']) ?>" 
                                         alt="<?= htmlspecialchars($cert['title']) ?>"
                                         class="img-fluid" style="max-height: 200px;">
                                </a>
                            <?php else: ?>
                                <a href="<?= htmlspecialchars($cert['file_url']) ?>" target="_blank" class="text-center">
                                    <i class="bi bi-file-pdf text-danger" style="font-size: 4rem;"></i>
                                    <p class="mb-0 text-muted">Click to view PDF</p>
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="card-body text-center">
                            <h5 class="card-title"><?= htmlspecialchars($cert['title']) ?></h5>
                            <?php if ($cert['description']): ?>
                                <p class="card-text text-muted"><?= htmlspecialchars($cert['description']) ?></p>
                            <?php endif; ?>
                            <a href="<?= htmlspecialchars($cert['file_url']) ?>" 
                               target="_blank" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-eye"></i> View Certificate
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="text-center mt-5">
        <p class="text-muted">
            All our products are certified and tested to ensure the highest quality standards.
        </p>
    </div>
</div>

<?php include VIEWS_PATH . '/layouts/footer.php'; ?>
