<?php
/**
 * Public Blog Listing View
 * Requirements: 8.5 - Published content available to customers
 */
include VIEWS_PATH . '/layouts/header.php';
?>

<div class="container py-5">
    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <h1 class="mb-4">Our Blog</h1>
            <p class="lead text-muted mb-5">Discover the world of Ceylon cinnamon - recipes, health benefits, and more.</p>

            <?php if (empty($posts)): ?>
                <div class="alert alert-info">
                    No blog posts available yet. Check back soon!
                </div>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <article class="card mb-4 border-0 shadow-sm">
                        <?php if ($post['featured_image']): ?>
                            <a href="/blog/<?= htmlspecialchars($post['slug']) ?>">
                                <img src="<?= htmlspecialchars($post['featured_image']) ?>" 
                                     class="card-img-top" alt="<?= htmlspecialchars($post['title']) ?>"
                                     style="height: 250px; object-fit: cover;">
                            </a>
                        <?php endif; ?>
                        <div class="card-body">
                            <div class="mb-2">
                                <?php if ($post['category_name']): ?>
                                    <a href="/blog/category/<?= htmlspecialchars($post['category_slug']) ?>" 
                                       class="badge bg-primary text-decoration-none">
                                        <?= htmlspecialchars($post['category_name']) ?>
                                    </a>
                                <?php endif; ?>
                                <small class="text-muted ms-2">
                                    <?= date('F j, Y', strtotime($post['published_at'] ?? $post['created_at'])) ?>
                                </small>
                            </div>
                            <h2 class="card-title h4">
                                <a href="/blog/<?= htmlspecialchars($post['slug']) ?>" class="text-decoration-none text-dark">
                                    <?= htmlspecialchars($post['title']) ?>
                                </a>
                            </h2>
                            <?php if ($post['excerpt']): ?>
                                <p class="card-text text-muted"><?= htmlspecialchars($post['excerpt']) ?></p>
                            <?php endif; ?>
                            <a href="/blog/<?= htmlspecialchars($post['slug']) ?>" class="btn btn-outline-primary btn-sm">
                                Read More <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <small class="text-muted">By <?= htmlspecialchars($post['author_name']) ?></small>
                        </div>
                    </article>
                <?php endforeach; ?>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav class="mt-5">
                        <ul class="pagination justify-content-center">
                            <?php if ($currentPage > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="/blog?page=<?= $currentPage - 1 ?>">Previous</a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                                    <a class="page-link" href="/blog?page=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($currentPage < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="/blog?page=<?= $currentPage + 1 ?>">Next</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
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
                                    <a href="/blog/category/<?= htmlspecialchars($category['slug']) ?>" 
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
                                    <a href="/blog/<?= htmlspecialchars($recent['slug']) ?>" 
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

<?php include VIEWS_PATH . '/layouts/footer.php'; ?>
