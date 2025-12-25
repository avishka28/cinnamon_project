<?php
/**
 * About Us Page
 * Requirements: 11.5 (responsive design)
 */
include VIEWS_PATH . '/layouts/header.php';
?>

<!-- Hero Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= url('/') ?>"><?= t('nav.home') ?></a></li>
                        <li class="breadcrumb-item active"><?= t('nav.about') ?></li>
                    </ol>
                </nav>
                <h1 class="display-5 fw-bold mb-3"><?= t('about.title') ?? 'Our Story' ?></h1>
                <p class="lead text-muted">
                    <?= t('about.intro') ?? 'For generations, our family has been cultivating the finest Ceylon cinnamon in the lush highlands of Sri Lanka. We bring this heritage directly to your table.' ?>
                </p>
            </div>
            <div class="col-lg-6">
                <img src="<?= url('/assets/images/about-hero.jpg') ?>" 
                     alt="Ceylon Cinnamon Plantation" 
                     class="img-fluid rounded-3 shadow"
                     loading="eager"
                     onerror="this.src='https://via.placeholder.com/600x400/FFF8DC/8B4513?text=Ceylon+Plantation'">
            </div>
        </div>
    </div>
</section>

<!-- Our Mission -->
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <h2 class="fw-bold mb-4"><?= t('about.mission_title') ?? 'Our Mission' ?></h2>
                <p class="lead">
                    <?= t('about.mission_text') ?? 'To share the authentic taste and health benefits of true Ceylon cinnamon with the world, while supporting sustainable farming practices and the livelihoods of Sri Lankan farmers.' ?>
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Why Ceylon Cinnamon -->
<section class="py-5 bg-white">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 order-lg-2 mb-4 mb-lg-0">
                <img src="<?= url('/assets/images/cinnamon-difference.jpg') ?>" 
                     alt="Ceylon vs Cassia Cinnamon" 
                     class="img-fluid rounded-3"
                     loading="lazy"
                     onerror="this.src='https://via.placeholder.com/500x400/FFF8DC/8B4513?text=True+Cinnamon'">
            </div>
            <div class="col-lg-6 order-lg-1">
                <h2 class="fw-bold mb-4"><?= t('about.why_title') ?? 'Why Ceylon Cinnamon?' ?></h2>
                <p class="text-muted mb-4">
                    <?= t('about.why_intro') ?? 'Not all cinnamon is created equal. Ceylon cinnamon, also known as "true cinnamon," is distinctly different from the more common Cassia variety.' ?>
                </p>
                <ul class="list-unstyled">
                    <li class="d-flex mb-3">
                        <i class="bi bi-check-circle-fill text-success me-3 mt-1"></i>
                        <div>
                            <strong><?= t('about.benefit1_title') ?? 'Lower Coumarin Levels' ?></strong>
                            <p class="text-muted small mb-0"><?= t('about.benefit1_desc') ?? 'Safe for daily consumption with significantly lower coumarin content' ?></p>
                        </div>
                    </li>
                    <li class="d-flex mb-3">
                        <i class="bi bi-check-circle-fill text-success me-3 mt-1"></i>
                        <div>
                            <strong><?= t('about.benefit2_title') ?? 'Delicate Flavor' ?></strong>
                            <p class="text-muted small mb-0"><?= t('about.benefit2_desc') ?? 'Subtle, sweet taste perfect for both sweet and savory dishes' ?></p>
                        </div>
                    </li>
                    <li class="d-flex mb-3">
                        <i class="bi bi-check-circle-fill text-success me-3 mt-1"></i>
                        <div>
                            <strong><?= t('about.benefit3_title') ?? 'Health Benefits' ?></strong>
                            <p class="text-muted small mb-0"><?= t('about.benefit3_desc') ?? 'Rich in antioxidants with anti-inflammatory properties' ?></p>
                        </div>
                    </li>
                    <li class="d-flex">
                        <i class="bi bi-check-circle-fill text-success me-3 mt-1"></i>
                        <div>
                            <strong><?= t('about.benefit4_title') ?? 'Premium Quality' ?></strong>
                            <p class="text-muted small mb-0"><?= t('about.benefit4_desc') ?? 'Thin, papery bark that\'s easy to grind and use' ?></p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- Our Process -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold"><?= t('about.process_title') ?? 'From Farm to Your Table' ?></h2>
            <p class="text-muted"><?= t('about.process_subtitle') ?? 'Our commitment to quality at every step' ?></p>
        </div>
        
        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <div class="text-center">
                    <div class="step-number bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px; font-size: 1.5rem; font-weight: bold;">1</div>
                    <h5><?= t('about.step1_title') ?? 'Cultivation' ?></h5>
                    <p class="text-muted small"><?= t('about.step1_desc') ?? 'Grown in the ideal climate of Sri Lanka\'s hill country' ?></p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="text-center">
                    <div class="step-number bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px; font-size: 1.5rem; font-weight: bold;">2</div>
                    <h5><?= t('about.step2_title') ?? 'Harvesting' ?></h5>
                    <p class="text-muted small"><?= t('about.step2_desc') ?? 'Hand-harvested by skilled artisans using traditional methods' ?></p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="text-center">
                    <div class="step-number bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px; font-size: 1.5rem; font-weight: bold;">3</div>
                    <h5><?= t('about.step3_title') ?? 'Processing' ?></h5>
                    <p class="text-muted small"><?= t('about.step3_desc') ?? 'Carefully processed and graded for premium quality' ?></p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="text-center">
                    <div class="step-number bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px; font-size: 1.5rem; font-weight: bold;">4</div>
                    <h5><?= t('about.step4_title') ?? 'Delivery' ?></h5>
                    <p class="text-muted small"><?= t('about.step4_desc') ?? 'Shipped fresh to preserve aroma and flavor' ?></p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Certifications -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold"><?= t('about.cert_title') ?? 'Our Certifications' ?></h2>
            <p class="text-muted"><?= t('about.cert_subtitle') ?? 'Quality and authenticity you can trust' ?></p>
        </div>
        
        <div class="row justify-content-center g-4">
            <div class="col-6 col-md-3 text-center">
                <div class="p-4 bg-light rounded-3">
                    <i class="bi bi-patch-check display-4 text-success mb-3"></i>
                    <h6>Organic Certified</h6>
                </div>
            </div>
            <div class="col-6 col-md-3 text-center">
                <div class="p-4 bg-light rounded-3">
                    <i class="bi bi-globe display-4 text-primary mb-3"></i>
                    <h6>ISO 22000</h6>
                </div>
            </div>
            <div class="col-6 col-md-3 text-center">
                <div class="p-4 bg-light rounded-3">
                    <i class="bi bi-shield-check display-4 text-info mb-3"></i>
                    <h6>HACCP</h6>
                </div>
            </div>
            <div class="col-6 col-md-3 text-center">
                <div class="p-4 bg-light rounded-3">
                    <i class="bi bi-award display-4 text-warning mb-3"></i>
                    <h6>GMP Certified</h6>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <a href="<?= url('/certificates') ?>" class="btn btn-outline-primary">
                <?= t('about.view_certificates') ?? 'View All Certificates' ?> <i class="bi bi-arrow-right ms-2"></i>
            </a>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="py-5 bg-primary text-white">
    <div class="container text-center">
        <h2 class="fw-bold mb-3"><?= t('about.cta_title') ?? 'Experience the Difference' ?></h2>
        <p class="mb-4 opacity-75"><?= t('about.cta_text') ?? 'Try our premium Ceylon cinnamon and taste the authentic flavor of Sri Lanka.' ?></p>
        <a href="<?= url('/products') ?>" class="btn btn-light btn-lg">
            <i class="bi bi-bag me-2"></i><?= t('home.shop_now') ?? 'Shop Now' ?>
        </a>
    </div>
</section>

<?php include VIEWS_PATH . '/layouts/footer.php'; ?>
