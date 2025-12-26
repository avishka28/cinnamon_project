<?php
/**
 * Public Blog Post Detail View
 * Requirements: 8.5 - Published content available to customers
 */
include VIEWS_PATH . '/layouts/header.php';
?>

<article class="py-5">
    <div class="container">
        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Post Header -->
                <header class="mb-4">
                    <?php if (!empty($post['category_name'])): ?>
                        <a href="<?= url('/blog/category/' . htmlspecialchars($post['category_slug'])) ?>" 
                           class="badge bg-primary text-decoration-none mb-2">
                            <?= htmlspecialchars($post['category_name']) ?>
                        </a>
                    <?php endif; ?>
                    
                    <h1 class="mb-3"><?= htmlspecialchars($post['title']) ?></h1>
                    
                    <div class="text-muted mb-4">
                        <span>By <?= htmlspecialchars($post['author_name'] ?? 'Admin') ?></span>
                        <span class="mx-2">•</span>
                        <span><?= date('F j, Y', strtotime($post['published_at'] ?? $post['created_at'])) ?></span>
                    </div>
                </header>

                <!-- Featured Image -->
                <figure class="mb-4">
                    <?php 
                    $blogImage = !empty($post['featured_image']) && file_exists(PUBLIC_PATH . '/' . $post['featured_image']) 
                        ? url('/' . $post['featured_image']) 
                        : 'https://placehold.co/800x400/FFF8DC/8B4513?text=' . urlencode($post['title']);
                    ?>
                    <img src="<?= $blogImage ?>" 
                         class="img-fluid rounded shadow-sm" 
                         alt="<?= htmlspecialchars($post['title']) ?>">
                </figure>

                <!-- Post Content -->
                <div class="post-content mb-5">
                    <?= $post['content'] ?>
                </div>

                <!-- Tags -->
                <?php if ($post['tags']): ?>
                    <div class="mb-4">
                        <strong>Tags:</strong>
                        <?php foreach (explode(',', $post['tags']) as $tag): ?>
                            <span class="badge bg-secondary"><?= htmlspecialchars(trim($tag)) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Share Buttons -->
                <div class="border-top border-bottom py-3 mb-5">
                    <strong>Share:</strong>
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($_SERVER['REQUEST_URI']) ?>" 
                       target="_blank" class="btn btn-sm btn-outline-primary ms-2">
                        <i class="bi bi-facebook"></i> Facebook
                    </a>
                    <a href="https://twitter.com/intent/tweet?url=<?= urlencode($_SERVER['REQUEST_URI']) ?>&text=<?= urlencode($post['title']) ?>" 
                       target="_blank" class="btn btn-sm btn-outline-info ms-2">
                        <i class="bi bi-twitter"></i> Twitter
                    </a>
                </div>

                <!-- Related Posts -->
                <?php if (!empty($relatedPosts)): ?>
                    <section class="mb-5">
                        <h3 class="mb-4">Related Posts</h3>
                        <div class="row">
                            <?php foreach ($relatedPosts as $related): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="card h-100 border-0 shadow-sm">
                                        <?php 
                                        $relatedImage = !empty($related['featured_image']) && file_exists(PUBLIC_PATH . '/' . $related['featured_image']) 
                                            ? url('/' . $related['featured_image']) 
                                            : 'https://placehold.co/400x120/FFF8DC/8B4513?text=' . urlencode($related['title']);
                                        ?>
                                        <img src="<?= $relatedImage ?>" 
                                             class="card-img-top" alt="<?= htmlspecialchars($related['title']) ?>"
                                             style="height: 120px; object-fit: cover;">
                                        <div class="card-body">
                                            <h6 class="card-title">
                                                <a href="<?= url('/blog/' . htmlspecialchars($related['slug'])) ?>" 
                                                   class="text-decoration-none text-dark">
                                                    <?= htmlspecialchars($related['title']) ?>
                                                </a>
                                            </h6>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- Navigation -->
                <nav class="d-flex justify-content-between">
                    <a href="<?= url('/blog') ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Blog
                    </a>
                </nav>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Categories -->
                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Categories</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($categories)): ?>
                            <p class="text-muted mb-0">No categories yet.</p>
                        <?php else: ?>
                            <ul class="list-unstyled mb-0">
                                <?php foreach ($categories as $category): ?>
                                    <li class="mb-2">
                                        <a href="<?= url('/blog/category/' . htmlspecialchars($category['slug'])) ?>" 
                                           class="text-decoration-none">
                                            <?= htmlspecialchars($category['name']) ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Posts -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Recent Posts</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recentPosts)): ?>
                            <p class="text-muted mb-0">No posts yet.</p>
                        <?php else: ?>
                            <ul class="list-unstyled mb-0">
                                <?php foreach ($recentPosts as $recent): ?>
                                    <li class="mb-3 pb-3 border-bottom">
                                        <a href="<?= url('/blog/' . htmlspecialchars($recent['slug'])) ?>" 
                                           class="text-decoration-none text-dark">
                                            <?= htmlspecialchars($recent['title']) ?>
                                        </a>
                                        <br>
                                        <small class="text-muted">
                                            <?= date('M j, Y', strtotime($recent['published_at'] ?? $recent['created_at'])) ?>
                                        </small>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</article>

<?php include VIEWS_PATH . '/layouts/footer.php'; ?>
