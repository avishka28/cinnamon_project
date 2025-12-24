<?php
/**
 * Cart Model
 * Handles shopping cart operations with session-based storage
 * 
 * Requirements:
 * - 3.1: Add products to cart, store in session or database
 * - 3.2: Display cart items with quantities, prices, and total
 */

declare(strict_types=1);

class Cart
{
    private const SESSION_KEY = 'cart';
    /** @var SessionManager|object Session manager or compatible mock */
    private $session;
    private Product $productModel;

    /**
     * @param SessionManager|object|null $session Session manager or compatible mock for testing
     */
    public function __construct($session = null)
    {
        $this->session = $session ?? new SessionManager();
        $this->session->start();
        $this->productModel = new Product();
        
        // Initialize cart if not exists
        if (!$this->session->has(self::SESSION_KEY)) {
            $this->session->set(self::SESSION_KEY, []);
        }
    }

    /**
     * Add a product to the cart
     * Requirement 3.1: Add products to cart
     * 
     * @param int $productId Product ID
     * @param int $quantity Quantity to add
     * @return bool Success
     */
    public function add(int $productId, int $quantity = 1): bool
    {
        if ($quantity < 1) {
            return false;
        }

        // Verify product exists and is active
        $product = $this->productModel->find($productId);
        if (!$product || !$product['is_active']) {
            return false;
        }

        // Check stock availability
        if ($product['stock_quantity'] < $quantity) {
            return false;
        }

        $cart = $this->getItems();
        
        // Check if product already in cart
        if (isset($cart[$productId])) {
            $newQuantity = $cart[$productId]['quantity'] + $quantity;
            
            // Verify stock for new quantity
            if ($product['stock_quantity'] < $newQuantity) {
                return false;
            }
            
            $cart[$productId]['quantity'] = $newQuantity;
        } else {
            $cart[$productId] = [
                'product_id' => $productId,
                'quantity' => $quantity,
                'added_at' => time()
            ];
        }

        $this->session->set(self::SESSION_KEY, $cart);
        return true;
    }

    /**
     * Update quantity of a cart item
     * 
     * @param int $productId Product ID
     * @param int $quantity New quantity
     * @return bool Success
     */
    public function update(int $productId, int $quantity): bool
    {
        if ($quantity < 0) {
            return false;
        }

        // If quantity is 0, remove the item
        if ($quantity === 0) {
            return $this->remove($productId);
        }

        $cart = $this->getItems();
        
        if (!isset($cart[$productId])) {
            return false;
        }

        // Verify stock availability
        $product = $this->productModel->find($productId);
        if (!$product || $product['stock_quantity'] < $quantity) {
            return false;
        }

        $cart[$productId]['quantity'] = $quantity;
        $this->session->set(self::SESSION_KEY, $cart);
        return true;
    }

    /**
     * Remove a product from the cart
     * 
     * @param int $productId Product ID
     * @return bool Success
     */
    public function remove(int $productId): bool
    {
        $cart = $this->getItems();
        
        if (!isset($cart[$productId])) {
            return false;
        }

        unset($cart[$productId]);
        $this->session->set(self::SESSION_KEY, $cart);
        return true;
    }

    /**
     * Clear all items from the cart
     */
    public function clear(): void
    {
        $this->session->set(self::SESSION_KEY, []);
    }

    /**
     * Get raw cart items (product IDs and quantities)
     * 
     * @return array Cart items
     */
    public function getItems(): array
    {
        return $this->session->get(self::SESSION_KEY, []);
    }

    /**
     * Get cart items with full product details
     * Requirement 3.2: Display cart items with quantities, prices, and total
     * 
     * @return array Cart items with product details
     */
    public function getItemsWithDetails(): array
    {
        $cart = $this->getItems();
        $items = [];

        foreach ($cart as $productId => $item) {
            $product = $this->productModel->find($productId);
            
            if ($product && $product['is_active']) {
                $price = $product['sale_price'] ?? $product['price'];
                $items[] = [
                    'product_id' => $productId,
                    'product' => $product,
                    'quantity' => $item['quantity'],
                    'price' => (float) $price,
                    'original_price' => (float) $product['price'],
                    'subtotal' => (float) $price * $item['quantity'],
                    'added_at' => $item['added_at']
                ];
            }
        }

        return $items;
    }

    /**
     * Get cart summary with totals
     * Requirement 3.2: Display total
     * 
     * @return array Cart summary
     */
    public function getSummary(): array
    {
        $items = $this->getItemsWithDetails();
        
        $subtotal = 0.0;
        $itemCount = 0;
        $totalQuantity = 0;

        foreach ($items as $item) {
            $subtotal += $item['subtotal'];
            $itemCount++;
            $totalQuantity += $item['quantity'];
        }

        return [
            'items' => $items,
            'item_count' => $itemCount,
            'total_quantity' => $totalQuantity,
            'subtotal' => $subtotal,
            'total' => $subtotal // Can add shipping/tax later
        ];
    }

    /**
     * Get total price of all items in cart
     * 
     * @return float Total price
     */
    public function getTotal(): float
    {
        return $this->getSummary()['total'];
    }

    /**
     * Get total number of items in cart
     * 
     * @return int Item count
     */
    public function getItemCount(): int
    {
        return $this->getSummary()['item_count'];
    }

    /**
     * Get total quantity of all items
     * 
     * @return int Total quantity
     */
    public function getTotalQuantity(): int
    {
        $cart = $this->getItems();
        $total = 0;
        
        foreach ($cart as $item) {
            $total += $item['quantity'];
        }
        
        return $total;
    }

    /**
     * Check if cart is empty
     * 
     * @return bool True if empty
     */
    public function isEmpty(): bool
    {
        return empty($this->getItems());
    }

    /**
     * Check if a product is in the cart
     * 
     * @param int $productId Product ID
     * @return bool True if in cart
     */
    public function hasProduct(int $productId): bool
    {
        $cart = $this->getItems();
        return isset($cart[$productId]);
    }

    /**
     * Get quantity of a specific product in cart
     * 
     * @param int $productId Product ID
     * @return int Quantity (0 if not in cart)
     */
    public function getProductQuantity(int $productId): int
    {
        $cart = $this->getItems();
        return $cart[$productId]['quantity'] ?? 0;
    }

    /**
     * Validate cart items against current stock
     * Returns items that are no longer available or have insufficient stock
     * 
     * @return array Invalid items with reasons
     */
    public function validateStock(): array
    {
        $cart = $this->getItems();
        $invalid = [];

        foreach ($cart as $productId => $item) {
            $product = $this->productModel->find($productId);
            
            if (!$product) {
                $invalid[$productId] = [
                    'reason' => 'product_not_found',
                    'message' => 'Product no longer exists'
                ];
            } elseif (!$product['is_active']) {
                $invalid[$productId] = [
                    'reason' => 'product_inactive',
                    'message' => 'Product is no longer available'
                ];
            } elseif ($product['stock_quantity'] < $item['quantity']) {
                $invalid[$productId] = [
                    'reason' => 'insufficient_stock',
                    'message' => "Only {$product['stock_quantity']} available",
                    'available' => $product['stock_quantity']
                ];
            }
        }

        return $invalid;
    }

    /**
     * Remove invalid items from cart
     * 
     * @return int Number of items removed
     */
    public function removeInvalidItems(): int
    {
        $invalid = $this->validateStock();
        $removed = 0;

        foreach (array_keys($invalid) as $productId) {
            if ($this->remove($productId)) {
                $removed++;
            }
        }

        return $removed;
    }

    /**
     * Get cart data for JSON response
     * 
     * @return array Cart data
     */
    public function toArray(): array
    {
        $summary = $this->getSummary();
        
        return [
            'items' => array_map(function ($item) {
                return [
                    'product_id' => $item['product_id'],
                    'name' => $item['product']['name'],
                    'slug' => $item['product']['slug'],
                    'price' => $item['price'],
                    'original_price' => $item['original_price'],
                    'quantity' => $item['quantity'],
                    'subtotal' => $item['subtotal'],
                    'stock_quantity' => $item['product']['stock_quantity']
                ];
            }, $summary['items']),
            'item_count' => $summary['item_count'],
            'total_quantity' => $summary['total_quantity'],
            'subtotal' => $summary['subtotal'],
            'total' => $summary['total']
        ];
    }
}
