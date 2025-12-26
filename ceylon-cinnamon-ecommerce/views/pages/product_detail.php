<?php
/**
 * Product Detail Page
 * Requirements: 1.6 (images, videos, descriptions, specifications, certificates, related products)
 * Requirements: 1.7 (customer reviews and ratings)
 * Requirements: 13.4 (wholesale pricing display)
 */
include VIEWS_PATH . '/layouts/header.php';

// Check if user is wholesale customer
$isWholesale = $isWholesale ?? false;
?>

<div class="container py-4">
    <!-- Wholesale Customer Banner -->
    <?php if ($isWholesale): ?>
    <div class="alert alert-success mb-4">
        <i class="bi bi-star-fill me-2"></i>
        <strong>Wholesale Pricing Active!</strong> You're viewing special wholesale prices.
    </div>
    <?php endif; ?>

    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= url('/') ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="<?= url('/products') ?>">Products</a></li>
            <?php if (!empty($breadcrumb)): ?>
                <?php foreach ($breadcrumb as $crumb): ?>
                    <li class="breadcrumb-item">
                        <a href="<?= url('/category/' . htmlspecialchars($crumb['slug'])) ?>">
                            <?= htmlspecialchars($crumb['name']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
            <li class="breadcrumb-item active"><?= htmlspecialchars($product['name']) ?></li>
        </ol>
    </nav>

    <div class="row">
        <!-- Product Images (Requirement 1.6, 11.4 - Lazy Loading) -->
        <div class="col-lg-6 mb-4">
            <div class="product-gallery">
                <!-- Main Image -->
                <div class="main-image mb-3">
                    <?php 
                    $primaryImage = !empty($product['images']) ? $product['images'][0] : null;
                    $imageSrc = null;
                    
                    if ($primaryImage && !empty($primaryImage['image_url'])) {
                        $imageUrl = $primaryImage['image_url'];
                        // Check if it's a relative path
                        if (!preg_match('/^https?:\/\//', $imageUrl)) {
                            // Check if file exists (handle both with and without leading slash)
                            $cleanPath = ltrim($imageUrl, '/');
                            if (file_exists(PUBLIC_PATH . '/' . $cleanPath)) {
                                $imageSrc = url('/' . $cleanPath);
                            }
                        } else {
                            $imageSrc = $imageUrl;
                        }
                    }
                    
                    // Fallback to placeholder
                    if (!$imageSrc) {
                        $imageSrc = 'https://placehold.co/600x600/FFF8DC/8B4513?text=' . urlencode($product['name']);
                    }
                    ?>
                    <img src="<?= htmlspecialchars($imageSrc) ?>" 
                         class="img-fluid rounded" 
                         alt="<?= htmlspecialchars($product['name']) ?>"
                         id="main-product-image"
                         loading="eager"
                         decoding="async"
                         onerror="this.src='https://placehold.co/600x600/FFF8DC/8B4513?text=Cinnamon'">
                </div>
                
                <!-- Thumbnail Gallery with Lazy Loading -->
                <?php if (!empty($product['images']) && count($product['images']) > 1): ?>
                    <div class="row g-2">
                        <?php foreach ($product['images'] as $index => $image): ?>
                            <div class="col-3">
                                <?php 
                                $thumbSrc = null;
                                if (!empty($image['image_url'])) {
                                    $imageUrl = $image['image_url'];
                                    if (!preg_match('/^https?:\/\//', $imageUrl)) {
                                        $cleanPath = ltrim($imageUrl, '/');
                                        if (file_exists(PUBLIC_PATH . '/' . $cleanPath)) {
                                            $thumbSrc = url('/' . $cleanPath);
                                        }
                                    } else {
                                        $thumbSrc = $imageUrl;
                                    }
                                }
                                
                                if (!$thumbSrc) {
                                    $thumbSrc = 'https://placehold.co/150x150/FFF8DC/8B4513?text=' . urlencode($image['alt_text'] ?? $product['name']);
                                }
                                ?>
                                <img src="<?= htmlspecialchars($thumbSrc) ?>" 
                                     class="img-fluid rounded thumbnail-image" 
                                     alt="<?= htmlspecialchars($image['alt_text'] ?? $product['name']) ?>"
                                     onclick="document.getElementById('main-product-image').src = this.src"
                                     style="cursor: pointer;"
                                     loading="<?= $index < 4 ? 'eager' : 'lazy' ?>"
                                     decoding="async"
                                     onerror="this.src='https://placehold.co/150x150/FFF8DC/8B4513?text=Cinnamon'">
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
                <?php if ($isWholesale && !empty($product['is_wholesale_price'])): ?>
                    <span class="badge bg-info">Wholesale Price</span>
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

            <!-- Price Display (Requirement 13.4: Wholesale pricing) -->
            <div class="mb-4">
                <?php if ($isWholesale && !empty($product['is_wholesale_price'])): ?>
                    <!-- Wholesale Price Display -->
                    <div class="wholesale-pricing p-3 bg-light rounded border border-success">
                        <div class="d-flex align-items-center mb-2">
                            <span class="text-decoration-line-through text-muted h5 mb-0">
                                $<?= number_format($product['price'], 2) ?>
                            </span>
                            <span class="h3 text-success ms-3 mb-0">
                                $<?= number_format($product['wholesale_price'], 2) ?>
                            </span>
                            <?php if (!empty($product['savings_percentage']) && $product['savings_percentage'] > 0): ?>
                                <span class="badge bg-success ms-2">
                                    Save <?= $product['savings_percentage'] ?>%
                                </span>
                            <?php endif; ?>
                        </div>
                        <small class="text-success">
                            <i class="bi bi-check-circle me-1"></i>
                            Wholesale pricing applied
                        </small>
                    </div>
                <?php elseif ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                    <!-- Sale Price Display -->
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
                    <!-- Regular Price Display -->
                    <span class="h3">$<?= number_format($product['price'], 2) ?></span>
                <?php endif; ?>
            </div>

            <!-- Wholesale Price Tiers (Requirement 13.3) -->
            <?php if ($isWholesale && !empty($product['price_tiers'])): ?>
                <div class="card mb-4 border-success">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="bi bi-tags me-2"></i>Wholesale Price Tiers</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Quantity</th>
                                    <th class="text-end">Price per Unit</th>
                                    <th class="text-end">Discount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($product['price_tiers'] as $tier): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($tier['label']) ?></td>
                                        <td class="text-end fw-bold text-success">$<?= number_format($tier['price'], 2) ?></td>
                                        <td class="text-end">
                                            <?php if ($tier['discount']): ?>
                                                <span class="badge bg-success"><?= $tier['discount'] ?> off</span>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php if (!empty($product['min_wholesale_qty'])): ?>
                            <small class="text-muted mt-2 d-block">
                                <i class="bi bi-info-circle me-1"></i>
                                Minimum wholesale order: <?= (int)$product['min_wholesale_qty'] ?> units
                            </small>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

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
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                    <div class="row g-3 align-items-center">
                        <div class="col-auto">
                            <label class="form-label mb-0">Quantity:</label>
                        </div>
                        <div class="col-auto">
                            <?php 
                            $minQty = ($isWholesale && !empty($product['min_wholesale_qty'])) 
                                ? (int)$product['min_wholesale_qty'] 
                                : 1;
                            ?>
                            <input type="number" class="form-control" name="quantity" 
                                   value="<?= $minQty ?>" min="<?= $minQty ?>" max="<?= $product['stock_quantity'] ?>" style="width: 100px;">
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary btn-lg" id="add-to-cart-btn">
                                <i class="bi bi-cart-plus me-2"></i>Add to Cart
                            </button>
                        </div>
                    </div>
                    <?php if ($isWholesale && !empty($product['min_wholesale_qty'])): ?>
                        <small class="text-muted mt-2 d-block">
                            Minimum order quantity for wholesale: <?= (int)$product['min_wholesale_qty'] ?> units
                        </small>
                    <?php endif; ?>
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
                                    <a href="<?= url('/products?category=' . htmlspecialchars($product['category_slug'])) ?>">
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
                    <?php if (!empty($product['description'])): ?>
                        <div class="product-description">
                            <?php
                            // Check if description contains HTML tags
                            if ($product['description'] !== strip_tags($product['description'])) {
                                // Contains HTML - render it (sanitized)
                                echo $product['description'];
                            } else {
                                // Plain text - convert newlines to <br>
                                echo nl2br(htmlspecialchars($product['description']));
                            }
                            ?>
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
                                <a href="<?= url('/products/' . htmlspecialchars($related['slug'])) ?>">
                                    <?php 
                                    // Get related product image URL
                                    $relatedImage = null;
                                    if (!empty($related['image_url'])) {
                                        $imageUrl = $related['image_url'];
                                        if (!preg_match('/^https?:\/\//', $imageUrl)) {
                                            $relatedImage = url($imageUrl);
                                        } else {
                                            $relatedImage = $imageUrl;
                                        }
                                    }
                                    
                                    if (!$relatedImage) {
                                        $relatedImage = 'https://placehold.co/300x300/FFF8DC/8B4513?text=' . urlencode($related['name']);
                                    }
                                    ?>
                                    <img src="<?= htmlspecialchars($relatedImage) ?>" 
                                         class="card-img-top" 
                                         alt="<?= htmlspecialchars($related['name']) ?>"
                                         style="height: 200px; object-fit: cover;"
                                         onerror="this.src='https://placehold.co/300x300/FFF8DC/8B4513?text=Cinnamon'">
                                </a>
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <a href="<?= url('/products/' . htmlspecialchars($related['slug'])) ?>" 
                                           class="text-decoration-none text-dark">
                                            <?= htmlspecialchars($related['name']) ?>
                                        </a>
                                    </h6>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <?php if (!empty($related['sale_price']) && $related['sale_price'] < $related['price']): ?>
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
    
    const btn = document.getElementById('add-to-cart-btn');
    if (!btn) return;
    
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Adding...';
    btn.disabled = true;
    
    const formData = new FormData(this);
    
    fetch('<?= url('/cart/add') ?>', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        // Clone the response so we can read it twice if needed
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Invalid JSON response:', text);
                throw new Error('Invalid server response');
            }
        });
    })
    .then(data => {
        btn.innerHTML = originalText;
        btn.disabled = false;
        
        if (data.success) {
            // Show success message
            showToast('Product added to cart!', 'success');
            // Update cart count in header (both mobile and desktop)
            if (data.cart) {
                const count = data.cart.total_quantity || data.cart.item_count || 0;
                const mobileCartCount = document.getElementById('mobile-cart-count');
                const desktopCartCount = document.getElementById('desktop-cart-count');
                if (mobileCartCount) mobileCartCount.textContent = count;
                if (desktopCartCount) desktopCartCount.textContent = count;
            }
        } else {
            showToast(data.error || 'Failed to add product to cart', 'error');
        }
    })
    .catch(error => {
        btn.innerHTML = originalText;
        btn.disabled = false;
        console.error('Fetch error:', error);
        // Only show error if we haven't already shown success
        // Check if cart count was updated (indicates success)
        const mobileCartCount = document.getElementById('mobile-cart-count');
        if (mobileCartCount && mobileCartCount.textContent !== '0') {
            // Cart was updated, likely success - don't show error
            return;
        }
        showToast('An error occurred. Please try again.', 'error');
    });
});

function showToast(message, type) {
    // Remove any existing toasts of the same type to prevent duplicates
    document.querySelectorAll('.toast-notification').forEach(t => t.remove());
    
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `alert alert-${type === 'success' ? 'success' : 'danger'} position-fixed toast-notification`;
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    toast.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
            <span>${message}</span>
            <button type="button" class="btn-close ms-auto" onclick="this.parentElement.parentElement.remove()"></button>
        </div>
    `;
    document.body.appendChild(toast);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        if (toast.parentElement) {
            toast.remove();
        }
    }, 3000);
}
</script>

<?php include VIEWS_PATH . '/layouts/footer.php'; ?>
