<?php
/**
 * Checkout Page View
 * Requirements: 3.3, 3.4, 4.1, 4.2, 4.3
 */
$pageTitle = 'Checkout - Ceylon Cinnamon';
include VIEWS_PATH . '/layouts/header.php';
?>

<div class="container py-5">
    <h1 class="mb-4">Checkout</h1>

    <?php if (!empty($_SESSION['flash']['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['flash']['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash']['error']); ?>
    <?php endif; ?>

    <form id="checkout-form" action="<?= url('/checkout') ?>" method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
        <input type="hidden" name="stripe_token" id="stripe-token" value="">
        <input type="hidden" name="paypal_order_id" id="paypal-order-id" value="">

        <div class="row">
            <!-- Customer Information -->
            <div class="col-lg-8">
                <!-- Contact Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Contact Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email Address *</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone"
                                       value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Shipping Address -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Shipping Address</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">First Name *</label>
                                <input type="text" class="form-control" id="first_name" name="first_name"
                                       value="<?= htmlspecialchars($user['first_name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input type="text" class="form-control" id="last_name" name="last_name"
                                       value="<?= htmlspecialchars($user['last_name'] ?? '') ?>" required>
                            </div>
                            <div class="col-12">
                                <label for="address" class="form-label">Street Address *</label>
                                <input type="text" class="form-control" id="address" name="address" required>
                            </div>
                            <div class="col-md-6">
                                <label for="city" class="form-label">City *</label>
                                <input type="text" class="form-control" id="city" name="city" required>
                            </div>
                            <div class="col-md-6">
                                <label for="state" class="form-label">State/Province</label>
                                <input type="text" class="form-control" id="state" name="state">
                            </div>
                            <div class="col-md-6">
                                <label for="postal_code" class="form-label">Postal Code *</label>
                                <input type="text" class="form-control" id="postal_code" name="postal_code" required>
                            </div>
                            <div class="col-md-6">
                                <label for="country" class="form-label">Country *</label>
                                <select class="form-select" id="country" name="country" required>
                                    <option value="">Select Country</option>
                                    <?php foreach ($countries as $code => $name): ?>
                                        <option value="<?= $code ?>"><?= htmlspecialchars($name) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" id="billing_same" name="billing_same" value="1" checked>
                            <label class="form-check-label" for="billing_same">
                                Billing address same as shipping
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Shipping Method Selection (Requirements: 14.2, 14.3, 14.4) -->
                <div class="card mb-4" id="shipping-method-section" style="display: none;">
                    <div class="card-header">
                        <h5 class="mb-0">Shipping Method</h5>
                    </div>
                    <div class="card-body">
                        <div id="shipping-methods-loading" class="text-center py-3">
                            <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                            <span class="ms-2">Loading shipping options...</span>
                        </div>
                        <div id="shipping-methods-container"></div>
                        <div id="shipping-methods-error" class="alert alert-warning d-none">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <span id="shipping-error-message"></span>
                        </div>
                        <input type="hidden" name="shipping_method" id="shipping_method" value="">
                    </div>
                </div>

                <!-- Billing Address (hidden by default) -->
                <div class="card mb-4" id="billing-address-section" style="display: none;">
                    <div class="card-header">
                        <h5 class="mb-0">Billing Address</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="billing_address" class="form-label">Street Address</label>
                                <input type="text" class="form-control" id="billing_address" name="billing_address">
                            </div>
                            <div class="col-md-6">
                                <label for="billing_city" class="form-label">City</label>
                                <input type="text" class="form-control" id="billing_city" name="billing_city">
                            </div>
                            <div class="col-md-6">
                                <label for="billing_state" class="form-label">State/Province</label>
                                <input type="text" class="form-control" id="billing_state" name="billing_state">
                            </div>
                            <div class="col-md-6">
                                <label for="billing_postal_code" class="form-label">Postal Code</label>
                                <input type="text" class="form-control" id="billing_postal_code" name="billing_postal_code">
                            </div>
                            <div class="col-md-6">
                                <label for="billing_country" class="form-label">Country</label>
                                <select class="form-select" id="billing_country" name="billing_country">
                                    <option value="">Select Country</option>
                                    <option value="US">United States</option>
                                    <option value="UK">United Kingdom</option>
                                    <option value="CA">Canada</option>
                                    <option value="AU">Australia</option>
                                    <option value="LK">Sri Lanka</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Method -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Payment Method</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($paymentMethods as $method => $info): ?>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="payment_method" 
                                       id="payment_<?= $method ?>" value="<?= $method ?>"
                                       <?= $method === 'stripe' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="payment_<?= $method ?>">
                                    <strong><?= htmlspecialchars($info['name']) ?></strong>
                                    <br><small class="text-muted"><?= htmlspecialchars($info['description']) ?></small>
                                </label>
                            </div>
                        <?php endforeach; ?>

                        <!-- Stripe Card Element -->
                        <div id="stripe-payment-section" class="mt-4">
                            <label class="form-label">Card Details</label>
                            <div id="card-element" class="form-control" style="padding: 12px;"></div>
                            <div id="card-errors" class="text-danger mt-2" role="alert"></div>
                        </div>

                        <!-- PayPal Button Container -->
                        <div id="paypal-payment-section" class="mt-4" style="display: none;">
                            <div id="paypal-button-container"></div>
                        </div>

                        <!-- Bank Transfer Info -->
                        <div id="bank-transfer-section" class="mt-4" style="display: none;">
                            <div class="alert alert-info">
                                <h6>Bank Transfer Instructions</h6>
                                <p class="mb-0">After placing your order, you will receive bank details to complete the transfer. Your order will be processed once payment is confirmed.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Notes -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Order Notes (Optional)</h5>
                    </div>
                    <div class="card-body">
                        <textarea class="form-control" id="notes" name="notes" rows="3" 
                                  placeholder="Special instructions for your order..."></textarea>
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="col-lg-4">
                <div class="card sticky-top" style="top: 20px;">
                    <div class="card-header">
                        <h5 class="mb-0">Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($cart['items'] as $item): ?>
                            <div class="d-flex justify-content-between mb-2">
                                <span>
                                    <?= htmlspecialchars($item['product']['name']) ?>
                                    <small class="text-muted">x<?= $item['quantity'] ?></small>
                                </span>
                                <span>$<?= number_format($item['subtotal'], 2) ?></span>
                            </div>
                        <?php endforeach; ?>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal</span>
                            <span>$<?= number_format($cart['subtotal'], 2) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Shipping</span>
                            <span id="shipping-cost-display">Select country first</span>
                        </div>
                        <div id="delivery-estimate" class="small text-muted mb-2 d-none">
                            <i class="bi bi-truck me-1"></i>
                            <span id="delivery-estimate-text"></span>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between mb-3">
                            <strong>Total</strong>
                            <strong id="order-total">$<?= number_format($cart['total'], 2) ?></strong>
                        </div>

                        <button type="submit" id="submit-btn" class="btn btn-primary w-100 btn-lg">
                            <span id="btn-text">Place Order</span>
                            <span id="btn-spinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                        </button>

                        <p class="text-muted small mt-3 mb-0">
                            By placing your order, you agree to our Terms of Service and Privacy Policy.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Stripe.js -->
<?php if (!empty($stripePublicKey)): ?>
<script src="https://js.stripe.com/v3/"></script>
<?php endif; ?>

<!-- PayPal SDK -->
<?php if (!empty($paypalClientId)): ?>
<script src="https://www.paypal.com/sdk/js?client-id=<?= htmlspecialchars($paypalClientId) ?>&currency=USD"></script>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('checkout-form');
    const submitBtn = document.getElementById('submit-btn');
    const btnText = document.getElementById('btn-text');
    const btnSpinner = document.getElementById('btn-spinner');
    const billingCheckbox = document.getElementById('billing_same');
    const billingSection = document.getElementById('billing-address-section');
    const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
    const countrySelect = document.getElementById('country');
    const shippingMethodSection = document.getElementById('shipping-method-section');
    const shippingMethodsContainer = document.getElementById('shipping-methods-container');
    const shippingMethodsLoading = document.getElementById('shipping-methods-loading');
    const shippingMethodsError = document.getElementById('shipping-methods-error');
    const shippingMethodInput = document.getElementById('shipping_method');
    const shippingCostDisplay = document.getElementById('shipping-cost-display');
    const orderTotal = document.getElementById('order-total');
    const deliveryEstimate = document.getElementById('delivery-estimate');
    const deliveryEstimateText = document.getElementById('delivery-estimate-text');
    
    const cartSubtotal = <?= $cart['subtotal'] ?>;
    let selectedShippingCost = 0;
    
    // Toggle billing address section
    billingCheckbox.addEventListener('change', function() {
        billingSection.style.display = this.checked ? 'none' : 'block';
    });

    // Load shipping methods when country changes (Requirements: 14.2, 14.3, 14.4)
    countrySelect.addEventListener('change', function() {
        const country = this.value;
        
        if (!country) {
            shippingMethodSection.style.display = 'none';
            shippingCostDisplay.textContent = 'Select country first';
            updateTotal(0);
            return;
        }
        
        // Show shipping section and loading state
        shippingMethodSection.style.display = 'block';
        shippingMethodsLoading.style.display = 'block';
        shippingMethodsContainer.innerHTML = '';
        shippingMethodsError.classList.add('d-none');
        shippingMethodInput.value = '';
        
        // Fetch shipping methods
        fetch('<?= url('/checkout/shipping-methods') ?>?country=' + encodeURIComponent(country), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.json())
        .then(data => {
            shippingMethodsLoading.style.display = 'none';
            
            if (data.success && data.methods && data.methods.length > 0) {
                let html = '';
                data.methods.forEach((method, index) => {
                    const checked = index === 0 ? 'checked' : '';
                    html += `
                        <div class="form-check mb-3 shipping-method-option">
                            <input class="form-check-input" type="radio" name="shipping_method_radio" 
                                   id="shipping_${method.id}" value="${method.id}" 
                                   data-cost="${method.cost}" 
                                   data-delivery="${method.delivery_text || ''}"
                                   ${checked}>
                            <label class="form-check-label w-100" for="shipping_${method.id}">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <strong>${escapeHtml(method.name)}</strong>
                                        ${method.description ? '<br><small class="text-muted">' + escapeHtml(method.description) + '</small>' : ''}
                                        ${method.delivery_text ? '<br><small class="text-success"><i class="bi bi-truck"></i> ' + escapeHtml(method.delivery_text) + '</small>' : ''}
                                    </div>
                                    <div class="text-end">
                                        ${method.free_shipping ? '<span class="badge bg-success">FREE</span>' : '<strong>' + method.cost_formatted + '</strong>'}
                                    </div>
                                </div>
                            </label>
                        </div>
                    `;
                });
                shippingMethodsContainer.innerHTML = html;
                
                // Add event listeners to shipping method radios
                document.querySelectorAll('input[name="shipping_method_radio"]').forEach(radio => {
                    radio.addEventListener('change', function() {
                        selectShippingMethod(this);
                    });
                });
                
                // Select first method by default
                const firstMethod = document.querySelector('input[name="shipping_method_radio"]:checked');
                if (firstMethod) {
                    selectShippingMethod(firstMethod);
                }
            } else {
                shippingMethodsError.classList.remove('d-none');
                document.getElementById('shipping-error-message').textContent = 
                    data.error || 'No shipping methods available for your location.';
                shippingCostDisplay.textContent = 'Not available';
                updateTotal(0);
            }
        })
        .catch(error => {
            shippingMethodsLoading.style.display = 'none';
            shippingMethodsError.classList.remove('d-none');
            document.getElementById('shipping-error-message').textContent = 
                'Unable to load shipping options. Please try again.';
            console.error('Error loading shipping methods:', error);
        });
    });
    
    function selectShippingMethod(radio) {
        const cost = parseFloat(radio.dataset.cost) || 0;
        const deliveryText = radio.dataset.delivery || '';
        
        shippingMethodInput.value = radio.value;
        selectedShippingCost = cost;
        
        if (cost === 0) {
            shippingCostDisplay.innerHTML = '<span class="text-success">FREE</span>';
        } else {
            shippingCostDisplay.textContent = '$' + cost.toFixed(2);
        }
        
        if (deliveryText) {
            deliveryEstimate.classList.remove('d-none');
            deliveryEstimateText.textContent = 'Estimated delivery: ' + deliveryText;
        } else {
            deliveryEstimate.classList.add('d-none');
        }
        
        updateTotal(cost);
    }
    
    function updateTotal(shippingCost) {
        const total = cartSubtotal + shippingCost;
        orderTotal.textContent = '$' + total.toFixed(2);
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Payment method sections
    const stripeSection = document.getElementById('stripe-payment-section');
    const paypalSection = document.getElementById('paypal-payment-section');
    const bankSection = document.getElementById('bank-transfer-section');

    function showPaymentSection(method) {
        stripeSection.style.display = method === 'stripe' ? 'block' : 'none';
        paypalSection.style.display = method === 'paypal' ? 'block' : 'none';
        bankSection.style.display = method === 'bank_transfer' ? 'block' : 'none';
        
        // Update button text
        if (method === 'paypal') {
            btnText.textContent = 'Continue with PayPal';
        } else if (method === 'bank_transfer') {
            btnText.textContent = 'Place Order (Bank Transfer)';
        } else {
            btnText.textContent = 'Place Order';
        }
    }

    paymentMethods.forEach(function(radio) {
        radio.addEventListener('change', function() {
            showPaymentSection(this.value);
        });
    });

    // Initialize with selected payment method
    const selectedMethod = document.querySelector('input[name="payment_method"]:checked');
    if (selectedMethod) {
        showPaymentSection(selectedMethod.value);
    }

    // Stripe setup
    <?php if (!empty($stripePublicKey)): ?>
    const stripe = Stripe('<?= htmlspecialchars($stripePublicKey) ?>');
    const elements = stripe.elements();
    const cardElement = elements.create('card', {
        style: {
            base: {
                fontSize: '16px',
                color: '#424770',
                '::placeholder': { color: '#aab7c4' }
            },
            invalid: { color: '#9e2146' }
        }
    });
    cardElement.mount('#card-element');

    cardElement.on('change', function(event) {
        const displayError = document.getElementById('card-errors');
        displayError.textContent = event.error ? event.error.message : '';
    });
    <?php endif; ?>

    // PayPal setup
    <?php if (!empty($paypalClientId)): ?>
    if (typeof paypal !== 'undefined') {
        paypal.Buttons({
            createOrder: function(data, actions) {
                return fetch('<?= url('/checkout/paypal/create') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        csrf_token: '<?= htmlspecialchars($csrf_token) ?>'
                    })
                }).then(function(res) {
                    return res.json();
                }).then(function(data) {
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    return data.orderID;
                });
            },
            onApprove: function(data, actions) {
                document.getElementById('paypal-order-id').value = data.orderID;
                form.submit();
            },
            onError: function(err) {
                alert('PayPal error: ' + err.message);
            }
        }).render('#paypal-button-container');
    }
    <?php endif; ?>

    // Form submission
    form.addEventListener('submit', async function(e) {
        const selectedPayment = document.querySelector('input[name="payment_method"]:checked').value;
        
        // For PayPal, the form is submitted via the PayPal button
        if (selectedPayment === 'paypal') {
            e.preventDefault();
            alert('Please use the PayPal button to complete your payment.');
            return;
        }

        // For Stripe, create token first
        <?php if (!empty($stripePublicKey)): ?>
        if (selectedPayment === 'stripe') {
            e.preventDefault();
            
            submitBtn.disabled = true;
            btnText.classList.add('d-none');
            btnSpinner.classList.remove('d-none');

            const {token, error} = await stripe.createToken(cardElement);
            
            if (error) {
                document.getElementById('card-errors').textContent = error.message;
                submitBtn.disabled = false;
                btnText.classList.remove('d-none');
                btnSpinner.classList.add('d-none');
                return;
            }

            document.getElementById('stripe-token').value = token.id;
            form.submit();
        }
        <?php endif; ?>

        // For bank transfer, just submit the form
        if (selectedPayment === 'bank_transfer') {
            submitBtn.disabled = true;
            btnText.classList.add('d-none');
            btnSpinner.classList.remove('d-none');
        }
    });
});
</script>

<?php include VIEWS_PATH . '/layouts/footer.php'; ?>
