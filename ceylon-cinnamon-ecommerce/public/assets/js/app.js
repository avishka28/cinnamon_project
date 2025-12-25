/**
 * Ceylon Cinnamon E-commerce JavaScript
 * Requirements: 3.1 (cart operations), 3.2 (cart display)
 */

(function() {
    'use strict';

    // ============================================
    // Configuration
    // ============================================
    const CONFIG = {
        apiBaseUrl: '',
        toastDuration: 3000,
        debounceDelay: 300
    };

    // ============================================
    // Utility Functions
    // ============================================
    
    /**
     * Debounce function to limit rapid calls
     */
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /**
     * Format currency
     */
    function formatCurrency(amount) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD'
        }).format(amount);
    }

    /**
     * Get CSRF token from meta tag or form
     */
    function getCsrfToken() {
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        if (metaTag) return metaTag.getAttribute('content');
        
        const csrfInput = document.querySelector('input[name="csrf_token"]');
        if (csrfInput) return csrfInput.value;
        
        return '';
    }

    // ============================================
    // Toast Notifications
    // ============================================
    const Toast = {
        container: null,

        init() {
            this.container = document.getElementById('toastContainer');
            if (!this.container) {
                this.container = document.createElement('div');
                this.container.id = 'toastContainer';
                this.container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
                document.body.appendChild(this.container);
            }
        },

        show(message, type = 'info') {
            if (!this.container) this.init();

            const toastId = 'toast-' + Date.now();
            const bgClass = {
                success: 'bg-success',
                error: 'bg-danger',
                warning: 'bg-warning',
                info: 'bg-primary'
            }[type] || 'bg-primary';

            const toastHtml = `
                <div id="${toastId}" class="toast align-items-center text-white ${bgClass} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="bi bi-${type === 'success' ? 'check-circle' : type === 'error' ? 'x-circle' : 'info-circle'} me-2"></i>
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>
            `;

            this.container.insertAdjacentHTML('beforeend', toastHtml);
            
            const toastElement = document.getElementById(toastId);
            const toast = new bootstrap.Toast(toastElement, { delay: CONFIG.toastDuration });
            toast.show();

            toastElement.addEventListener('hidden.bs.toast', () => {
                toastElement.remove();
            });
        },

        success(message) { this.show(message, 'success'); },
        error(message) { this.show(message, 'error'); },
        warning(message) { this.show(message, 'warning'); },
        info(message) { this.show(message, 'info'); }
    };

    // ============================================
    // Cart Operations (Requirements 3.1, 3.2)
    // ============================================
    const Cart = {
        /**
         * Add item to cart
         * Requirement 3.1
         */
        async add(productId, quantity = 1) {
            try {
                const formData = new FormData();
                formData.append('product_id', productId);
                formData.append('quantity', quantity);
                formData.append('csrf_token', getCsrfToken());

                const response = await fetch('/cart/add', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.updateCartCount(data.cart_count || data.count);
                    Toast.success(data.message || 'Product added to cart!');
                    return true;
                } else {
                    Toast.error(data.error || 'Failed to add product to cart');
                    return false;
                }
            } catch (error) {
                console.error('Cart add error:', error);
                Toast.error('An error occurred. Please try again.');
                return false;
            }
        },

        /**
         * Update cart item quantity
         */
        async update(productId, quantity) {
            try {
                const formData = new FormData();
                formData.append('product_id', productId);
                formData.append('quantity', quantity);
                formData.append('csrf_token', getCsrfToken());

                const response = await fetch('/cart/update', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.updateCartCount(data.cart_count);
                    if (data.item_total !== undefined) {
                        this.updateItemTotal(productId, data.item_total);
                    }
                    if (data.cart_total !== undefined) {
                        this.updateCartTotal(data.cart_total);
                    }
                    return true;
                } else {
                    Toast.error(data.error || 'Failed to update cart');
                    return false;
                }
            } catch (error) {
                console.error('Cart update error:', error);
                Toast.error('An error occurred. Please try again.');
                return false;
            }
        },

        /**
         * Remove item from cart
         */
        async remove(productId) {
            try {
                const formData = new FormData();
                formData.append('product_id', productId);
                formData.append('csrf_token', getCsrfToken());

                const response = await fetch('/cart/remove', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.updateCartCount(data.cart_count);
                    // Remove item row from DOM
                    const itemRow = document.querySelector(`[data-product-id="${productId}"]`);
                    if (itemRow) {
                        itemRow.remove();
                    }
                    // Reload if cart is empty
                    if (data.cart_count === 0) {
                        location.reload();
                    } else if (data.cart_total !== undefined) {
                        this.updateCartTotal(data.cart_total);
                    }
                    Toast.success('Item removed from cart');
                    return true;
                } else {
                    Toast.error(data.error || 'Failed to remove item');
                    return false;
                }
            } catch (error) {
                console.error('Cart remove error:', error);
                Toast.error('An error occurred. Please try again.');
                return false;
            }
        },

        /**
         * Update cart count in header
         * Requirement 3.2
         */
        updateCartCount(count) {
            const badges = document.querySelectorAll('.cart-count, #mobile-cart-count, #desktop-cart-count');
            badges.forEach(badge => {
                badge.textContent = count;
                badge.style.display = count > 0 ? 'flex' : 'none';
            });
        },

        /**
         * Update item subtotal display
         */
        updateItemTotal(productId, total) {
            const itemRow = document.querySelector(`[data-product-id="${productId}"]`);
            if (itemRow) {
                const subtotalEl = itemRow.querySelector('.item-subtotal');
                if (subtotalEl) {
                    subtotalEl.textContent = formatCurrency(total);
                }
            }
        },

        /**
         * Update cart total display
         */
        updateCartTotal(total) {
            const totalEl = document.getElementById('cart-total');
            if (totalEl) {
                totalEl.textContent = formatCurrency(total);
            }
            const subtotalEl = document.getElementById('cart-subtotal');
            if (subtotalEl) {
                subtotalEl.textContent = formatCurrency(total);
            }
        }
    };

    // ============================================
    // Form Validation
    // ============================================
    const FormValidator = {
        init() {
            // Bootstrap validation
            const forms = document.querySelectorAll('.needs-validation');
            forms.forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });

            // Real-time validation
            this.initRealTimeValidation();
        },

        initRealTimeValidation() {
            // Email validation
            document.querySelectorAll('input[type="email"]').forEach(input => {
                input.addEventListener('blur', () => this.validateEmail(input));
            });

            // Password confirmation
            const confirmPassword = document.getElementById('confirm_password');
            if (confirmPassword) {
                confirmPassword.addEventListener('input', () => {
                    const password = document.getElementById('new_password') || document.getElementById('password');
                    if (password && confirmPassword.value !== password.value) {
                        confirmPassword.setCustomValidity('Passwords do not match');
                    } else {
                        confirmPassword.setCustomValidity('');
                    }
                });
            }

            // Phone number formatting
            document.querySelectorAll('input[type="tel"]').forEach(input => {
                input.addEventListener('input', () => this.formatPhone(input));
            });
        },

        validateEmail(input) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (input.value && !emailRegex.test(input.value)) {
                input.classList.add('is-invalid');
                return false;
            }
            input.classList.remove('is-invalid');
            return true;
        },

        formatPhone(input) {
            let value = input.value.replace(/\D/g, '');
            if (value.length > 10) {
                value = value.slice(0, 10);
            }
            input.value = value;
        }
    };

    // ============================================
    // Image Gallery
    // ============================================
    const ImageGallery = {
        init() {
            // Product image thumbnails
            document.querySelectorAll('.thumbnail-image').forEach(thumb => {
                thumb.addEventListener('click', function() {
                    const mainImage = document.getElementById('main-product-image');
                    if (mainImage) {
                        mainImage.src = this.src;
                        // Update active state
                        document.querySelectorAll('.thumbnail-image').forEach(t => t.classList.remove('active'));
                        this.classList.add('active');
                    }
                });
            });

            // Lightbox functionality
            this.initLightbox();
        },

        initLightbox() {
            const mainImage = document.getElementById('main-product-image');
            if (mainImage) {
                mainImage.style.cursor = 'zoom-in';
                mainImage.addEventListener('click', () => {
                    this.openLightbox(mainImage.src);
                });
            }
        },

        openLightbox(src) {
            const lightbox = document.createElement('div');
            lightbox.className = 'lightbox-overlay';
            lightbox.innerHTML = `
                <div class="lightbox-content">
                    <button class="lightbox-close" aria-label="Close">&times;</button>
                    <img src="${src}" alt="Product image">
                </div>
            `;
            lightbox.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.9);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
                cursor: pointer;
            `;
            
            const content = lightbox.querySelector('.lightbox-content');
            content.style.cssText = 'position: relative; max-width: 90%; max-height: 90%;';
            
            const img = lightbox.querySelector('img');
            img.style.cssText = 'max-width: 100%; max-height: 90vh; object-fit: contain;';
            
            const closeBtn = lightbox.querySelector('.lightbox-close');
            closeBtn.style.cssText = `
                position: absolute;
                top: -40px;
                right: 0;
                background: none;
                border: none;
                color: white;
                font-size: 2rem;
                cursor: pointer;
            `;

            document.body.appendChild(lightbox);
            document.body.style.overflow = 'hidden';

            const closeLightbox = () => {
                lightbox.remove();
                document.body.style.overflow = '';
            };

            lightbox.addEventListener('click', (e) => {
                if (e.target === lightbox || e.target === closeBtn) {
                    closeLightbox();
                }
            });

            document.addEventListener('keydown', function handler(e) {
                if (e.key === 'Escape') {
                    closeLightbox();
                    document.removeEventListener('keydown', handler);
                }
            });
        }
    };

    // ============================================
    // Back to Top Button
    // ============================================
    const BackToTop = {
        button: null,

        init() {
            this.button = document.getElementById('backToTop');
            if (!this.button) return;

            window.addEventListener('scroll', debounce(() => {
                if (window.scrollY > 300) {
                    this.button.classList.add('visible');
                } else {
                    this.button.classList.remove('visible');
                }
            }, 100));

            this.button.addEventListener('click', () => {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        }
    };

    // ============================================
    // Navbar Scroll Effect
    // ============================================
    const NavbarScroll = {
        init() {
            const navbar = document.querySelector('.navbar');
            if (!navbar) return;

            window.addEventListener('scroll', debounce(() => {
                if (window.scrollY > 50) {
                    navbar.classList.add('scrolled');
                } else {
                    navbar.classList.remove('scrolled');
                }
            }, 50));
        }
    };

    // ============================================
    // Quantity Input Controls
    // ============================================
    const QuantityControls = {
        init() {
            // Quantity minus buttons
            document.querySelectorAll('.qty-minus').forEach(btn => {
                btn.addEventListener('click', function() {
                    const input = this.parentElement.querySelector('.qty-input');
                    const currentVal = parseInt(input.value) || 0;
                    if (currentVal > 0) {
                        input.value = currentVal - 1;
                        input.dispatchEvent(new Event('change'));
                    }
                });
            });

            // Quantity plus buttons
            document.querySelectorAll('.qty-plus').forEach(btn => {
                btn.addEventListener('click', function() {
                    const input = this.parentElement.querySelector('.qty-input');
                    const currentVal = parseInt(input.value) || 0;
                    const maxVal = parseInt(input.max) || 999;
                    if (currentVal < maxVal) {
                        input.value = currentVal + 1;
                        input.dispatchEvent(new Event('change'));
                    }
                });
            });

            // Quantity input change (debounced for cart updates)
            document.querySelectorAll('.qty-input').forEach(input => {
                input.addEventListener('change', debounce(function() {
                    const form = this.closest('form');
                    if (form && form.classList.contains('cart-update-form')) {
                        const productId = form.querySelector('[name="product_id"]').value;
                        Cart.update(productId, this.value);
                    }
                }, CONFIG.debounceDelay));
            });
        }
    };

    // ============================================
    // Add to Cart Buttons
    // ============================================
    const AddToCartButtons = {
        init() {
            document.querySelectorAll('.add-to-cart').forEach(button => {
                button.addEventListener('click', async function(e) {
                    e.preventDefault();
                    
                    const productId = this.dataset.productId;
                    const quantity = this.dataset.quantity || 1;
                    
                    // Disable button and show loading
                    const originalHtml = this.innerHTML;
                    this.disabled = true;
                    this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
                    
                    await Cart.add(productId, quantity);
                    
                    // Restore button
                    this.disabled = false;
                    this.innerHTML = originalHtml;
                });
            });

            // Add to cart form (product detail page)
            const addToCartForm = document.getElementById('add-to-cart-form');
            if (addToCartForm) {
                addToCartForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const productId = this.querySelector('[name="product_id"]').value;
                    const quantity = this.querySelector('[name="quantity"]').value;
                    const submitBtn = this.querySelector('[type="submit"]');
                    
                    // Disable button and show loading
                    const originalHtml = submitBtn.innerHTML;
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Adding...';
                    
                    await Cart.add(productId, quantity);
                    
                    // Restore button
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalHtml;
                });
            }
        }
    };

    // ============================================
    // Cart Page Functionality
    // ============================================
    const CartPage = {
        init() {
            // Remove item buttons
            document.querySelectorAll('.cart-remove-form').forEach(form => {
                form.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    if (!confirm('Remove this item from cart?')) return;
                    
                    const productId = this.querySelector('[name="product_id"]').value;
                    await Cart.remove(productId);
                });
            });
        }
    };

    // ============================================
    // Lazy Loading Images
    // ============================================
    const LazyLoad = {
        init() {
            if ('loading' in HTMLImageElement.prototype) {
                // Native lazy loading supported
                document.querySelectorAll('img[loading="lazy"]').forEach(img => {
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                    }
                });
            } else {
                // Fallback for older browsers
                this.initIntersectionObserver();
            }
        },

        initIntersectionObserver() {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        if (img.dataset.src) {
                            img.src = img.dataset.src;
                        }
                        img.classList.remove('lazy');
                        observer.unobserve(img);
                    }
                });
            });

            document.querySelectorAll('img.lazy').forEach(img => {
                imageObserver.observe(img);
            });
        }
    };

    // ============================================
    // Search Functionality
    // ============================================
    const Search = {
        init() {
            const searchInputs = document.querySelectorAll('input[name="search"]');
            searchInputs.forEach(input => {
                // Auto-submit on enter
                input.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        this.closest('form').submit();
                    }
                });
            });
        }
    };

    // ============================================
    // Initialize Everything
    // ============================================
    function init() {
        Toast.init();
        FormValidator.init();
        ImageGallery.init();
        BackToTop.init();
        NavbarScroll.init();
        QuantityControls.init();
        AddToCartButtons.init();
        CartPage.init();
        LazyLoad.init();
        Search.init();

        // Initialize Bootstrap tooltips
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el));

        // Initialize Bootstrap popovers
        const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
        popoverTriggerList.forEach(el => new bootstrap.Popover(el));
    }

    // Run on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Expose Cart to global scope for inline handlers
    window.CeylonCinnamon = {
        Cart,
        Toast
    };

})();
