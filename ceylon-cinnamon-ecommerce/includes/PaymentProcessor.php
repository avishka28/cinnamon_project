<?php
/**
 * Payment Processor Class
 * Handles payment processing through multiple gateways
 * 
 * Requirements:
 * - 4.1: Process payment through Stripe API in demo mode
 * - 4.2: Process payment through PayPal API in demo mode
 * - 4.3: Support bank transfer with bank details
 * - 4.4: Do NOT store credit card information on server
 */

declare(strict_types=1);

class PaymentProcessor
{
    /** @var array Payment result structure */
    private array $result = [
        'success' => false,
        'transaction_id' => null,
        'error' => null,
        'payment_method' => null,
        'amount' => 0.0
    ];

    /**
     * Process payment based on method
     * 
     * @param string $method Payment method (stripe, paypal, bank_transfer)
     * @param float $amount Amount to charge
     * @param array $paymentData Payment-specific data
     * @param array $orderData Order information
     * @return array Payment result
     */
    public function process(string $method, float $amount, array $paymentData, array $orderData): array
    {
        $this->result['payment_method'] = $method;
        $this->result['amount'] = $amount;

        return match ($method) {
            'stripe' => $this->processStripe($amount, $paymentData, $orderData),
            'paypal' => $this->processPayPal($amount, $paymentData, $orderData),
            'bank_transfer' => $this->processBankTransfer($amount, $orderData),
            default => $this->createError('Invalid payment method')
        };
    }

    /**
     * Process Stripe payment
     * Requirement 4.1: Process payment through Stripe API in demo mode
     * Requirement 4.4: Do NOT store credit card information
     * 
     * @param float $amount Amount in dollars
     * @param array $paymentData Contains 'token' from Stripe.js
     * @param array $orderData Order information
     * @return array Payment result
     */
    public function processStripe(float $amount, array $paymentData, array $orderData): array
    {
        // Validate required data
        if (empty($paymentData['token'])) {
            return $this->createError('Payment token is required');
        }

        $secretKey = STRIPE_SECRET_KEY;
        if (empty($secretKey)) {
            return $this->createError('Stripe is not configured');
        }

        try {
            // Stripe API endpoint for creating charges
            $url = 'https://api.stripe.com/v1/charges';
            
            $postData = [
                'amount' => (int) ($amount * 100), // Convert to cents
                'currency' => 'usd',
                'source' => $paymentData['token'],
                'description' => 'Ceylon Cinnamon Order: ' . ($orderData['order_number'] ?? 'New Order'),
                'metadata' => [
                    'order_number' => $orderData['order_number'] ?? '',
                    'customer_email' => $orderData['email'] ?? ''
                ]
            ];

            $response = $this->makeStripeRequest($url, $postData, $secretKey);

            if (isset($response['id']) && $response['status'] === 'succeeded') {
                return $this->createSuccess($response['id']);
            }

            // Handle Stripe error response
            $errorMessage = $response['error']['message'] ?? 'Payment failed';
            return $this->createError($errorMessage);

        } catch (Exception $e) {
            return $this->createError('Payment processing error: ' . $e->getMessage());
        }
    }

    /**
     * Process PayPal payment
     * Requirement 4.2: Process payment through PayPal API in demo mode
     * 
     * @param float $amount Amount in dollars
     * @param array $paymentData Contains 'order_id' from PayPal checkout
     * @param array $orderData Order information
     * @return array Payment result
     */
    public function processPayPal(float $amount, array $paymentData, array $orderData): array
    {
        // Validate required data
        if (empty($paymentData['order_id'])) {
            return $this->createError('PayPal order ID is required');
        }

        $clientId = PAYPAL_CLIENT_ID;
        $secret = PAYPAL_SECRET;
        
        if (empty($clientId) || empty($secret)) {
            return $this->createError('PayPal is not configured');
        }

        try {
            // Get PayPal API base URL based on mode
            $baseUrl = PAYPAL_MODE === 'sandbox' 
                ? 'https://api-m.sandbox.paypal.com' 
                : 'https://api-m.paypal.com';

            // Get access token
            $accessToken = $this->getPayPalAccessToken($baseUrl, $clientId, $secret);
            if (!$accessToken) {
                return $this->createError('Failed to authenticate with PayPal');
            }

            // Capture the payment
            $captureUrl = "{$baseUrl}/v2/checkout/orders/{$paymentData['order_id']}/capture";
            $response = $this->makePayPalRequest($captureUrl, [], $accessToken, 'POST');

            if (isset($response['status']) && $response['status'] === 'COMPLETED') {
                $transactionId = $response['purchase_units'][0]['payments']['captures'][0]['id'] ?? $response['id'];
                return $this->createSuccess($transactionId);
            }

            $errorMessage = $response['message'] ?? 'PayPal payment failed';
            return $this->createError($errorMessage);

        } catch (Exception $e) {
            return $this->createError('PayPal processing error: ' . $e->getMessage());
        }
    }

    /**
     * Process bank transfer payment
     * Requirement 4.3: Support bank transfer with bank details, mark order as pending
     * 
     * @param float $amount Amount
     * @param array $orderData Order information
     * @return array Payment result with bank details
     */
    public function processBankTransfer(float $amount, array $orderData): array
    {
        // Generate a unique reference for the bank transfer
        $reference = 'BT-' . strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 10));
        
        $bankDetails = $this->getBankDetails();
        
        return [
            'success' => true,
            'transaction_id' => $reference,
            'error' => null,
            'payment_method' => 'bank_transfer',
            'amount' => $amount,
            'status' => 'pending', // Bank transfers are always pending until confirmed
            'bank_details' => $bankDetails,
            'reference' => $reference,
            'instructions' => "Please transfer {$amount} USD to the bank account below. " .
                "Use reference: {$reference} in your transfer description."
        ];
    }

    /**
     * Get bank details for bank transfer
     * 
     * @return array Bank account details
     */
    public function getBankDetails(): array
    {
        return [
            'bank_name' => 'Ceylon National Bank',
            'account_name' => 'Ceylon Cinnamon Exports Ltd',
            'account_number' => '1234567890',
            'routing_number' => '021000021',
            'swift_code' => 'CNBKLKLX',
            'currency' => 'USD'
        ];
    }

    /**
     * Create PayPal order for checkout
     * Used to initialize PayPal checkout on the frontend
     * 
     * @param float $amount Order amount
     * @param array $orderData Order details
     * @return array PayPal order creation result
     */
    public function createPayPalOrder(float $amount, array $orderData): array
    {
        $clientId = PAYPAL_CLIENT_ID;
        $secret = PAYPAL_SECRET;
        
        if (empty($clientId) || empty($secret)) {
            return $this->createError('PayPal is not configured');
        }

        try {
            $baseUrl = PAYPAL_MODE === 'sandbox' 
                ? 'https://api-m.sandbox.paypal.com' 
                : 'https://api-m.paypal.com';

            $accessToken = $this->getPayPalAccessToken($baseUrl, $clientId, $secret);
            if (!$accessToken) {
                return $this->createError('Failed to authenticate with PayPal');
            }

            $orderPayload = [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'amount' => [
                        'currency_code' => 'USD',
                        'value' => number_format($amount, 2, '.', '')
                    ],
                    'description' => 'Ceylon Cinnamon Order'
                ]]
            ];

            $response = $this->makePayPalRequest(
                "{$baseUrl}/v2/checkout/orders",
                $orderPayload,
                $accessToken,
                'POST'
            );

            if (isset($response['id'])) {
                return [
                    'success' => true,
                    'order_id' => $response['id'],
                    'status' => $response['status']
                ];
            }

            return $this->createError($response['message'] ?? 'Failed to create PayPal order');

        } catch (Exception $e) {
            return $this->createError('PayPal error: ' . $e->getMessage());
        }
    }

    /**
     * Verify payment status (for webhooks or confirmation)
     * 
     * @param string $method Payment method
     * @param string $transactionId Transaction ID
     * @return array Verification result
     */
    public function verifyPayment(string $method, string $transactionId): array
    {
        return match ($method) {
            'stripe' => $this->verifyStripePayment($transactionId),
            'paypal' => $this->verifyPayPalPayment($transactionId),
            'bank_transfer' => ['success' => true, 'status' => 'pending'],
            default => $this->createError('Invalid payment method')
        };
    }

    /**
     * Verify Stripe payment
     */
    private function verifyStripePayment(string $chargeId): array
    {
        $secretKey = STRIPE_SECRET_KEY;
        if (empty($secretKey)) {
            return $this->createError('Stripe is not configured');
        }

        try {
            $url = "https://api.stripe.com/v1/charges/{$chargeId}";
            $response = $this->makeStripeRequest($url, [], $secretKey, 'GET');

            if (isset($response['id']) && $response['paid'] === true) {
                return [
                    'success' => true,
                    'status' => 'paid',
                    'transaction_id' => $response['id']
                ];
            }

            return $this->createError('Payment not verified');

        } catch (Exception $e) {
            return $this->createError('Verification error: ' . $e->getMessage());
        }
    }

    /**
     * Verify PayPal payment
     */
    private function verifyPayPalPayment(string $orderId): array
    {
        $clientId = PAYPAL_CLIENT_ID;
        $secret = PAYPAL_SECRET;
        
        if (empty($clientId) || empty($secret)) {
            return $this->createError('PayPal is not configured');
        }

        try {
            $baseUrl = PAYPAL_MODE === 'sandbox' 
                ? 'https://api-m.sandbox.paypal.com' 
                : 'https://api-m.paypal.com';

            $accessToken = $this->getPayPalAccessToken($baseUrl, $clientId, $secret);
            if (!$accessToken) {
                return $this->createError('Failed to authenticate with PayPal');
            }

            $response = $this->makePayPalRequest(
                "{$baseUrl}/v2/checkout/orders/{$orderId}",
                [],
                $accessToken,
                'GET'
            );

            if (isset($response['status']) && $response['status'] === 'COMPLETED') {
                return [
                    'success' => true,
                    'status' => 'paid',
                    'transaction_id' => $orderId
                ];
            }

            return [
                'success' => true,
                'status' => strtolower($response['status'] ?? 'unknown'),
                'transaction_id' => $orderId
            ];

        } catch (Exception $e) {
            return $this->createError('Verification error: ' . $e->getMessage());
        }
    }

    /**
     * Make HTTP request to Stripe API
     */
    private function makeStripeRequest(string $url, array $data, string $secretKey, string $method = 'POST'): array
    {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => $secretKey . ':',
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
        ]);

        if ($method === 'POST' && !empty($data)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            throw new Exception('Failed to connect to Stripe');
        }

        return json_decode($response, true) ?? [];
    }

    /**
     * Get PayPal access token
     */
    private function getPayPalAccessToken(string $baseUrl, string $clientId, string $secret): ?string
    {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => "{$baseUrl}/v1/oauth2/token",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_USERPWD => "{$clientId}:{$secret}",
            CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        if ($response === false) {
            return null;
        }

        $data = json_decode($response, true);
        return $data['access_token'] ?? null;
    }

    /**
     * Make HTTP request to PayPal API
     */
    private function makePayPalRequest(string $url, array $data, string $accessToken, string $method = 'POST'): array
    {
        $ch = curl_init();
        
        $headers = [
            'Content-Type: application/json',
            "Authorization: Bearer {$accessToken}"
        ];

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }

        $response = curl_exec($ch);
        curl_close($ch);

        if ($response === false) {
            throw new Exception('Failed to connect to PayPal');
        }

        return json_decode($response, true) ?? [];
    }

    /**
     * Create success result
     */
    private function createSuccess(string $transactionId): array
    {
        return [
            'success' => true,
            'transaction_id' => $transactionId,
            'error' => null,
            'payment_method' => $this->result['payment_method'],
            'amount' => $this->result['amount'],
            'status' => 'paid'
        ];
    }

    /**
     * Create error result
     */
    private function createError(string $message): array
    {
        return [
            'success' => false,
            'transaction_id' => null,
            'error' => $message,
            'payment_method' => $this->result['payment_method'],
            'amount' => $this->result['amount'],
            'status' => 'failed'
        ];
    }

    /**
     * Check if a payment method is available/configured
     */
    public function isMethodAvailable(string $method): bool
    {
        return match ($method) {
            'stripe' => !empty(STRIPE_SECRET_KEY) && !empty(STRIPE_PUBLIC_KEY),
            'paypal' => !empty(PAYPAL_CLIENT_ID) && !empty(PAYPAL_SECRET),
            'bank_transfer' => true, // Always available
            default => false
        };
    }

    /**
     * Get available payment methods
     */
    public function getAvailableMethods(): array
    {
        $methods = [];
        
        if ($this->isMethodAvailable('stripe')) {
            $methods['stripe'] = [
                'name' => 'Credit/Debit Card',
                'description' => 'Pay securely with your card via Stripe',
                'icon' => 'credit-card'
            ];
        }
        
        if ($this->isMethodAvailable('paypal')) {
            $methods['paypal'] = [
                'name' => 'PayPal',
                'description' => 'Pay with your PayPal account',
                'icon' => 'paypal'
            ];
        }
        
        // Bank transfer is always available
        $methods['bank_transfer'] = [
            'name' => 'Bank Transfer',
            'description' => 'Pay via direct bank transfer',
            'icon' => 'bank'
        ];
        
        return $methods;
    }
}
