<?php
/**
 * Contact Page
 * Requirements: 11.5 (responsive design)
 */
include VIEWS_PATH . '/layouts/header.php';
?>

<section class="py-5">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= url('/') ?>"><?= t('nav.home') ?></a></li>
                <li class="breadcrumb-item active"><?= t('nav.contact') ?></li>
            </ol>
        </nav>
        
        <div class="row">
            <div class="col-lg-8 mx-auto text-center mb-5">
                <h1 class="fw-bold"><?= t('contact.title') ?? 'Get in Touch' ?></h1>
                <p class="lead text-muted"><?= t('contact.subtitle') ?? 'Have questions? We\'d love to hear from you. Send us a message and we\'ll respond as soon as possible.' ?></p>
            </div>
        </div>
        
        <div class="row g-5">
            <!-- Contact Form -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <h3 class="mb-4"><?= t('contact.form_title') ?? 'Send us a Message' ?></h3>
                        
                        <?php if (!empty($success)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($success) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>
                        
                        <form action="<?= url('/contact') ?>" method="POST" id="contact-form">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="name" class="form-label"><?= t('label.name') ?> *</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label"><?= t('label.email') ?> *</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                <div class="col-12">
                                    <label for="phone" class="form-label"><?= t('label.phone') ?></label>
                                    <input type="tel" class="form-control" id="phone" name="phone">
                                </div>
                                <div class="col-12">
                                    <label for="subject" class="form-label"><?= t('contact.subject') ?? 'Subject' ?> *</label>
                                    <select class="form-select" id="subject" name="subject" required>
                                        <option value=""><?= t('contact.select_subject') ?? 'Select a subject' ?></option>
                                        <option value="general"><?= t('contact.subject_general') ?? 'General Inquiry' ?></option>
                                        <option value="order"><?= t('contact.subject_order') ?? 'Order Question' ?></option>
                                        <option value="wholesale"><?= t('contact.subject_wholesale') ?? 'Wholesale Inquiry' ?></option>
                                        <option value="shipping"><?= t('contact.subject_shipping') ?? 'Shipping Question' ?></option>
                                        <option value="returns"><?= t('contact.subject_returns') ?? 'Returns & Refunds' ?></option>
                                        <option value="other"><?= t('contact.subject_other') ?? 'Other' ?></option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label for="message" class="form-label"><?= t('contact.message') ?? 'Message' ?> *</label>
                                    <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="bi bi-send me-2"></i><?= t('contact.send') ?? 'Send Message' ?>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Contact Info -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h5 class="mb-4"><?= t('contact.info_title') ?? 'Contact Information' ?></h5>
                        
                        <div class="d-flex mb-4">
                            <div class="flex-shrink-0">
                                <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                    <i class="bi bi-geo-alt text-primary"></i>
                                </div>
                            </div>
                            <div class="ms-3">
                                <h6 class="mb-1"><?= t('contact.address') ?? 'Address' ?></h6>
                                <p class="text-muted mb-0">123 Cinnamon Gardens<br>Colombo 07, Sri Lanka</p>
                            </div>
                        </div>
                        
                        <div class="d-flex mb-4">
                            <div class="flex-shrink-0">
                                <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                    <i class="bi bi-telephone text-primary"></i>
                                </div>
                            </div>
                            <div class="ms-3">
                                <h6 class="mb-1"><?= t('contact.phone') ?? 'Phone' ?></h6>
                                <p class="text-muted mb-0">
                                    <a href="tel:+94112345678" class="text-decoration-none">+94 11 234 5678</a>
                                </p>
                            </div>
                        </div>
                        
                        <div class="d-flex mb-4">
                            <div class="flex-shrink-0">
                                <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                    <i class="bi bi-envelope text-primary"></i>
                                </div>
                            </div>
                            <div class="ms-3">
                                <h6 class="mb-1"><?= t('contact.email') ?? 'Email' ?></h6>
                                <p class="text-muted mb-0">
                                    <a href="mailto:info@ceyloncinnamon.com" class="text-decoration-none">info@ceyloncinnamon.com</a>
                                </p>
                            </div>
                        </div>
                        
                        <div class="d-flex">
                            <div class="flex-shrink-0">
                                <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                    <i class="bi bi-clock text-primary"></i>
                                </div>
                            </div>
                            <div class="ms-3">
                                <h6 class="mb-1"><?= t('contact.hours') ?? 'Business Hours' ?></h6>
                                <p class="text-muted mb-0">
                                    Mon - Fri: 9:00 AM - 6:00 PM<br>
                                    Sat: 9:00 AM - 1:00 PM<br>
                                    Sun: Closed
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Social Links -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h5 class="mb-3"><?= t('contact.follow_us') ?? 'Follow Us' ?></h5>
                        <div class="d-flex gap-2">
                            <a href="#" class="btn btn-outline-primary" aria-label="Facebook">
                                <i class="bi bi-facebook"></i>
                            </a>
                            <a href="#" class="btn btn-outline-primary" aria-label="Instagram">
                                <i class="bi bi-instagram"></i>
                            </a>
                            <a href="#" class="btn btn-outline-primary" aria-label="Twitter">
                                <i class="bi bi-twitter-x"></i>
                            </a>
                            <a href="#" class="btn btn-outline-primary" aria-label="YouTube">
                                <i class="bi bi-youtube"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Map Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="ratio ratio-21x9 rounded-3 overflow-hidden shadow">
            <iframe 
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3960.798467128636!2d79.86124!3d6.9147!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zNsKwNTQnNTMuMCJOIDc5wrA1MScyMC4wIkU!5e0!3m2!1sen!2slk!4v1234567890"
                style="border:0;" 
                allowfullscreen="" 
                loading="lazy" 
                referrerpolicy="no-referrer-when-downgrade"
                title="Ceylon Cinnamon Location">
            </iframe>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h2 class="fw-bold text-center mb-4"><?= t('contact.faq_title') ?? 'Frequently Asked Questions' ?></h2>
                
                <div class="accordion" id="faqAccordion">
                    <div class="accordion-item border-0 mb-3 shadow-sm">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                <?= t('contact.faq1_q') ?? 'How long does shipping take?' ?>
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted">
                                <?= t('contact.faq1_a') ?? 'Domestic orders typically arrive within 3-5 business days. International shipping takes 7-14 business days depending on the destination.' ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item border-0 mb-3 shadow-sm">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                <?= t('contact.faq2_q') ?? 'Do you offer wholesale pricing?' ?>
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted">
                                <?= t('contact.faq2_a') ?? 'Yes! We offer competitive wholesale pricing for bulk orders. Please visit our wholesale page or contact us directly for more information.' ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item border-0 mb-3 shadow-sm">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                <?= t('contact.faq3_q') ?? 'What is your return policy?' ?>
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted">
                                <?= t('contact.faq3_a') ?? 'We offer a 30-day satisfaction guarantee. If you\'re not happy with your purchase, contact us for a full refund or exchange.' ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include VIEWS_PATH . '/layouts/footer.php'; ?>
