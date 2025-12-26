<?php
/**
 * Shopping Cart Page
 * Displays cart items with quantities, prices, and total
 * 
 * Requirements:
 * - 3.2: Display cart items with quantities, prices, and total
 */

$pageTitle = 'Shopping Cart - Ceylon Cinnamon';
include VIEWS_PATH . '/layouts/header.php';

// Get flash messages if available
$successMessage = $success ?? null;
$errorMessage = $error ?? null;
?>

<div class="container py-5">
    <h1 class="mb-4">Shopping Cart</h1>

    <?php if ($successMessage): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($successMessage) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($errorMessage): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($errorMessage) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($stockIssues)): ?>
        <div class="alert alert-warning" role="alert">
            <strong>Stock Issues:</strong> Some items in your cart have availability issues.
        </div>
    <?php endif; ?>

    <?php if (empty($cart['items'])): ?>
        <div class="text-center py-5">
            <i class="bi bi-cart-x display-1 text-muted"></i>
            <h3 class="mt-3">Your cart is empty</h3>
            <p class="text-muted">Browse our products and add items to your cart.</p>
            <a href="<?= url('/products') ?>" class="btn btn-primary">Continue Shopping</a>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle" id="cart-table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th style="width: 150px;">Quantity</th>
                                        <th>Subtotal</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cart['items'] as $item): ?>
                                        <?php 
                                        $hasStockIssue = isset($stockIssues[$item['product_id']]);
                                        $stockIssue = $hasStockIssue ? $stockIssues[$item['product_id']] : null;
                                        ?>
                                        <tr class="cart-item <?= $hasStockIssue ? 'table-warning' : '' ?>" 
                                            data-product-id="<?= $item['product_id'] ?>">
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div>
                                                        <h6 class="mb-0">
                                                            <a href="<?= url('/products/' . htmlspecialchars($item['product']['slug'])) ?>" 
                                                               class="text-decoration-none">
                                                                <?= htmlspecialchars($item['product']['name']) ?>
                                                            </a>
                                                        </h6>
                                                        <?php if ($hasStockIssue): ?>
                                                            <small class="text-danger">
                                                                <?= htmlspecialchars($stockIssue['message']) ?>
                                                            </small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($item['price'] < $item['original_price']): ?>
                                                    <span class="text-decoration-line-through text-muted">
                                                        $<?= number_format($item['original_price'], 2) ?>
                                                    </span>
                                                    <span class="text-danger fw-bold">
                                                        $<?= number_format($item['price'], 2) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="fw-bold">$<?= number_format($item['price'], 2) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <form action="<?= url('/cart/update') ?>" method="POST" class="cart-update-form">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                                    <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                                                    <div class="input-group input-group-sm">
                                                        <button type="button" class="btn btn-outline-secondary qty-minus">-</button>
                                                        <input type="number" name="quantity" 
                                                               class="form-control text-center qty-input" 
                                                               value="<?= $item['quantity'] ?>" 
                                                               min="0" 
                                                               max="<?= $item['product']['stock_quantity'] ?>">
                                                        <button type="button" class="btn btn-outline-secondary qty-plus">+</button>
                                                    </div>
                                                </form>
                                            </td>
                                            <td>
                                                <span class="fw-bold item-subtotal">
                                                    $<?= number_format($item['subtotal'], 2) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <form action="<?= url('/cart/remove') ?>" method="POST" class="cart-remove-form">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                                    <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                            title="Remove item">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-3">
                    <a href="<?= url('/products') ?>" class="btn btn-outline-primary">
                        <i class="bi bi-arrow-left"></i> Continue Shopping
                    </a>
                    <form action="<?= url('/cart/clear') ?>" method="POST" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                        <button type="submit" class="btn btn-outline-danger" 
                                onclick="return confirm('Are you sure you want to clear your cart?')">
                            <i class="bi bi-trash"></i> Clear Cart
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Items (<?= $cart['total_quantity'] ?>)</span>
                            <span id="cart-subtotal">$<?= number_format($cart['subtotal'], 2) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Shipping</span>
                            <span class="text-muted">Calculated at checkout</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-3">
                            <strong>Total</strong>
                            <strong id="cart-total">$<?= number_format($cart['total'], 2) ?></strong>
                        </div>
                        
                        <?php if (empty($stockIssues)): ?>
                            <a href="<?= url('/checkout') ?>" class="btn btn-primary w-100">
                                Proceed to Checkout
                            </a>
                        <?php else: ?>
                            <button class="btn btn-secondary w-100" disabled>
                                Please resolve stock issues
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-body">
                        <h6>Secure Checkout</h6>
                        <p class="small text-muted mb-0">
                            <i class="bi bi-shield-check text-success"></i>
                            Your payment information is secure. We accept Visa, MasterCard, PayPal, and bank transfers.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Quantity buttons
    document.querySelectorAll('.qty-minus').forEach(btn => {
        btn.addEventListener('click', function() {
            const input = this.parentElement.querySelector('.qty-input');
            const currentVal = parseInt(input.value) || 0;
            if (currentVal > 0) {
                input.value = currentVal - 1;
                updateCartItem(this.closest('form'));
            }
        });
    });

    document.querySelectorAll('.qty-plus').forEach(btn => {
        btn.addEventListener('click', function() {
            const input = this.parentElement.querySelector('.qty-input');
            const currentVal = parseInt(input.value) || 0;
            const maxVal = parseInt(input.max) || 999;
            if (currentVal < maxVal) {
                input.value = currentVal + 1;
                updateCartItem(this.closest('form'));
            }
        });
    });

    // Quantity input change
    document.querySelectorAll('.qty-input').forEach(input => {
        input.addEventListener('change', function() {
            updateCartItem(this.closest('form'));
        });
    });

    function updateCartItem(form) {
        const formData = new FormData(form);
        
        fetch('<?= url('/cart/update') ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload page to update totals
                location.reload();
            } else {
                alert(data.error || 'Failed to update cart');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Submit form normally as fallback
            form.submit();
        });
    }
});
</script>

<?php include VIEWS_PATH . '/layouts/footer.php'; ?>
