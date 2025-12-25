<?php
/**
 * Public Gallery Page
 * Requirements: 8.3 - Gallery display for images and videos
 */
include VIEWS_PATH . '/layouts/header.php';
?>

<div class="container py-5">
    <div class="text-center mb-5">
        <h1>Gallery</h1>
        <p class="lead text-muted">Explore our cinnamon plantation, production process, and products.</p>
    </div>

    <?php if (empty($items)): ?>
        <div class="alert alert-info text-center">
            Gallery items will be displayed here soon.
        </div>
    <?php else: ?>
        <!-- Filter Tabs -->
        <ul class="nav nav-pills justify-content-center mb-4" id="galleryTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="all-tab" data-bs-toggle="pill" data-bs-target="#all" 
                        type="button" role="tab">All</button>
            </li>
            <?php if (!empty($images)): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="images-tab" data-bs-toggle="pill" data-bs-target="#images" 
                            type="button" role="tab">Images (<?= count($images) ?>)</button>
                </li>
            <?php endif; ?>
            <?php if (!empty($videos)): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="videos-tab" data-bs-toggle="pill" data-bs-target="#videos" 
                            type="button" role="tab">Videos (<?= count($videos) ?>)</button>
                </li>
            <?php endif; ?>
        </ul>

        <div class="tab-content" id="galleryTabContent">
            <!-- All Items -->
            <div class="tab-pane fade show active" id="all" role="tabpanel">
                <div class="row">
                    <?php foreach ($items as $item): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 border-0 shadow-sm gallery-item">
                                <?php if ($item['file_type'] === 'image'): ?>
                                    <a href="<?= htmlspecialchars($item['file_url']) ?>" 
                                       data-lightbox="gallery" data-title="<?= htmlspecialchars($item['title']) ?>">
                                        <img src="<?= htmlspecialchars($item['file_url']) ?>" 
                                             class="card-img-top" alt="<?= htmlspecialchars($item['title']) ?>"
                                             style="height: 220px; object-fit: cover;">
                                    </a>
                                <?php else: ?>
                                    <div class="card-img-top bg-dark d-flex align-items-center justify-content-center" 
                                         style="height: 220px; cursor: pointer;"
                                         onclick="playVideo('<?= htmlspecialchars($item['file_url']) ?>', '<?= htmlspecialchars($item['title']) ?>')">
                                        <div class="text-center text-white">
                                            <i class="bi bi-play-circle" style="font-size: 3rem;"></i>
                                            <p class="mb-0">Click to play</p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <div class="card-body">
                                    <h6 class="card-title"><?= htmlspecialchars($item['title']) ?></h6>
                                    <?php if ($item['description']): ?>
                                        <p class="card-text small text-muted"><?= htmlspecialchars($item['description']) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Images Only -->
            <?php if (!empty($images)): ?>
                <div class="tab-pane fade" id="images" role="tabpanel">
                    <div class="row">
                        <?php foreach ($images as $item): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card h-100 border-0 shadow-sm">
                                    <a href="<?= htmlspecialchars($item['file_url']) ?>" 
                                       data-lightbox="images" data-title="<?= htmlspecialchars($item['title']) ?>">
                                        <img src="<?= htmlspecialchars($item['file_url']) ?>" 
                                             class="card-img-top" alt="<?= htmlspecialchars($item['title']) ?>"
                                             style="height: 220px; object-fit: cover;">
                                    </a>
                                    <div class="card-body">
                                        <h6 class="card-title"><?= htmlspecialchars($item['title']) ?></h6>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Videos Only -->
            <?php if (!empty($videos)): ?>
                <div class="tab-pane fade" id="videos" role="tabpanel">
                    <div class="row">
                        <?php foreach ($videos as $item): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card h-100 border-0 shadow-sm">
                                    <div class="card-img-top bg-dark d-flex align-items-center justify-content-center" 
                                         style="height: 220px; cursor: pointer;"
                                         onclick="playVideo('<?= htmlspecialchars($item['file_url']) ?>', '<?= htmlspecialchars($item['title']) ?>')">
                                        <div class="text-center text-white">
                                            <i class="bi bi-play-circle" style="font-size: 3rem;"></i>
                                            <p class="mb-0">Click to play</p>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <h6 class="card-title"><?= htmlspecialchars($item['title']) ?></h6>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Video Modal -->
<div class="modal fade" id="videoModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="videoModalTitle">Video</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <video id="videoPlayer" controls class="w-100">
                    <source src="" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
            </div>
        </div>
    </div>
</div>

<script>
function playVideo(url, title) {
    const video = document.getElementById('videoPlayer');
    const source = video.querySelector('source');
    source.src = url;
    video.load();
    document.getElementById('videoModalTitle').textContent = title;
    new bootstrap.Modal(document.getElementById('videoModal')).show();
}

document.getElementById('videoModal').addEventListener('hidden.bs.modal', function () {
    const video = document.getElementById('videoPlayer');
    video.pause();
    video.currentTime = 0;
});
</script>

<?php include VIEWS_PATH . '/layouts/footer.php'; ?>
