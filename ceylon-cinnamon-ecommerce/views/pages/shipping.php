<?php
/**
 * Shipping Information Page
 * Displays shipping rates by country and weight brackets
 * Requirements: 14.1 (display shipping rates), 14.4 (delivery estimation)
 */
?>

<?php include __DIR__ . '/../layouts/header.php'; ?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <h1 class="mb-4">Shipping Information</h1>
            
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">Check Shipping Rates</h5>
                    <p class="text-muted">Select your country to see available shipping options and rates.</p>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <label for="country-select" class="form-label">Select Country</label>
                            <select class="form-select" id="country-select">
                                <option value="">-- Select Country --</option>
                                <?php foreach ($countries as $code => $name): ?>
                                    <option value="<?= $code ?>"><?= htmlspecialchars($name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div id="shipping-rates" class="d-none">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Available Shipping Methods</h5>
                    </div>
                    <div class="card-body" id="shipping-methods-container">
                        <!-- Shipping methods will be loaded here -->
                    </div>
                </div>
            </div>

            <div id="no-shipping" class="alert alert-warning d-none">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <span id="no-shipping-message">Shipping is not available to your selected country.</span>
            </div>

            <!-- General Shipping Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">General Information</h5>
                </div>
                <div class="card-body">
                    <h6>Processing Time</h6>
                    <p>Orders are typically processed within 1-2 business days. During peak seasons, processing may take up to 3 business days.</p>
                    
                    <h6>Delivery Times</h6>
                    <p>Delivery times vary based on your location and selected shipping method. Estimated delivery times are displayed at checkout and do not include processing time.</p>
                    
                    <h6>Tracking</h6>
                    <p>Once your order ships, you will receive an email with tracking information. You can also track your order using the order number on our <a href="/order/track">Order Tracking</a> page.</p>
                    
                    <h6>International Shipping</h6>
                    <p>We ship to many countries worldwide. International orders may be subject to customs duties and taxes, which are the responsibility of the recipient.</p>
                </div>
            </div>

            <!-- FAQ -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Frequently Asked Questions</h5>
                </div>
                <div class="card-body">
                    <div class="accordion" id="shippingFaq">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                    How is shipping cost calculated?
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#shippingFaq">
                                <div class="accordion-body">
                                    Shipping costs are calculated based on your delivery location and the total weight of your order. Some shipping methods offer free shipping for orders above a certain amount.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                    Can I change my shipping address after placing an order?
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#shippingFaq">
                                <div class="accordion-body">
                                    If your order hasn't shipped yet, please contact us immediately and we'll do our best to update the shipping address. Once an order has shipped, the address cannot be changed.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                    What if my package is lost or damaged?
                                </button>
                            </h2>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#shippingFaq">
                                <div class="accordion-body">
                                    If your package is lost or arrives damaged, please contact us within 7 days of the expected delivery date. We'll work with the shipping carrier to resolve the issue and ensure you receive your order.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('country-select').addEventListener('change', function() {
    const country = this.value;
    const ratesContainer = document.getElementById('shipping-rates');
    const noShipping = document.getElementById('no-shipping');
    const methodsContainer = document.getElementById('shipping-methods-container');
    
    if (!country) {
        ratesContainer.classList.add('d-none');
        noShipping.classList.add('d-none');
        return;
    }
    
    // Fetch shipping rates for selected country
    fetch('/api/shipping/rates?country=' + encodeURIComponent(country))
        .then(response => response.json())
        .then(data => {
            if (data.available && data.methods && data.methods.length > 0) {
                ratesContainer.classList.remove('d-none');
                noShipping.classList.add('d-none');
                
                let html = '<div class="table-responsive"><table class="table">';
                html += '<thead><tr><th>Method</th><th>Delivery Time</th><th>Starting From</th><th>Free Shipping</th></tr></thead>';
                html += '<tbody>';
                
                data.methods.forEach(method => {
                    html += '<tr>';
                    html += '<td><strong>' + escapeHtml(method.name) + '</strong>';
                    if (method.description) {
                        html += '<br><small class="text-muted">' + escapeHtml(method.description) + '</small>';
                    }
                    html += '</td>';
                    html += '<td>' + (method.delivery_time || 'Varies') + '</td>';
                    html += '<td>$' + method.base_cost.toFixed(2);
                    if (method.cost_per_kg > 0) {
                        html += ' + $' + method.cost_per_kg.toFixed(2) + '/kg';
                    }
                    html += '</td>';
                    html += '<td>';
                    if (method.free_shipping_text) {
                        html += '<span class="badge bg-success">' + escapeHtml(method.free_shipping_text) + '</span>';
                    } else {
                        html += '<span class="text-muted">-</span>';
                    }
                    html += '</td>';
                    html += '</tr>';
                    
                    // Show weight brackets if available
                    if (method.weight_brackets && method.weight_brackets.length > 0) {
                        html += '<tr class="table-light"><td colspan="4">';
                        html += '<small><strong>Weight-based pricing:</strong> ';
                        method.weight_brackets.forEach((bracket, index) => {
                            if (index > 0) html += ' | ';
                            html += bracket.range_text + ': $' + bracket.cost.toFixed(2);
                        });
                        html += '</small></td></tr>';
                    }
                });
                
                html += '</tbody></table></div>';
                methodsContainer.innerHTML = html;
            } else {
                ratesContainer.classList.add('d-none');
                noShipping.classList.remove('d-none');
                document.getElementById('no-shipping-message').textContent = 
                    data.message || 'Shipping is not available to your selected country.';
            }
        })
        .catch(error => {
            console.error('Error fetching shipping rates:', error);
            ratesContainer.classList.add('d-none');
            noShipping.classList.remove('d-none');
            document.getElementById('no-shipping-message').textContent = 
                'Unable to load shipping rates. Please try again later.';
        });
});

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
