<?php
/**
 * Terms of Service Page
 */
include VIEWS_PATH . '/layouts/header.php';
?>

<section class="py-5">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= url('/') ?>"><?= t('nav.home') ?></a></li>
                <li class="breadcrumb-item active"><?= t('footer.terms') ?></li>
            </ol>
        </nav>
        
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h1 class="fw-bold mb-4"><?= t('terms.title') ?? 'Terms of Service' ?></h1>
                <p class="text-muted mb-4"><?= t('terms.last_updated') ?? 'Last updated: ' . date('F d, Y') ?></p>
                
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <h4>1. Acceptance of Terms</h4>
                        <p>By accessing and using this website, you accept and agree to be bound by these Terms of Service. If you do not agree to these terms, please do not use our website.</p>
                        
                        <h4 class="mt-4">2. Products and Pricing</h4>
                        <p>All products are subject to availability. We reserve the right to modify prices without prior notice. Prices displayed are in USD unless otherwise specified.</p>
                        
                        <h4 class="mt-4">3. Orders and Payment</h4>
                        <ul>
                            <li>All orders are subject to acceptance and availability</li>
                            <li>Payment must be received before order processing</li>
                            <li>We accept major credit cards, PayPal, and bank transfers</li>
                            <li>Prices are inclusive of applicable taxes unless stated otherwise</li>
                        </ul>
                        
                        <h4 class="mt-4">4. Shipping and Delivery</h4>
                        <p>Shipping times and costs vary by destination. We are not responsible for delays caused by customs, weather, or other factors beyond our control. Risk of loss passes to you upon delivery to the carrier.</p>
                        
                        <h4 class="mt-4">5. Returns and Refunds</h4>
                        <ul>
                            <li>Returns accepted within 30 days of delivery</li>
                            <li>Products must be unopened and in original packaging</li>
                            <li>Refunds processed within 5-7 business days</li>
                            <li>Shipping costs are non-refundable</li>
                        </ul>
                        
                        <h4 class="mt-4">6. Product Information</h4>
                        <p>We strive to provide accurate product descriptions and images. However, we do not warrant that product descriptions or other content is accurate, complete, or error-free.</p>
                        
                        <h4 class="mt-4">7. Intellectual Property</h4>
                        <p>All content on this website, including text, images, logos, and graphics, is the property of Ceylon Cinnamon and is protected by copyright laws.</p>
                        
                        <h4 class="mt-4">8. Limitation of Liability</h4>
                        <p>Ceylon Cinnamon shall not be liable for any indirect, incidental, special, or consequential damages arising from the use of our products or services.</p>
                        
                        <h4 class="mt-4">9. Governing Law</h4>
                        <p>These terms shall be governed by and construed in accordance with the laws of Sri Lanka, without regard to its conflict of law provisions.</p>
                        
                        <h4 class="mt-4">10. Contact Information</h4>
                        <p>For questions about these Terms of Service, please contact us at:</p>
                        <p>
                            Email: <a href="mailto:legal@ceyloncinnamon.com">legal@ceyloncinnamon.com</a><br>
                            Address: 123 Cinnamon Gardens, Colombo 07, Sri Lanka
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include VIEWS_PATH . '/layouts/footer.php'; ?>
