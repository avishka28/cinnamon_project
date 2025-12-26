<?php
/**
 * Home Page
 * Requirements: 1.1 (product display), 11.5 (responsive design)
 */
include VIEWS_PATH . '/layouts/header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <span class="badge bg-success mb-3"><?= t('home.badge') ?? '100% Authentic Ceylon Cinnamon' ?></span>
                <h1 class="display-4 fw-bold mb-3">
                    <?= t('home.hero_title') ?? 'Experience the True Taste of Ceylon Cinnamon' ?>
                </h1>
                <p class="lead text-muted mb-4">
                    <?= t('home.hero_description') ?? 'Premium quality cinnamon products sourced directly from the lush plantations of Sri Lanka. Discover the authentic flavor and health benefits of true cinnamon.' ?>
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="<?= url('/products') ?>" class="btn btn-primary btn-lg">
                        <i class="bi bi-bag me-2"></i><?= t('home.shop_now') ?? 'Shop Now' ?>
                    </a>
                    <a href="<?= url('/about') ?>" class="btn btn-outline-primary btn-lg">
                        <?= t('home.learn_more') ?? 'Learn More' ?>
                    </a>
                </div>
                
                <!-- Trust Badges -->
                <div class="d-flex flex-wrap gap-4 mt-4 pt-3">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-truck text-primary fs-4 me-2"></i>
                        <small class="text-muted"><?= t('home.free_shipping') ?? 'Free Shipping $50+' ?></small>
                    </div>
                    <div class="d-flex align-items-center">
                        <i class="bi bi-shield-check text-primary fs-4 me-2"></i>
                        <small class="text-muted"><?= t('home.secure_payment') ?? 'Secure Payment' ?></small>
                    </div>
                    <div class="d-flex align-items-center">
                        <i class="bi bi-award text-primary fs-4 me-2"></i>
                        <small class="text-muted"><?= t('home.certified') ?? 'Certified Organic' ?></small>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="hero-image text-center">
                    <img src="<?= url('/assets/images/hero-cinnamon.png') ?>" 
                         alt="Ceylon Cinnamon Products" 
                         class="img-fluid"
                         loading="eager"
                         onerror="this.src='https://via.placeholder.com/600x500/FFF8DC/8B4513?text=Ceylon+Cinnamon'">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="py-5 bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold"><?= t('home.categories_title') ?? 'Shop by Category' ?></h2>
            <p class="text-muted"><?= t('home.categories_subtitle') ?? 'Explore our range of premium Ceylon cinnamon products' ?></p>
        </div>
        
        <div class="row g-4">
            <?php
            $categories = [
                ['name' => 'Cinnamon Sticks', 'slug' => 'cinnamon-sticks', 'icon' => 'bi-tree', 'desc' => 'Premium quality cinnamon quills'],
                ['name' => 'Cinnamon Powder', 'slug' => 'cinnamon-powder', 'icon' => 'bi-cup-hot', 'desc' => 'Finely ground for cooking & baking'],
                ['name' => 'Cinnamon Oil', 'slug' => 'cinnamon-oil', 'icon' => 'bi-droplet', 'desc' => 'Pure essential oils'],
                ['name' => 'Cinnamon Tea', 'slug' => 'cinnamon-tea', 'icon' => 'bi-cup', 'desc' => 'Aromatic herbal blends'],
            ];
            foreach ($categories as $cat): ?>
            <div class="col-6 col-md-3">
                <a href="<?= url('/products?category=' . $cat['slug']) ?>" class="text-decoration-none">
                    <div class="card h-100 text-center category-card-home border-0 shadow-sm">
                        <div class="card-body py-4">
                            <div class="category-icon mb-3">
                                <i class="bi <?= $cat['icon'] ?> display-4 text-primary"></i>
                            </div>
                            <h5 class="card-title text-dark"><?= htmlspecialchars($cat['name']) ?></h5>
                            <p class="card-text small text-muted d-none d-md-block"><?= htmlspecialchars($cat['desc']) ?></p>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Featured Products Section -->
<section class="featured-products py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-1"><?= t('home.featured_title') ?? 'Featured Products' ?></h2>
                <p class="text-muted mb-0"><?= t('home.featured_subtitle') ?? 'Our most popular Ceylon cinnamon products' ?></p>
            </div>
            <a href="<?= url('/products') ?>" class="btn btn-outline-primary d-none d-md-inline-flex">
                <?= t('home.view_all') ?? 'View All' ?> <i class="bi bi-arrow-right ms-2"></i>
            </a>
        </div>
        
        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3 g-md-4">
            <?php if (!empty($featuredProducts)): ?>
                <?php foreach ($featuredProducts as $product): ?>
                <div class="col">
                    <div class="card h-100 product-card">
                        <a href="<?= url('/products/' . htmlspecialchars($product['slug'])) ?>">
                            <div class="position-relative overflow-hidden">
                                <?php 
                                // Get product image URL
                                $imageUrl = null;
                                if (!empty($product['image_url'])) {
                                    $imageUrl = $product['image_url'];
                                } elseif (!empty($product['primary_image'])) {
                                    $imageUrl = $product['primary_image'];
                                }
                                
                                // Check if it's a relative path and prepend base URL
                                if ($imageUrl && !preg_match('/^https?:\/\//', $imageUrl)) {
                                    $imageUrl = url($imageUrl);
                                }
                                
                                // Fallback to placeholder
                                if (!$imageUrl) {
                                    $imageUrl = 'https://placehold.co/300x200/FFF8DC/8B4513?text=' . urlencode($product['name']);
                                }
                                ?>
                                <img src="<?= htmlspecialchars($imageUrl) ?>" 
                                     class="card-img-top" 
                                     alt="<?= htmlspecialchars($product['name']) ?>"
                                     loading="lazy"
                                     style="height: 200px; object-fit: cover;"
                                     onerror="this.src='https://placehold.co/300x200/FFF8DC/8B4513?text=Cinnamon'">
                                <?php if (!empty($product['is_organic'])): ?>
                                <span class="badge bg-success position-absolute top-0 start-0 m-2">Organic</span>
                                <?php endif; ?>
                                <?php if (!empty($product['sale_price']) && $product['sale_price'] < $product['price']): ?>
                                <span class="badge bg-danger position-absolute top-0 end-0 m-2">Sale</span>
                                <?php endif; ?>
                            </div>
                        </a>
                        <div class="card-body">
                            <p class="text-muted small mb-1"><?= htmlspecialchars($product['category_name'] ?? '') ?></p>
                            <h6 class="card-title mb-2">
                                <a href="<?= url('/products/' . htmlspecialchars($product['slug'])) ?>" class="text-decoration-none text-dark">
                                    <?= htmlspecialchars($product['name']) ?>
                                </a>
                            </h6>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <?php if (!empty($product['sale_price']) && $product['sale_price'] < $product['price']): ?>
                                    <span class="text-decoration-line-through text-muted small">$<?= number_format($product['price'], 2) ?></span>
                                    <span class="text-danger fw-bold">$<?= number_format($product['sale_price'], 2) ?></span>
                                    <?php else: ?>
                                    <span class="fw-bold">$<?= number_format($product['price'], 2) ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($product['stock_quantity'] > 0): ?>
                                <button class="btn btn-sm btn-outline-primary add-to-cart" data-product-id="<?= $product['id'] ?>">
                                    <i class="bi bi-cart-plus"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Placeholder products when no data -->
                <?php for ($i = 0; $i < 4; $i++): ?>
                <div class="col">
                    <div class="card h-100 product-card">
                        <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                            <i class="bi bi-image text-muted display-4"></i>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small mb-1">Category</p>
                            <h6 class="card-title mb-2">Product Name</h6>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold">$0.00</span>
                                <button class="btn btn-sm btn-outline-primary" disabled>
                                    <i class="bi bi-cart-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endfor; ?>
            <?php endif; ?>
        </div>
        
        <div class="text-center mt-4 d-md-none">
            <a href="<?= url('/products') ?>" class="btn btn-outline-primary">
                <?= t('home.view_all') ?? 'View All Products' ?> <i class="bi bi-arrow-right ms-2"></i>
            </a>
        </div>
    </div>
</section>

<!-- Why Choose Us Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold"><?= t('home.why_title') ?? 'Why Choose Ceylon Cinnamon?' ?></h2>
            <p class="text-muted"><?= t('home.why_subtitle') ?? 'The finest cinnamon from the pearl of the Indian Ocean' ?></p>
        </div>
        
        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <div class="text-center">
                    <div class="feature-icon bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <i class="bi bi-geo-alt text-primary display-6"></i>
                    </div>
                    <h5><?= t('home.feature1_title') ?? 'Direct from Sri Lanka' ?></h5>
                    <p class="text-muted small"><?= t('home.feature1_desc') ?? 'Sourced directly from certified plantations in Sri Lanka' ?></p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="text-center">
                    <div class="feature-icon bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <i class="bi bi-patch-check text-primary display-6"></i>
                    </div>
                    <h5><?= t('home.feature2_title') ?? 'Premium Quality' ?></h5>
                    <p class="text-muted small"><?= t('home.feature2_desc') ?? 'Hand-selected and processed to maintain the highest quality' ?></p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="text-center">
                    <div class="feature-icon bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <i class="bi bi-heart-pulse text-primary display-6"></i>
                    </div>
                    <h5><?= t('home.feature3_title') ?? 'Health Benefits' ?></h5>
                    <p class="text-muted small"><?= t('home.feature3_desc') ?? 'True cinnamon with lower coumarin levels for daily use' ?></p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="text-center">
                    <div class="feature-icon bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <i class="bi bi-globe text-primary display-6"></i>
                    </div>
                    <h5><?= t('home.feature4_title') ?? 'Worldwide Shipping' ?></h5>
                    <p class="text-muted small"><?= t('home.feature4_desc') ?? 'Fast and secure delivery to your doorstep globally' ?></p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold"><?= t('home.testimonials_title') ?? 'What Our Customers Say' ?></h2>
        </div>
        
        <div class="row g-4">
            <?php
            $testimonials = [
                ['name' => 'Sarah M.', 'location' => 'New York, USA', 'text' => 'The best cinnamon I\'ve ever tasted! The aroma is incredible and it makes my baking so much better.', 'rating' => 5],
                ['name' => 'James L.', 'location' => 'London, UK', 'text' => 'Finally found authentic Ceylon cinnamon. The quality is outstanding and shipping was fast.', 'rating' => 5],
                ['name' => 'Maria G.', 'location' => 'Sydney, AU', 'text' => 'I use this cinnamon daily in my morning coffee. Love the subtle sweetness and health benefits!', 'rating' => 5],
            ];
            foreach ($testimonials as $testimonial): ?>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="text-warning mb-3">
                            <?php for ($i = 0; $i < $testimonial['rating']; $i++): ?>
                            <i class="bi bi-star-fill"></i>
                            <?php endfor; ?>
                        </div>
                        <p class="card-text mb-3">"<?= htmlspecialchars($testimonial['text']) ?>"</p>
                        <div class="d-flex align-items-center">
                            <div class="avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
                                <?= strtoupper(substr($testimonial['name'], 0, 1)) ?>
                            </div>
                            <div>
                                <h6 class="mb-0"><?= htmlspecialchars($testimonial['name']) ?></h6>
                                <small class="text-muted"><?= htmlspecialchars($testimonial['location']) ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-5 bg-primary text-white">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8 mb-4 mb-lg-0">
                <h2 class="fw-bold mb-2"><?= t('home.cta_title') ?? 'Ready to Experience True Ceylon Cinnamon?' ?></h2>
                <p class="mb-0 opacity-75"><?= t('home.cta_subtitle') ?? 'Join thousands of satisfied customers worldwide. Order now and taste the difference!' ?></p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <a href="<?= url('/products') ?>" class="btn btn-light btn-lg">
                    <i class="bi bi-bag me-2"></i><?= t('home.shop_now') ?? 'Shop Now' ?>
                </a>
            </div>
        </div>
    </div>
</section>

<?php include VIEWS_PATH . '/layouts/footer.php'; ?>
