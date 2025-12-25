    </main>

    <!-- Newsletter Section -->
    <section class="newsletter-section bg-primary text-white py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 text-center">
                    <h3 class="mb-3"><?= t('newsletter.title') ?? 'Subscribe to Our Newsletter' ?></h3>
                    <p class="mb-4"><?= t('newsletter.description') ?? 'Get updates on new products, special offers, and cinnamon recipes!' ?></p>
                    <form class="row g-2 justify-content-center" action="<?= url('/newsletter/subscribe') ?>" method="POST">
                        <div class="col-12 col-sm-8 col-md-6">
                            <input type="email" name="email" class="form-control form-control-lg" placeholder="<?= t('label.email') ?>" required>
                        </div>
                        <div class="col-12 col-sm-4 col-md-3">
                            <button type="submit" class="btn btn-light btn-lg w-100"><?= t('action.subscribe') ?? 'Subscribe' ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Footer -->
    <footer class="footer bg-dark text-light pt-5 pb-3">
        <div class="container">
            <div class="row g-4">
                <!-- Company Info -->
                <div class="col-lg-4 col-md-6">
                    <div class="footer-brand mb-3">
                        <span class="brand-icon me-2">🌿</span>
                        <span class="brand-text h4 text-white"><?= APP_NAME ?></span>
                    </div>
                    <p class="text-light-emphasis mb-3">
                        <?= t('footer.description') ?? 'Premium Ceylon cinnamon products sourced directly from Sri Lanka. Experience the authentic taste and health benefits of true cinnamon.' ?>
                    </p>
                    <div class="social-links">
                        <a href="#" class="btn btn-outline-light btn-sm me-2" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="btn btn-outline-light btn-sm me-2" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="btn btn-outline-light btn-sm me-2" aria-label="Twitter"><i class="bi bi-twitter-x"></i></a>
                        <a href="#" class="btn btn-outline-light btn-sm" aria-label="YouTube"><i class="bi bi-youtube"></i></a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="col-lg-2 col-md-6 col-6">
                    <h5 class="text-white mb-3"><?= t('footer.quick_links') ?? 'Quick Links' ?></h5>
                    <ul class="list-unstyled footer-links">
                        <li><a href="<?= url('/products') ?>"><?= t('nav.products') ?></a></li>
                        <li><a href="<?= url('/about') ?>"><?= t('nav.about') ?></a></li>
                        <li><a href="<?= url('/blog') ?>"><?= t('nav.blog') ?></a></li>
                        <li><a href="<?= url('/wholesale') ?>"><?= t('nav.wholesale') ?? 'Wholesale' ?></a></li>
                        <li><a href="<?= url('/certificates') ?>"><?= t('nav.certificates') ?? 'Certificates' ?></a></li>
                    </ul>
                </div>

                <!-- Customer Service -->
                <div class="col-lg-2 col-md-6 col-6">
                    <h5 class="text-white mb-3"><?= t('footer.customer_service') ?? 'Customer Service' ?></h5>
                    <ul class="list-unstyled footer-links">
                        <li><a href="<?= url('/contact') ?>"><?= t('nav.contact') ?></a></li>
                        <li><a href="<?= url('/order/track') ?>"><?= t('footer.track_order') ?? 'Track Order' ?></a></li>
                        <li><a href="<?= url('/shipping') ?>"><?= t('footer.shipping') ?? 'Shipping Info' ?></a></li>
                        <li><a href="<?= url('/returns') ?>"><?= t('footer.returns') ?? 'Returns' ?></a></li>
                        <li><a href="<?= url('/faq') ?>"><?= t('footer.faq') ?? 'FAQ' ?></a></li>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div class="col-lg-4 col-md-6">
                    <h5 class="text-white mb-3"><?= t('footer.contact_us') ?? 'Contact Us' ?></h5>
                    <ul class="list-unstyled footer-contact">
                        <li class="mb-2">
                            <i class="bi bi-geo-alt me-2 text-primary"></i>
                            <span>123 Cinnamon Gardens, Colombo 07, Sri Lanka</span>
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-telephone me-2 text-primary"></i>
                            <a href="tel:+94112345678">+94 11 234 5678</a>
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-envelope me-2 text-primary"></i>
                            <a href="mailto:info@ceyloncinnamon.com">info@ceyloncinnamon.com</a>
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-clock me-2 text-primary"></i>
                            <span><?= t('footer.hours') ?? 'Mon - Fri: 9:00 AM - 6:00 PM' ?></span>
                        </li>
                    </ul>
                    
                    <!-- Payment Methods -->
                    <div class="payment-methods mt-3">
                        <span class="text-light-emphasis small d-block mb-2"><?= t('footer.we_accept') ?? 'We Accept' ?>:</span>
                        <img src="<?= url('/assets/images/payment-visa.svg') ?>" alt="Visa" height="24" class="me-2" onerror="this.style.display='none'">
                        <img src="<?= url('/assets/images/payment-mastercard.svg') ?>" alt="Mastercard" height="24" class="me-2" onerror="this.style.display='none'">
                        <img src="<?= url('/assets/images/payment-paypal.svg') ?>" alt="PayPal" height="24" class="me-2" onerror="this.style.display='none'">
                        <i class="bi bi-credit-card fs-4 text-light-emphasis"></i>
                    </div>
                </div>
            </div>

            <hr class="my-4 border-secondary">

            <!-- Bottom Footer -->
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                    <p class="mb-0 text-light-emphasis small">
                        <?= t('footer.copyright', ['year' => date('Y')]) ?? '© ' . date('Y') . ' Ceylon Cinnamon. All rights reserved.' ?>
                    </p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <ul class="list-inline mb-0 footer-legal">
                        <li class="list-inline-item"><a href="<?= url('/privacy') ?>"><?= t('footer.privacy') ?></a></li>
                        <li class="list-inline-item">|</li>
                        <li class="list-inline-item"><a href="<?= url('/terms') ?>"><?= t('footer.terms') ?></a></li>
                        <li class="list-inline-item">|</li>
                        <li class="list-inline-item"><a href="<?= url('/sitemap') ?>"><?= t('footer.sitemap') ?? 'Sitemap' ?></a></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <button type="button" class="btn btn-primary btn-back-to-top" id="backToTop" aria-label="Back to top">
        <i class="bi bi-arrow-up"></i>
    </button>

    <!-- Toast Container for Notifications -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer"></div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JavaScript -->
    <script src="<?= class_exists('AssetHelper') ? AssetHelper::js('app.js') : url('/assets/js/app.js') ?>"></script>
</body>
</html>
