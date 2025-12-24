<?php
/**
 * Product Detail Page
 * Requirements: 1.6 (images, videos, descriptions, specifications, certificates, related products)
 * Requirements: 1.7 (customer reviews and ratings)
 */
include VIEWS_PATH . '/layouts/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">Home</a></li>
            <li class="breadcrumb-item"><a href="/products">Products</a></li>
            <?php if (!empty($breadcrumb)): ?>
                <?php foreach ($breadcrumb as $crumb): ?>
                    <li class="breadcrumb-item">
                        <a href="/category/<?= htmlspecialchars($crumb['slug']) ?>">
                            <?= htmlspecialchars($crumb['name']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
            <li class="breadcrumb-item active"><?= htmlspecialchars($product['name']) ?></li>
        </ol>
    </nav>

    <div class="row">
        <!-- Product Images (Requirement 1.6) -->
        <div class="col-lg-6 mb-4">
            <div class="product-gallery">
                <!-- Main Image -->
                <div class="main-image mb-3">
                    <?php 
                    $primaryImage = !empty($product['images']) ? $product['images'][0] : null;
                    $imageSrc = $primaryImage ? '/uploads/products/' . $primaryImage['image_url'] : '/assets/images/placeholder.jpg';
                    ?>
                    <img src="<?= htmlspecialchars($imageSrc) ?>" 
                         class="img-fluid rounded" 
                         alt="<?= htmlspecialchars($product['name']) ?>"
                         id="main-product-image">
                </div>
                
                <!-- Thumbnail Gallery -->
                <?php if (!empty($product['images']) && count($product['images']) > 1): ?>
                    <div class="row g-2">
                        <?php foreach ($product['images'] as $image): ?>
                            <div class="col-3">
                                <img src="/uploads/products/<?= htmlspecialchars($image['image_url']) ?>" 
                                     class="img-fluid rounded thumbnail-image" 
                                     alt="<?= htmlspecialchars($image['alt_text'] ?? $product['name']) ?>"
                                     onclick="document.getElementById('main-product-image').src = this.src"
                                     style="cursor: pointer;">
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Product Info -->
        <div class="col-lg-6">
            <!-- Badges -->
            <div class="mb-2">
                <?php if ($product['is_organic']): ?>
                    <span class="badge bg-success">Certified Organic</span>
                <?php endif; ?>
                <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                    <span class="badge bg-danger">On Sale</span>
                <?php endif; ?>
            </div>

            <h1 class="h2 mb-3"><?= htmlspecialchars($product['name']) ?></h1>

            <!-- Rating (Requirement 1.7) -->
            <?php if ($product['review_count'] > 0): ?>
                <div class="mb-3">
                    <div class="d-flex align-items-center">
                        <div class="text-warning me-2">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php if ($i <= $product['average_rating']): ?>
                                    <i class="bi bi-star-fill"></i>
                                <?php elseif ($i - 0.5 <= $product['average_rating']): ?>
                                    <i class="bi bi-star-half"></i>
                                <?php else: ?>
                                    <i class="bi bi-star"></i>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                        <span class="text-muted">
                            <?= $product['average_rating'] ?> (<?= $product['review_count'] ?> reviews)
                        </span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Price -->
            <div class="mb-4">
                <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                    <span class="text-decoration-line-through text-muted h5">
                        $<?= number_format($product['price'], 2) ?>
                    </span>
                    <span class="h3 text-danger ms-2">
                        $<?= number_format($product['sale_price'], 2) ?>
                    </span>
                    <span class="badge bg-danger ms-2">
                        Save <?= round((($product['price'] - $product['sale_price']) / $product['price']) * 100) ?>%
                    </span>
                <?php else: ?>
                    <span class="h3">$<?= number_format($product['price'], 2) ?></span>
                <?php endif; ?>
            </div>

            <!-- Short Description -->
            <?php if ($product['short_description']): ?>
                <p class="lead"><?= htmlspecialchars($product['short_description']) ?></p>
            <?php endif; ?>

            <!-- Stock Status -->
            <div class="mb-4">
                <?php if ($product['stock_quantity'] > 0): ?>
                    <span class="text-success">
                        <i class="bi bi-check-circle me-1"></i>
                        In Stock (<?= $product['stock_quantity'] ?> available)
                    </span>
                <?php else: ?>
                    <span class="text-danger">
                        <i class="bi bi-x-circle me-1"></i>
                        Out of Stock
                    </span>
                <?php endif; ?>
            </div>

            <!-- Add to Cart -->
            <?php if ($product['stock_quantity'] > 0): ?>
                <form class="mb-4" id="add-to-cart-form">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                    <div class="row g-3 align-items-center">
                        <div class="col-auto">
                            <label class="form-label mb-0">Quantity:</label>
                        </div>
                        <div class="col-auto">
                            <input type="number" class="form-control" name="quantity" 
                                   value="1" min="1" max="<?= $product['stock_quantity'] ?>" style="width: 80px;">
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-cart-plus me-2"></i>Add to Cart
                            </button>
                        </div>
                    </div>
                </form>
            <?php endif; ?>

            <!-- Specifications (Requirement 1.6) -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Specifications</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tbody>
                            <tr>
                                <th scope="row" style="width: 40%;">SKU</th>
                                <td><?= htmlspecialchars($product['sku']) ?></td>
                            </tr>
                            <?php if ($product['weight']): ?>
                                <tr>
                                    <th scope="row">Weight</th>
                                    <td><?= htmlspecialchars($product['weight']) ?> kg</td>
                                </tr>
                            <?php endif; ?>
                            <?php if ($product['dimensions']): ?>
                                <tr>
                                    <th scope="row">Dimensions</th>
                                    <td><?= htmlspecialchars($product['dimensions']) ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if ($product['origin']): ?>
                                <tr>
                                    <th scope="row">Origin</th>
                                    <td><?= htmlspecialchars($product['origin']) ?></td>
                                </tr>
                            <?php endif; ?>
                            <tr>
                                <th scope="row">Category</th>
                                <td>
                                    <a href="/category/<?= htmlspecialchars($product['category_slug']) ?>">
                                        <?= htmlspecialchars($product['category_name']) ?>
                                    </a>
                                </td>
                            </tr>
                            <?php if ($product['is_organic']): ?>
                                <tr>
                                    <th scope="row">Certification</th>
                                    <td><span class="badge bg-success">Certified Organic</span></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Full Description (Requirement 1.6) -->
    <div class="row mt-4">
        <div class="col-12">
            <ul class="nav nav-tabs" id="productTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="description-tab" data-bs-toggle="tab" 
                            data-bs-target="#description" type="button" role="tab">
                        Description
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="reviews-tab" data-bs-toggle="tab" 
                            data-bs-target="#reviews" type="button" role="tab">
                        Reviews (<?= $product['review_count'] ?>)
                    </button>
                </li>
            </ul>
            <div class="tab-content p-4 border border-top-0 rounded-bottom" id="productTabsContent">
                <!-- Description Tab -->
                <div class="tab-pane fade show active" id="description" role="tabpanel">
                    <?php if ($product['description']): ?>
                        <div class="product-description">
                            <?= nl2br(htmlspecialchars($product['description'])) ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No detailed description available.</p>
                    <?php endif; ?>
                </div>

                <!-- Reviews Tab (Requirement 1.7) -->
                <div class="tab-pane fade" id="reviews" role="tabpanel">
                    <?php if (!empty($product['reviews'])): ?>
                        <div class="reviews-list">
                            <?php foreach ($product['reviews'] as $review): ?>
                                <div class="review-item border-bottom pb-3 mb-3">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <strong>
                                                <?= htmlspecialchars(($review['first_name'] ?? 'Anonymous') . ' ' . ($review['last_name'] ?? '')[0] ?? '') ?>.
                                            </strong>
                                            <div class="text-warning">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="bi bi-star<?= $i <= $review['rating'] ? '-fill' : '' ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                        <small class="text-muted">
                                            <?= date('M d, Y', strtotime($review['created_at'])) ?>
                                        </small>
                                    </div>
                                    <?php if ($review['review_text']): ?>
                                        <p class="mt-2 mb-0"><?= nl2br(htmlspecialchars($review['review_text'])) ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No reviews yet. Be the first to review this product!</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Related Products (Requirement 1.6) -->
    <?php if (!empty($relatedProducts)): ?>
        <div class="row mt-5">
            <div class="col-12">
                <h3 class="mb-4">Related Products</h3>
                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
                    <?php foreach ($relatedProducts as $related): ?>
                        <div class="col">
                            <div class="card h-100 product-card">
                                <a href="/products/<?= htmlspecialchars($related['slug']) ?>">
                                    <img src="/uploads/products/<?= htmlspecialchars($related['slug']) ?>.jpg" 
                                         class="card-img-top" 
                                         alt="<?= htmlspecialchars($related['name']) ?>"
                                         onerror="this.src='/assets/images/placeholder.jpg'">
                                </a>
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <a href="/products/<?= htmlspecialchars($related['slug']) ?>" 
                                           class="text-decoration-none text-dark">
                                            <?= htmlspecialchars($related['name']) ?>
                                        </a>
                                    </h6>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <?php if ($related['sale_price'] && $related['sale_price'] < $related['price']): ?>
                                            <span class="text-danger fw-bold">
                                                $<?= number_format($related['sale_price'], 2) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="fw-bold">$<?= number_format($related['price'], 2) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.getElementById('add-to-cart-form')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('/api/cart/add', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Product added to cart!');
            // Update cart count in header if exists
            const cartCount = document.getElementById('cart-count');
            if (cartCount) {
                cartCount.textContent = data.cart_count;
            }
        } else {
            alert(data.error || 'Failed to add product to cart');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    });
});
</script>

<?php include VIEWS_PATH . '/layouts/footer.php'; ?>
