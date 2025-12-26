<?php
/**
 * Products Listing Page
 * Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 13.4
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
        </ol>
    </nav>

    <div class="row">
        <!-- Filters Sidebar -->
        <div class="col-lg-3 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Filters</h5>
                </div>
                <div class="card-body">
                    <form method="GET" id="filter-form">
                        <!-- Category Filter (Requirement 1.2) -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Category</label>
                            <?php if (!empty($categories)): ?>
                                <?php foreach ($categories as $cat): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" 
                                               name="category" value="<?= $cat['id'] ?>"
                                               id="cat-<?= $cat['id'] ?>"
                                               <?= ($filters['category_id'] ?? '') == $cat['id'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="cat-<?= $cat['id'] ?>">
                                            <?= htmlspecialchars($cat['name']) ?>
                                        </label>
                                    </div>
                                    <?php if (!empty($cat['children'])): ?>
                                        <?php foreach ($cat['children'] as $child): ?>
                                            <div class="form-check ms-3">
                                                <input class="form-check-input" type="radio" 
                                                       name="category" value="<?= $child['id'] ?>"
                                                       id="cat-<?= $child['id'] ?>"
                                                       <?= ($filters['category_id'] ?? '') == $child['id'] ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="cat-<?= $child['id'] ?>">
                                                    <?= htmlspecialchars($child['name']) ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Price Range Filter (Requirement 1.3) -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Price Range</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="number" class="form-control form-control-sm" 
                                           name="price_min" placeholder="Min"
                                           value="<?= htmlspecialchars($filters['price_min'] ?? '') ?>"
                                           min="<?= $priceRange['min_price'] ?? 0 ?>">
                                </div>
                                <div class="col-6">
                                    <input type="number" class="form-control form-control-sm" 
                                           name="price_max" placeholder="Max"
                                           value="<?= htmlspecialchars($filters['price_max'] ?? '') ?>"
                                           max="<?= $priceRange['max_price'] ?? 10000 ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Origin Filter (Requirement 1.4) -->
                        <?php if (!empty($origins)): ?>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Origin</label>
                            <select class="form-select form-select-sm" name="origin">
                                <option value="">All Origins</option>
                                <?php foreach ($origins as $origin): ?>
                                    <option value="<?= htmlspecialchars($origin) ?>"
                                            <?= ($filters['origin'] ?? '') === $origin ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($origin) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>

                        <!-- Organic Filter (Requirement 1.5) -->
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" 
                                       name="is_organic" value="1" id="organic-filter"
                                       <?= !empty($filters['is_organic']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="organic-filter">
                                    <span class="badge bg-success">Organic Only</span>
                                </label>
                            </div>
                        </div>

                        <!-- In Stock Filter -->
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" 
                                       name="in_stock" value="1" id="stock-filter"
                                       <?= !empty($filters['in_stock']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="stock-filter">
                                    In Stock Only
                                </label>
                            </div>
                        </div>

                        <!-- On Sale Filter -->
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" 
                                       name="on_sale" value="1" id="sale-filter"
                                       <?= !empty($filters['on_sale']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="sale-filter">
                                    On Sale
                                </label>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-sm">Apply Filters</button>
                            <a href="<?= url('/products') ?>" class="btn btn-outline-secondary btn-sm">Clear Filters</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Products Grid -->
        <div class="col-lg-9">
            <!-- Header with sort and results count -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h4 mb-0">
                        <?= isset($category) ? htmlspecialchars($category['name']) : 'All Products' ?>
                    </h1>
                    <small class="text-muted"><?= $total ?> products found</small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <label class="form-label mb-0 me-2">Sort by:</label>
                    <select class="form-select form-select-sm" name="sort" form="filter-form" onchange="this.form.submit()">
                        <option value="newest" <?= ($filters['sort'] ?? '') === 'newest' ? 'selected' : '' ?>>Newest</option>
                        <option value="price_low" <?= ($filters['sort'] ?? '') === 'price_low' ? 'selected' : '' ?>>Price: Low to High</option>
                        <option value="price_high" <?= ($filters['sort'] ?? '') === 'price_high' ? 'selected' : '' ?>>Price: High to Low</option>
                        <option value="name_asc" <?= ($filters['sort'] ?? '') === 'name_asc' ? 'selected' : '' ?>>Name: A-Z</option>
                        <option value="name_desc" <?= ($filters['sort'] ?? '') === 'name_desc' ? 'selected' : '' ?>>Name: Z-A</option>
                    </select>
                </div>
            </div>

            <?php if (empty($products)): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    No products found matching your criteria. Try adjusting your filters.
                </div>
            <?php else: ?>
                <!-- Products Grid with Lazy Loading (Requirement 11.4) -->
                <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
                    <?php foreach ($products as $index => $product): ?>
                        <div class="col">
                            <div class="card h-100 product-card">
                                <!-- Product Image with Lazy Loading -->
                                <a href="<?= url('/products/' . htmlspecialchars($product['slug'])) ?>">
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
                                         loading="<?= $index < 6 ? 'eager' : 'lazy' ?>"
                                         decoding="async"
                                         style="height: 200px; object-fit: cover;"
                                         onerror="this.src='https://placehold.co/300x200/FFF8DC/8B4513?text=Cinnamon'">
                                </a>
                                
                                <!-- Badges -->
                                <div class="position-absolute top-0 start-0 p-2">
                                    <?php if ($product['is_organic']): ?>
                                        <span class="badge bg-success">Organic</span>
                                    <?php endif; ?>
                                    <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                                        <span class="badge bg-danger">Sale</span>
                                    <?php endif; ?>
                                    <?php if ($product['stock_quantity'] <= 0): ?>
                                        <span class="badge bg-secondary">Out of Stock</span>
                                    <?php endif; ?>
                                    <?php if ($isWholesale && !empty($product['is_wholesale_price'])): ?>
                                        <span class="badge bg-info">Wholesale</span>
                                    <?php endif; ?>
                                </div>

                                <div class="card-body">
                                    <p class="text-muted small mb-1">
                                        <?= htmlspecialchars($product['category_name'] ?? '') ?>
                                    </p>
                                    <h5 class="card-title">
                                        <a href="<?= url('/products/' . htmlspecialchars($product['slug'])) ?>" class="text-decoration-none text-dark">
                                            <?= htmlspecialchars($product['name']) ?>
                                        </a>
                                    </h5>
                                    <p class="card-text small text-muted">
                                        <?= htmlspecialchars(substr($product['short_description'] ?? '', 0, 80)) ?>...
                                    </p>
                                    
                                    <!-- Price Display (Requirement 13.4: Wholesale pricing) -->
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <?php if ($isWholesale && !empty($product['is_wholesale_price'])): ?>
                                                <!-- Wholesale Price Display -->
                                                <span class="text-decoration-line-through text-muted small">
                                                    $<?= number_format($product['price'], 2) ?>
                                                </span>
                                                <span class="h5 text-success mb-0 ms-1">
                                                    $<?= number_format($product['wholesale_price'], 2) ?>
                                                </span>
                                                <?php if (!empty($product['savings_percentage']) && $product['savings_percentage'] > 0): ?>
                                                    <span class="badge bg-success ms-1">
                                                        Save <?= $product['savings_percentage'] ?>%
                                                    </span>
                                                <?php endif; ?>
                                            <?php elseif ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                                                <!-- Sale Price Display -->
                                                <span class="text-decoration-line-through text-muted">
                                                    $<?= number_format($product['price'], 2) ?>
                                                </span>
                                                <span class="h5 text-danger mb-0 ms-2">
                                                    $<?= number_format($product['sale_price'], 2) ?>
                                                </span>
                                            <?php else: ?>
                                                <!-- Regular Price Display -->
                                                <span class="h5 mb-0">$<?= number_format($product['price'], 2) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($product['stock_quantity'] > 0): ?>
                                            <button class="btn btn-outline-primary btn-sm add-to-cart" 
                                                    data-product-id="<?= $product['id'] ?>">
                                                <i class="bi bi-cart-plus"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Wholesale Minimum Order Info -->
                                    <?php if ($isWholesale && !empty($product['min_wholesale_qty'])): ?>
                                        <small class="text-muted d-block mt-2">
                                            <i class="bi bi-info-circle"></i>
                                            Min. wholesale order: <?= (int)$product['min_wholesale_qty'] ?> units
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($pages > 1): ?>
                    <nav aria-label="Product pagination" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($currentPage > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page' => $currentPage - 1])) ?>">
                                        Previous
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $currentPage - 2); $i <= min($pages, $currentPage + 2); $i++): ?>
                                <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page' => $i])) ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($currentPage < $pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page' => $currentPage + 1])) ?>">
                                        Next
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include VIEWS_PATH . '/layouts/footer.php'; ?>
