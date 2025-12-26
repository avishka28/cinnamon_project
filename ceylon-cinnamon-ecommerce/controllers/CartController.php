<?php
/**
 * Cart Controller
 * Handles shopping cart operations with AJAX endpoints
 * 
 * Requirements:
 * - 3.1: Add products to cart, store in session
 * - 3.2: Display cart items with quantities, prices, and total
 * - 10.2: CSRF protection for cart operations
 */

declare(strict_types=1);

class CartController extends Controller
{
    private Cart $cart;
    private SessionManager $sessionManager;
    private Product $productModel;

    public function __construct()
    {
        parent::__construct();
        $this->sessionManager = new SessionManager();
        $this->cart = new Cart($this->sessionManager);
        $this->productModel = new Product();
    }

    /**
     * Display cart page
     * Requirement 3.2: Display cart items with quantities, prices, and total
     */
    public function index(): void
    {
        $summary = $this->cart->getSummary();
        
        // Validate stock and get any issues
        $stockIssues = $this->cart->validateStock();
        
        // Get flash messages
        $success = $this->sessionManager->getFlash('success');
        $error = $this->sessionManager->getFlash('error');
        
        $this->view('pages/cart', [
            'cart' => $summary,
            'stockIssues' => $stockIssues,
            'csrf_token' => $this->sessionManager->getCsrfToken(),
            'success' => $success,
            'error' => $error
        ]);
    }

    /**
     * Add product to cart
     * Requirement 3.1: Add products to cart
     * Requirement 10.2: CSRF protection
     */
    public function add(): void
    {
        // Handle both AJAX and form submissions
        if ($this->isAjax()) {
            $this->addAjax();
            return;
        }

        if (!$this->isPost()) {
            $this->redirect('/cart');
            return;
        }

        // Validate CSRF token (Requirement 10.2)
        if (!$this->sessionManager->validateCsrfToken($this->input('csrf_token', ''))) {
            $this->sessionManager->flash('error', 'Invalid security token. Please try again.');
            $this->redirect('/cart');
            return;
        }

        $productId = (int) $this->input('product_id', 0);
        $quantity = (int) $this->input('quantity', 1);

        if ($productId <= 0) {
            $this->sessionManager->flash('error', 'Invalid product.');
            $this->redirect('/cart');
            return;
        }

        if ($quantity < 1) {
            $quantity = 1;
        }

        if ($this->cart->add($productId, $quantity)) {
            $this->sessionManager->flash('success', 'Product added to cart.');
        } else {
            $this->sessionManager->flash('error', 'Could not add product to cart. Please check availability.');
        }

        // Redirect back to referring page or cart
        $referer = $_SERVER['HTTP_REFERER'] ?? '/cart';
        $this->redirect($referer);
    }

    /**
     * Add product to cart via AJAX
     * Requirement 3.1, 10.2
     */
    private function addAjax(): void
    {
        // For FormData submissions, get values from POST
        // For JSON submissions, get from request body
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            $input = $this->getJsonInput();
            $csrfToken = $input['csrf_token'] ?? '';
            $productId = (int) ($input['product_id'] ?? 0);
            $quantity = (int) ($input['quantity'] ?? 1);
        } else {
            // FormData submission
            $csrfToken = $this->input('csrf_token', '');
            $productId = (int) $this->input('product_id', 0);
            $quantity = (int) $this->input('quantity', 1);
        }
        
        // Validate CSRF token (Requirement 10.2)
        if (!$this->sessionManager->validateCsrfToken($csrfToken)) {
            $this->json([
                'success' => false,
                'error' => 'Invalid security token. Please refresh the page.'
            ], 403);
            return;
        }

        if ($productId <= 0) {
            $this->json([
                'success' => false,
                'error' => 'Invalid product.'
            ], 400);
            return;
        }

        if ($quantity < 1) {
            $quantity = 1;
        }

        // Check product exists
        $product = $this->productModel->find($productId);
        if (!$product) {
            $this->json([
                'success' => false,
                'error' => 'Product not found.'
            ], 404);
            return;
        }

        if ($this->cart->add($productId, $quantity)) {
            $this->json([
                'success' => true,
                'message' => 'Product added to cart.',
                'cart' => $this->cart->toArray()
            ]);
        } else {
            $this->json([
                'success' => false,
                'error' => 'Could not add product to cart. Please check availability.'
            ], 400);
        }
    }

    /**
     * Update cart item quantity
     * Requirement 10.2: CSRF protection
     */
    public function update(): void
    {
        if ($this->isAjax()) {
            $this->updateAjax();
            return;
        }

        if (!$this->isPost()) {
            $this->redirect('/cart');
            return;
        }

        // Validate CSRF token (Requirement 10.2)
        if (!$this->sessionManager->validateCsrfToken($this->input('csrf_token', ''))) {
            $this->sessionManager->flash('error', 'Invalid security token. Please try again.');
            $this->redirect('/cart');
            return;
        }

        $productId = (int) $this->input('product_id', 0);
        $quantity = (int) $this->input('quantity', 0);

        if ($productId <= 0) {
            $this->sessionManager->flash('error', 'Invalid product.');
            $this->redirect('/cart');
            return;
        }

        if ($quantity === 0) {
            // Remove item if quantity is 0
            $this->cart->remove($productId);
            $this->sessionManager->flash('success', 'Item removed from cart.');
        } elseif ($this->cart->update($productId, $quantity)) {
            $this->sessionManager->flash('success', 'Cart updated.');
        } else {
            $this->sessionManager->flash('error', 'Could not update cart. Please check availability.');
        }

        $this->redirect('/cart');
    }

    /**
     * Update cart item via AJAX
     */
    private function updateAjax(): void
    {
        // Handle both JSON and FormData submissions
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            $input = $this->getJsonInput();
            $csrfToken = $input['csrf_token'] ?? '';
            $productId = (int) ($input['product_id'] ?? 0);
            $quantity = (int) ($input['quantity'] ?? 0);
        } else {
            // FormData submission
            $csrfToken = $this->input('csrf_token', '');
            $productId = (int) $this->input('product_id', 0);
            $quantity = (int) $this->input('quantity', 0);
        }
        
        // Validate CSRF token (Requirement 10.2)
        if (!$this->sessionManager->validateCsrfToken($csrfToken)) {
            $this->json([
                'success' => false,
                'error' => 'Invalid security token. Please refresh the page.'
            ], 403);
            return;
        }

        if ($productId <= 0) {
            $this->json([
                'success' => false,
                'error' => 'Invalid product.'
            ], 400);
            return;
        }

        $success = false;
        $message = '';

        if ($quantity === 0) {
            $success = $this->cart->remove($productId);
            $message = $success ? 'Item removed from cart.' : 'Could not remove item.';
        } else {
            $success = $this->cart->update($productId, $quantity);
            $message = $success ? 'Cart updated.' : 'Could not update cart. Please check availability.';
        }

        if ($success) {
            $summary = $this->cart->getSummary();
            $this->json([
                'success' => true,
                'message' => $message,
                'cart_count' => $summary['item_count'] ?? 0,
                'cart_total' => $summary['total'] ?? 0,
                'cart' => $this->cart->toArray()
            ]);
        } else {
            $this->json([
                'success' => false,
                'error' => $message
            ], 400);
        }
    }

    /**
     * Remove item from cart
     * Requirement 10.2: CSRF protection
     */
    public function remove(): void
    {
        if ($this->isAjax()) {
            $this->removeAjax();
            return;
        }

        if (!$this->isPost()) {
            $this->redirect('/cart');
            return;
        }

        // Validate CSRF token (Requirement 10.2)
        if (!$this->sessionManager->validateCsrfToken($this->input('csrf_token', ''))) {
            $this->sessionManager->flash('error', 'Invalid security token. Please try again.');
            $this->redirect('/cart');
            return;
        }

        $productId = (int) $this->input('product_id', 0);

        if ($productId <= 0) {
            $this->sessionManager->flash('error', 'Invalid product.');
            $this->redirect('/cart');
            return;
        }

        if ($this->cart->remove($productId)) {
            $this->sessionManager->flash('success', 'Item removed from cart.');
        } else {
            $this->sessionManager->flash('error', 'Could not remove item from cart.');
        }

        $this->redirect('/cart');
    }

    /**
     * Remove item from cart via AJAX
     */
    private function removeAjax(): void
    {
        // Handle both JSON and FormData submissions
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            $input = $this->getJsonInput();
            $csrfToken = $input['csrf_token'] ?? '';
            $productId = (int) ($input['product_id'] ?? 0);
        } else {
            // FormData submission
            $csrfToken = $this->input('csrf_token', '');
            $productId = (int) $this->input('product_id', 0);
        }
        
        // Validate CSRF token (Requirement 10.2)
        if (!$this->sessionManager->validateCsrfToken($csrfToken)) {
            $this->json([
                'success' => false,
                'error' => 'Invalid security token. Please refresh the page.'
            ], 403);
            return;
        }

        if ($productId <= 0) {
            $this->json([
                'success' => false,
                'error' => 'Invalid product.'
            ], 400);
            return;
        }

        if ($this->cart->remove($productId)) {
            $summary = $this->cart->getSummary();
            $this->json([
                'success' => true,
                'message' => 'Item removed from cart.',
                'cart_count' => $summary['item_count'] ?? 0,
                'cart_total' => $summary['total'] ?? 0,
                'cart' => $this->cart->toArray()
            ]);
        } else {
            $this->json([
                'success' => false,
                'error' => 'Could not remove item from cart.'
            ], 400);
        }
    }

    /**
     * Clear entire cart
     */
    public function clear(): void
    {
        if ($this->isAjax()) {
            $this->clearAjax();
            return;
        }

        if (!$this->isPost()) {
            $this->redirect('/cart');
            return;
        }

        // Validate CSRF token (Requirement 10.2)
        if (!$this->sessionManager->validateCsrfToken($this->input('csrf_token', ''))) {
            $this->sessionManager->flash('error', 'Invalid security token. Please try again.');
            $this->redirect('/cart');
            return;
        }

        $this->cart->clear();
        $this->sessionManager->flash('success', 'Cart cleared.');
        $this->redirect('/cart');
    }

    /**
     * Clear cart via AJAX
     */
    private function clearAjax(): void
    {
        $input = $this->getJsonInput();
        
        // Validate CSRF token (Requirement 10.2)
        $csrfToken = $input['csrf_token'] ?? $this->input('csrf_token', '');
        if (!$this->sessionManager->validateCsrfToken($csrfToken)) {
            $this->json([
                'success' => false,
                'error' => 'Invalid security token. Please refresh the page.'
            ], 403);
            return;
        }

        $this->cart->clear();
        
        $this->json([
            'success' => true,
            'message' => 'Cart cleared.',
            'cart' => $this->cart->toArray()
        ]);
    }

    /**
     * Get cart data as JSON (for AJAX polling)
     */
    public function getCart(): void
    {
        $this->json([
            'success' => true,
            'cart' => $this->cart->toArray()
        ]);
    }

    /**
     * Get cart count (for header display)
     */
    public function getCount(): void
    {
        $this->json([
            'success' => true,
            'count' => $this->cart->getTotalQuantity(),
            'item_count' => $this->cart->getItemCount()
        ]);
    }

    /**
     * Get JSON input from request body
     */
    private function getJsonInput(): array
    {
        $rawInput = file_get_contents('php://input');
        if (empty($rawInput)) {
            return [];
        }
        
        $decoded = json_decode($rawInput, true);
        return is_array($decoded) ? $decoded : [];
    }
}
