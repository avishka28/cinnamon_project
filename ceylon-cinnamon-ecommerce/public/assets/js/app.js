/**
 * Ceylon Cinnamon E-commerce JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize cart functionality
    initCart();
});

function initCart() {
    // Add to cart buttons
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.dataset.productId;
            const quantity = this.dataset.quantity || 1;
            addToCart(productId, quantity);
        });
    });
}

async function addToCart(productId, quantity) {
    try {
        const response = await fetch('/api/cart/add', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ product_id: productId, quantity: quantity })
        });
        
        const data = await response.json();
        
        if (data.success) {
            updateCartCount(data.cart_count);
            showNotification('Product added to cart!', 'success');
        } else {
            showNotification(data.error || 'Failed to add product', 'error');
        }
    } catch (error) {
        showNotification('An error occurred', 'error');
    }
}

function updateCartCount(count) {
    const cartBadge = document.querySelector('.cart-count');
    if (cartBadge) {
        cartBadge.textContent = count;
    }
}

function showNotification(message, type) {
    // Simple notification - can be enhanced with a toast library
    alert(message);
}
