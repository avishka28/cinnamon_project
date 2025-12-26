<?php
/**
 * Wholesale Page
 * Requirements: 13.1 (inquiry form), 13.3 (price tiers)
 */
include VIEWS_PATH . '/layouts/header.php';
?>

<!-- Hero Section -->
<section class="bg-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="display-5 fw-bold mb-3">Wholesale Ceylon Cinnamon</h1>
                <p class="lead mb-0">Partner with us for premium quality Ceylon cinnamon at competitive wholesale prices. We supply retailers, distributors, and food manufacturers worldwide.</p>
            </div>
            <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">
                <a href="#inquiry-form" class="btn btn-light btn-lg">
                    <i class="bi bi-envelope me-2"></i>Request Quote
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Benefits Section -->
<section class="py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <div class="text-center">
                    <div class="feature-icon bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <i class="bi bi-percent text-primary display-6"></i>
                    </div>
                    <h5>Volume Discounts</h5>
                    <p class="text-muted small">Save up to 30% with our tiered wholesale pricing structure.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="text-center">
                    <div class="feature-icon bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <i class="bi bi-patch-check text-primary display-6"></i>
                    </div>
                    <h5>Premium Quality</h5>
                    <p class="text-muted small">Certified organic Ceylon cinnamon from Sri Lanka.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="text-center">
                    <div class="feature-icon bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <i class="bi bi-globe text-primary display-6"></i>
                    </div>
                    <h5>Global Shipping</h5>
                    <p class="text-muted small">We ship to retailers and distributors worldwide.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="text-center">
                    <div class="feature-icon bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <i class="bi bi-headset text-primary display-6"></i>
                    </div>
                    <h5>Dedicated Support</h5>
                    <p class="text-muted small">Personal account manager for all wholesale clients.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Wholesale Products with Price Tiers -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Wholesale Price Tiers</h2>
            <p class="text-muted">Volume-based pricing for our most popular products</p>
        </div>
        
        <?php if (!empty($wholesaleProducts)): ?>
        <div class="row g-4">
            <?php foreach ($wholesaleProducts as $product): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="card-title mb-1"><?= htmlspecialchars($product['name']) ?></h5>
                        <small class="text-muted">Retail Price: $<?= number_format((float)$product['price'], 2) ?></small>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($product['price_tiers'])): ?>
                        <table class="table table-sm table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                    <th>Savings</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($product['price_tiers'] as $tier): ?>
                                <tr>
                                    <td><?= htmlspecialchars($tier['label']) ?></td>
                                    <td class="text-success fw-bold">$<?= number_format($tier['price'], 2) ?></td>
                                    <td>
                                        <?php if (!empty($tier['discount'])): ?>
                                        <span class="badge bg-success"><?= htmlspecialchars($tier['discount']) ?> off</span>
                                        <?php else: ?>
                                        <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <p class="text-muted mb-0 text-center py-3">
                            <i class="bi bi-info-circle me-2"></i>Contact us for wholesale pricing
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-box-seam display-4 text-muted mb-3"></i>
            <p class="text-muted">Contact us for wholesale pricing on all our products.</p>
            <a href="#inquiry-form" class="btn btn-primary">Request Quote</a>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Inquiry Form Section -->
<section class="py-5" id="inquiry-form">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0"><i class="bi bi-envelope me-2"></i>Wholesale Inquiry Form</h3>
                    </div>
                    <div class="card-body p-4">
                        <?php if (!empty($success)): ?>
                        <div class="alert alert-success">
                            <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success) ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-circle me-2"></i><?= $error ?>
                        </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="<?= url('/wholesale') ?>">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                            
                            <div class="row g-3">
                                <!-- Company Information -->
                                <div class="col-12">
                                    <h5 class="border-bottom pb-2 mb-3">Company Information</h5>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="company_name" class="form-label">Company Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="company_name" name="company_name" 
                                           value="<?= htmlspecialchars($formData['company_name'] ?? '') ?>" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="business_type" class="form-label">Business Type</label>
                                    <select class="form-select" id="business_type" name="business_type">
                                        <option value="">Select...</option>
                                        <option value="retailer" <?= ($formData['business_type'] ?? '') === 'retailer' ? 'selected' : '' ?>>Retailer</option>
                                        <option value="distributor" <?= ($formData['business_type'] ?? '') === 'distributor' ? 'selected' : '' ?>>Distributor</option>
                                        <option value="manufacturer" <?= ($formData['business_type'] ?? '') === 'manufacturer' ? 'selected' : '' ?>>Food Manufacturer</option>
                                        <option value="restaurant" <?= ($formData['business_type'] ?? '') === 'restaurant' ? 'selected' : '' ?>>Restaurant/Cafe</option>
                                        <option value="other" <?= ($formData['business_type'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
                                    </select>
                                </div>
                                
                                <!-- Contact Information -->
                                <div class="col-12 mt-4">
                                    <h5 class="border-bottom pb-2 mb-3">Contact Information</h5>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="contact_name" class="form-label">Contact Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="contact_name" name="contact_name" 
                                           value="<?= htmlspecialchars($formData['contact_name'] ?? '') ?>" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?= htmlspecialchars($formData['email'] ?? '') ?>" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?= htmlspecialchars($formData['phone'] ?? '') ?>">
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="country" class="form-label">Country</label>
                                    <input type="text" class="form-control" id="country" name="country" 
                                           value="<?= htmlspecialchars($formData['country'] ?? '') ?>">
                                </div>
                                
                                <!-- Order Details -->
                                <div class="col-12 mt-4">
                                    <h5 class="border-bottom pb-2 mb-3">Order Details</h5>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="products_interested" class="form-label">Products Interested In</label>
                                    <select class="form-select" id="products_interested" name="products_interested">
                                        <option value="">Select...</option>
                                        <option value="cinnamon_sticks" <?= ($formData['products_interested'] ?? '') === 'cinnamon_sticks' ? 'selected' : '' ?>>Cinnamon Sticks</option>
                                        <option value="cinnamon_powder" <?= ($formData['products_interested'] ?? '') === 'cinnamon_powder' ? 'selected' : '' ?>>Cinnamon Powder</option>
                                        <option value="cinnamon_oil" <?= ($formData['products_interested'] ?? '') === 'cinnamon_oil' ? 'selected' : '' ?>>Cinnamon Oil</option>
                                        <option value="cinnamon_tea" <?= ($formData['products_interested'] ?? '') === 'cinnamon_tea' ? 'selected' : '' ?>>Cinnamon Tea</option>
                                        <option value="multiple" <?= ($formData['products_interested'] ?? '') === 'multiple' ? 'selected' : '' ?>>Multiple Products</option>
                                        <option value="all" <?= ($formData['products_interested'] ?? '') === 'all' ? 'selected' : '' ?>>All Products</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="estimated_quantity" class="form-label">Estimated Monthly Quantity</label>
                                    <select class="form-select" id="estimated_quantity" name="estimated_quantity">
                                        <option value="">Select...</option>
                                        <option value="10-50" <?= ($formData['estimated_quantity'] ?? '') === '10-50' ? 'selected' : '' ?>>10-50 units</option>
                                        <option value="50-100" <?= ($formData['estimated_quantity'] ?? '') === '50-100' ? 'selected' : '' ?>>50-100 units</option>
                                        <option value="100-500" <?= ($formData['estimated_quantity'] ?? '') === '100-500' ? 'selected' : '' ?>>100-500 units</option>
                                        <option value="500+" <?= ($formData['estimated_quantity'] ?? '') === '500+' ? 'selected' : '' ?>>500+ units</option>
                                    </select>
                                </div>
                                
                                <div class="col-12">
                                    <label for="message" class="form-label">Additional Information</label>
                                    <textarea class="form-control" id="message" name="message" rows="4" 
                                              placeholder="Tell us about your business needs, specific requirements, or any questions you have..."><?= htmlspecialchars($formData['message'] ?? '') ?></textarea>
                                </div>
                                
                                <div class="col-12 mt-4">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="bi bi-send me-2"></i>Submit Inquiry
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Frequently Asked Questions</h2>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="accordion" id="wholesaleFaq">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                What is the minimum order quantity?
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#wholesaleFaq">
                            <div class="accordion-body">
                                Our minimum wholesale order is typically 10 units per product. However, we can discuss flexible arrangements based on your business needs.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                Do you offer private labeling?
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#wholesaleFaq">
                            <div class="accordion-body">
                                Yes, we offer private labeling services for orders above 100 units. Contact us to discuss your branding requirements.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                What certifications do your products have?
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#wholesaleFaq">
                            <div class="accordion-body">
                                Our products are certified organic (USDA, EU), ISO 22000 certified, and Fair Trade certified. We can provide all necessary documentation for your market.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                How long does shipping take?
                            </button>
                        </h2>
                        <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#wholesaleFaq">
                            <div class="accordion-body">
                                Shipping times vary by destination. Typically, orders are processed within 2-3 business days, with delivery taking 7-21 days depending on your location.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include VIEWS_PATH . '/layouts/footer.php'; ?>
