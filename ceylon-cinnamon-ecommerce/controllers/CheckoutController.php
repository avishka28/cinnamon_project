<?php
/**
 * Checkout Controller
 * Handles checkout process for guest and registered users
 * 
 * Requirements:
 * - 3.3: Support both guest and registered user checkout
 * - 3.4: Process payment through Stripe or PayPal
 * - 3.5: Create order record and send confirmation email on success
 * - 4.5: Handle payment failure with cart preservation
 * - 4.6: Generate order confirmation and invoice on success
 */

declare(strict_types=1);

class CheckoutController extends Controller
{
    private Cart $cart;
    private SessionManager $sessionManager;
    private PaymentProcessor $paymentProcessor;
    private PaymentErrorHandler $errorHandler;
    private Order $orderModel;
    private ShippingCalculator $shippingCalculator;

    public function __construct()
    {
        parent::__construct();
        $this->sessionManager = new SessionManager();
        $this->cart = new Cart($this->sessionManager);
        $this->paymentProcessor = new PaymentProcessor();
        $this->errorHandler = new PaymentErrorHandler();
        $this->orderModel = new Order();
        $this->shippingCalculator = new ShippingCalculator();
    }

    /**
     * Display checkout page
     * Requirement 3.3: Support both guest and registered user checkout
     */
    public function index(): void
    {
        // Check if cart is empty
        if ($this->cart->isEmpty()) {
            $this->sessionManager->flash('error', 'Your cart is empty.');
            $this->redirect('/cart');
            return;
        }

        // Validate cart stock
        $stockIssues = $this->cart->validateStock();
        if (!empty($stockIssues)) {
            $this->sessionManager->flash('error', 'Some items in your cart are no longer available. Please review your cart.');
            $this->redirect('/cart');
            return;
        }

        // Get cart summary
        $cartSummary = $this->cart->getSummary();

        // Get logged in user data if available
        $user = null;
        if ($this->sessionManager->isLoggedIn()) {
            $userModel = new User();
            $user = $userModel->find($this->sessionManager->getUserId());
        }

        // Get available payment methods
        $paymentMethods = $this->paymentProcessor->getAvailableMethods();

        $this->view('pages.checkout', [
            'cart' => $cartSummary,
            'user' => $user,
            'paymentMethods' => $paymentMethods,
            'stripePublicKey' => STRIPE_PUBLIC_KEY,
            'paypalClientId' => PAYPAL_CLIENT_ID,
            'csrf_token' => $this->sessionManager->getCsrfToken(),
            'countries' => ShippingZone::getSupportedCountries()
        ]);
    }

    /**
     * Get available shipping methods for a country (AJAX endpoint)
     * Requirement 14.2: Calculate shipping costs based on destination and weight
     */
    public function getShippingMethods(): void
    {
        if (!$this->isAjax()) {
            $this->json(['error' => 'Invalid request'], 400);
            return;
        }

        $countryCode = $this->input('country', '');
        
        if (empty($countryCode)) {
            $this->json(['error' => 'Country is required'], 400);
            return;
        }

        // Get cart summary for weight and amount calculation
        $cartSummary = $this->cart->getSummary();
        
        if ($this->cart->isEmpty()) {
            $this->json(['error' => 'Cart is empty'], 400);
            return;
        }

        // Calculate total weight from cart items
        $totalWeight = $this->shippingCalculator->calculateTotalWeight($cartSummary['items']);
        
        // Get available shipping methods
        $result = $this->shippingCalculator->getAvailableMethods(
            $countryCode,
            $totalWeight,
            $cartSummary['subtotal']
        );

        $this->json($result);
    }

    /**
     * Calculate shipping cost for selected method (AJAX endpoint)
     * Requirement 14.2: Calculate shipping costs
     */
    public function calculateShipping(): void
    {
        if (!$this->isAjax()) {
            $this->json(['error' => 'Invalid request'], 400);
            return;
        }

        $methodId = (int) $this->input('method_id', 0);
        
        if ($methodId <= 0) {
            $this->json(['error' => 'Invalid shipping method'], 400);
            return;
        }

        $cartSummary = $this->cart->getSummary();
        
        if ($this->cart->isEmpty()) {
            $this->json(['error' => 'Cart is empty'], 400);
            return;
        }

        $totalWeight = $this->shippingCalculator->calculateTotalWeight($cartSummary['items']);
        
        $result = $this->shippingCalculator->calculateShipping(
            $methodId,
            $totalWeight,
            $cartSummary['subtotal']
        );

        if ($result['success']) {
            $result['new_total'] = $cartSummary['subtotal'] + $result['cost'];
            $result['new_total_formatted'] = '$' . number_format($result['new_total'], 2);
        }

        $this->json($result);
    }

    /**
     * Process checkout
     * Requirements: 3.3, 3.4, 3.5, 4.5, 4.6
     */
    public function process(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/checkout');
            return;
        }

        // Validate CSRF token
        if (!$this->sessionManager->validateCsrfToken($this->input('csrf_token', ''))) {
            $this->handleError('Invalid security token. Please try again.');
            return;
        }

        // Check cart is not empty
        if ($this->cart->isEmpty()) {
            $this->handleError('Your cart is empty.');
            return;
        }

        // Validate stock again before processing
        $stockIssues = $this->cart->validateStock();
        if (!empty($stockIssues)) {
            $this->handleError('Some items are no longer available. Please review your cart.');
            return;
        }

        // Validate checkout form data
        $validationResult = $this->validateCheckoutData();
        if (!$validationResult['valid']) {
            $this->handleError($validationResult['error']);
            return;
        }

        $checkoutData = $validationResult['data'];
        $cartSummary = $this->cart->getSummary();

        // Prepare order data
        $orderData = $this->prepareOrderData($checkoutData, $cartSummary);
        
        // Prepare order items
        $orderItems = $this->prepareOrderItems($cartSummary['items']);

        // Process payment based on method
        $paymentMethod = $checkoutData['payment_method'];
        $paymentData = $this->getPaymentData($paymentMethod);

        // For bank transfer, create order with pending status
        if ($paymentMethod === 'bank_transfer') {
            $this->processBankTransferOrder($orderData, $orderItems);
            return;
        }

        // Process card/PayPal payment
        $paymentResult = $this->paymentProcessor->process(
            $paymentMethod,
            $cartSummary['total'],
            $paymentData,
            $orderData
        );

        if (!$paymentResult['success']) {
            // Requirement 4.5: Payment failure - preserve cart
            // Use PaymentErrorHandler for user-friendly error messages
            $errorInfo = $this->errorHandler->handleError(
                $paymentResult['error_code'] ?? 'unknown',
                $paymentResult['error'] ?? 'Unknown error',
                $paymentMethod
            );
            
            $this->handlePaymentError($errorInfo);
            return;
        }

        // Payment successful - create order
        $orderData['payment_status'] = 'paid';
        $orderData['transaction_id'] = $paymentResult['transaction_id'];

        try {
            $orderNumber = $this->orderModel->createOrder($orderData, $orderItems);
            
            // Clear cart after successful order
            $this->cart->clear();

            // Send order confirmation email (Requirement 12.1)
            $this->sendOrderConfirmationEmail($orderNumber);

            // Store order number in session for success page
            $this->sessionManager->set('last_order_number', $orderNumber);
            $this->sessionManager->set('last_order_email', $orderData['email']);

            // Redirect to success page
            $this->redirect('/checkout/success');

        } catch (Exception $e) {
            // Order creation failed after payment - log this critical error
            error_log("Critical: Payment succeeded but order creation failed. Transaction: {$paymentResult['transaction_id']}. Error: {$e->getMessage()}");
            $this->handleError('An error occurred while creating your order. Please contact support with your payment confirmation.');
        }
    }

    /**
     * Process bank transfer order
     * Requirement 4.3: Bank transfer with pending status
     */
    private function processBankTransferOrder(array $orderData, array $orderItems): void
    {
        // Get bank transfer details
        $bankResult = $this->paymentProcessor->processBankTransfer(
            $orderData['total_amount'] ?? 0,
            $orderData
        );

        $orderData['payment_status'] = 'pending';
        $orderData['transaction_id'] = $bankResult['reference'];

        try {
            $orderNumber = $this->orderModel->createOrder($orderData, $orderItems);
            
            // Clear cart
            $this->cart->clear();

            // Send order confirmation email (Requirement 12.1)
            $this->sendOrderConfirmationEmail($orderNumber);

            // Store order info for success page
            $this->sessionManager->set('last_order_number', $orderNumber);
            $this->sessionManager->set('last_order_email', $orderData['email']);
            $this->sessionManager->set('bank_transfer_details', $bankResult);

            $this->redirect('/checkout/success');

        } catch (Exception $e) {
            $this->handleError('Failed to create order: ' . $e->getMessage());
        }
    }

    /**
     * Display order success page
     * Requirement 4.6: Generate order confirmation
     */
    public function success(): void
    {
        $orderNumber = $this->sessionManager->get('last_order_number');
        $email = $this->sessionManager->get('last_order_email');
        $bankDetails = $this->sessionManager->get('bank_transfer_details');

        if (!$orderNumber) {
            $this->redirect('/');
            return;
        }

        // Get order details
        $order = $this->orderModel->trackOrder($orderNumber, $email);

        // Clear session data
        $this->sessionManager->remove('last_order_number');
        $this->sessionManager->remove('last_order_email');
        $this->sessionManager->remove('bank_transfer_details');

        $this->view('pages.checkout_success', [
            'order' => $order,
            'bankDetails' => $bankDetails
        ]);
    }

    /**
     * Create PayPal order (AJAX endpoint)
     */
    public function createPayPalOrder(): void
    {
        if (!$this->isPost() || !$this->isAjax()) {
            $this->json(['error' => 'Invalid request'], 400);
            return;
        }

        $cartSummary = $this->cart->getSummary();
        
        if ($cartSummary['total'] <= 0) {
            $this->json(['error' => 'Invalid cart total'], 400);
            return;
        }

        $result = $this->paymentProcessor->createPayPalOrder(
            $cartSummary['total'],
            ['description' => 'Ceylon Cinnamon Order']
        );

        if ($result['success']) {
            $this->json(['orderID' => $result['order_id']]);
        } else {
            $this->json(['error' => $result['error']], 400);
        }
    }

    /**
     * Validate checkout form data
     */
    private function validateCheckoutData(): array
    {
        $errors = [];
        
        // Required fields
        $requiredFields = [
            'email' => 'Email address',
            'first_name' => 'First name',
            'last_name' => 'Last name',
            'address' => 'Shipping address',
            'city' => 'City',
            'country' => 'Country',
            'postal_code' => 'Postal code',
            'payment_method' => 'Payment method',
            'shipping_method' => 'Shipping method'
        ];

        $data = [];
        foreach ($requiredFields as $field => $label) {
            $value = trim($this->input($field, ''));
            if (empty($value)) {
                $errors[] = "{$label} is required";
            }
            $data[$field] = $this->sanitize($value);
        }

        // Validate email
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address';
        }

        // Validate payment method
        $validMethods = ['stripe', 'paypal', 'bank_transfer'];
        if (!empty($data['payment_method']) && !in_array($data['payment_method'], $validMethods)) {
            $errors[] = 'Invalid payment method';
        }

        // Validate shipping method (Requirement 14.2)
        if (!empty($data['shipping_method']) && !empty($data['country'])) {
            $cartSummary = $this->cart->getSummary();
            $totalWeight = $this->shippingCalculator->calculateTotalWeight($cartSummary['items']);
            
            $shippingValidation = $this->shippingCalculator->validateShippingMethod(
                (int) $data['shipping_method'],
                $data['country'],
                $totalWeight,
                $cartSummary['subtotal']
            );
            
            if (!$shippingValidation['valid']) {
                $errors[] = $shippingValidation['error'];
            }
        }

        // Optional fields
        $data['phone'] = $this->sanitize($this->input('phone', ''));
        $data['state'] = $this->sanitize($this->input('state', ''));
        $data['notes'] = $this->sanitize($this->input('notes', ''));
        $data['billing_same'] = $this->input('billing_same', '1') === '1';

        // Billing address (if different from shipping)
        if (!$data['billing_same']) {
            $data['billing_address'] = $this->sanitize($this->input('billing_address', ''));
            $data['billing_city'] = $this->sanitize($this->input('billing_city', ''));
            $data['billing_state'] = $this->sanitize($this->input('billing_state', ''));
            $data['billing_country'] = $this->sanitize($this->input('billing_country', ''));
            $data['billing_postal_code'] = $this->sanitize($this->input('billing_postal_code', ''));
        }

        if (!empty($errors)) {
            return ['valid' => false, 'error' => implode('. ', $errors)];
        }

        return ['valid' => true, 'data' => $data];
    }

    /**
     * Prepare order data from checkout form
     */
    private function prepareOrderData(array $checkoutData, array $cartSummary): array
    {
        // Build shipping address string
        $shippingAddress = $this->formatAddress(
            $checkoutData['address'],
            $checkoutData['city'],
            $checkoutData['state'] ?? '',
            $checkoutData['postal_code'],
            $checkoutData['country']
        );

        // Build billing address
        $billingAddress = $shippingAddress;
        if (!$checkoutData['billing_same'] && !empty($checkoutData['billing_address'])) {
            $billingAddress = $this->formatAddress(
                $checkoutData['billing_address'],
                $checkoutData['billing_city'] ?? '',
                $checkoutData['billing_state'] ?? '',
                $checkoutData['billing_postal_code'] ?? '',
                $checkoutData['billing_country'] ?? ''
            );
        }

        // Calculate shipping cost (Requirement 14.2)
        $shippingCost = 0.00;
        $shippingMethodId = (int) ($checkoutData['shipping_method'] ?? 0);
        
        if ($shippingMethodId > 0) {
            $totalWeight = $this->shippingCalculator->calculateTotalWeight($cartSummary['items']);
            $shippingResult = $this->shippingCalculator->calculateShipping(
                $shippingMethodId,
                $totalWeight,
                $cartSummary['subtotal']
            );
            
            if ($shippingResult['success']) {
                $shippingCost = $shippingResult['cost'];
            }
        }

        $totalAmount = $cartSummary['subtotal'] + $shippingCost;

        $orderData = [
            'email' => $checkoutData['email'],
            'first_name' => $checkoutData['first_name'],
            'last_name' => $checkoutData['last_name'],
            'phone' => $checkoutData['phone'] ?? '',
            'shipping_address' => $shippingAddress,
            'billing_address' => $billingAddress,
            'payment_method' => $checkoutData['payment_method'],
            'notes' => $checkoutData['notes'] ?? '',
            'subtotal' => $cartSummary['subtotal'],
            'shipping_cost' => $shippingCost,
            'tax_amount' => 0.00, // TODO: Calculate tax
            'total_amount' => $totalAmount
        ];

        // Add user ID if logged in
        if ($this->sessionManager->isLoggedIn()) {
            $orderData['user_id'] = $this->sessionManager->getUserId();
        }

        return $orderData;
    }

    /**
     * Prepare order items from cart
     */
    private function prepareOrderItems(array $cartItems): array
    {
        $items = [];
        foreach ($cartItems as $item) {
            $items[] = [
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'price' => $item['price']
            ];
        }
        return $items;
    }

    /**
     * Get payment-specific data from request
     */
    private function getPaymentData(string $method): array
    {
        return match ($method) {
            'stripe' => ['token' => $this->input('stripe_token', '')],
            'paypal' => ['order_id' => $this->input('paypal_order_id', '')],
            default => []
        };
    }

    /**
     * Format address string
     */
    private function formatAddress(string $street, string $city, string $state, string $postalCode, string $country): string
    {
        $parts = array_filter([$street, $city, $state, $postalCode, $country]);
        return implode(', ', $parts);
    }

    /**
     * Handle checkout error
     * Requirement 4.5: Preserve cart on payment failure
     */
    private function handleError(string $message): void
    {
        if ($this->isAjax()) {
            $this->json(['success' => false, 'error' => $message], 400);
        } else {
            $this->sessionManager->flash('error', $message);
            $this->redirect('/checkout');
        }
    }

    /**
     * Handle payment-specific error with detailed information
     * Requirement 4.5: Payment failure with cart preservation
     */
    private function handlePaymentError(array $errorInfo): void
    {
        $message = $errorInfo['message'];
        
        // Add suggestion if available
        if (!empty($errorInfo['suggestion'])) {
            $message .= ' ' . $errorInfo['suggestion'];
        }
        
        if ($this->isAjax()) {
            $this->json([
                'success' => false,
                'error' => $message,
                'error_code' => $errorInfo['error_code'] ?? 'unknown',
                'recoverable' => $errorInfo['recoverable'] ?? true
            ], 400);
        } else {
            $this->sessionManager->flash('error', $message);
            $this->redirect('/checkout');
        }
    }

    /**
     * Send order confirmation email
     * Requirement 12.1: Send order confirmation email to customer
     * 
     * @param string $orderNumber Order number
     */
    private function sendOrderConfirmationEmail(string $orderNumber): void
    {
        try {
            $notificationService = new OrderNotificationService();
            $notificationService->sendOrderConfirmation($orderNumber);
        } catch (Exception $e) {
            // Log error but don't fail the order process
            error_log("Failed to send order confirmation email for {$orderNumber}: " . $e->getMessage());
        }
    }
}
