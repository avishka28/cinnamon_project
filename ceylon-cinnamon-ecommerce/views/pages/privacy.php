<?php
/**
 * Privacy Policy Page
 */
include VIEWS_PATH . '/layouts/header.php';
?>

<section class="py-5">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= url('/') ?>"><?= t('nav.home') ?></a></li>
                <li class="breadcrumb-item active"><?= t('footer.privacy') ?></li>
            </ol>
        </nav>
        
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h1 class="fw-bold mb-4"><?= t('privacy.title') ?? 'Privacy Policy' ?></h1>
                <p class="text-muted mb-4"><?= t('privacy.last_updated') ?? 'Last updated: ' . date('F d, Y') ?></p>
                
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <h4>1. Information We Collect</h4>
                        <p>We collect information you provide directly to us, such as when you create an account, make a purchase, or contact us for support. This may include:</p>
                        <ul>
                            <li>Name and contact information</li>
                            <li>Billing and shipping addresses</li>
                            <li>Payment information (processed securely through our payment providers)</li>
                            <li>Order history and preferences</li>
                        </ul>
                        
                        <h4 class="mt-4">2. How We Use Your Information</h4>
                        <p>We use the information we collect to:</p>
                        <ul>
                            <li>Process and fulfill your orders</li>
                            <li>Send order confirmations and shipping updates</li>
                            <li>Respond to your inquiries and provide customer support</li>
                            <li>Send promotional communications (with your consent)</li>
                            <li>Improve our products and services</li>
                        </ul>
                        
                        <h4 class="mt-4">3. Information Sharing</h4>
                        <p>We do not sell, trade, or rent your personal information to third parties. We may share your information with:</p>
                        <ul>
                            <li>Payment processors to complete transactions</li>
                            <li>Shipping carriers to deliver your orders</li>
                            <li>Service providers who assist in our operations</li>
                        </ul>
                        
                        <h4 class="mt-4">4. Data Security</h4>
                        <p>We implement appropriate security measures to protect your personal information. All payment transactions are encrypted using SSL technology. We do not store credit card information on our servers.</p>
                        
                        <h4 class="mt-4">5. Cookies</h4>
                        <p>We use cookies to enhance your browsing experience, analyze site traffic, and personalize content. You can control cookie settings through your browser preferences.</p>
                        
                        <h4 class="mt-4">6. Your Rights</h4>
                        <p>You have the right to:</p>
                        <ul>
                            <li>Access your personal information</li>
                            <li>Correct inaccurate data</li>
                            <li>Request deletion of your data</li>
                            <li>Opt-out of marketing communications</li>
                        </ul>
                        
                        <h4 class="mt-4">7. Contact Us</h4>
                        <p>If you have questions about this Privacy Policy, please contact us at:</p>
                        <p>
                            Email: <a href="mailto:privacy@ceyloncinnamon.com">privacy@ceyloncinnamon.com</a><br>
                            Address: 123 Cinnamon Gardens, Colombo 07, Sri Lanka
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include VIEWS_PATH . '/layouts/footer.php'; ?>
