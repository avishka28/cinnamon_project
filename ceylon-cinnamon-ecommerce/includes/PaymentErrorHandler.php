<?php
/**
 * Payment Error Handler
 * Handles payment failures and error logging
 * 
 * Requirements:
 * - 4.5: Handle payment failure with cart preservation
 * - 4.6: Generate order confirmation on success
 */

declare(strict_types=1);

class PaymentErrorHandler
{
    /** @var array Error codes and user-friendly messages */
    private const ERROR_MESSAGES = [
        // Stripe error codes
        'card_declined' => 'Your card was declined. Please try a different card.',
        'insufficient_funds' => 'Insufficient funds. Please try a different card.',
        'expired_card' => 'Your card has expired. Please use a different card.',
        'incorrect_cvc' => 'The security code (CVC) is incorrect.',
        'incorrect_number' => 'The card number is incorrect.',
        'invalid_expiry_month' => 'The expiration month is invalid.',
        'invalid_expiry_year' => 'The expiration year is invalid.',
        'processing_error' => 'An error occurred while processing your card. Please try again.',
        'rate_limit' => 'Too many requests. Please wait a moment and try again.',
        
        // PayPal error codes
        'INSTRUMENT_DECLINED' => 'Your payment method was declined. Please try a different method.',
        'PAYER_ACTION_REQUIRED' => 'Additional action is required to complete this payment.',
        'PAYEE_BLOCKED_TRANSACTION' => 'This transaction cannot be processed.',
        
        // Generic errors
        'authentication_failed' => 'Payment authentication failed. Please contact support.',
        'gateway_unavailable' => 'Payment service is temporarily unavailable. Please try again later.',
        'invalid_amount' => 'Invalid payment amount.',
        'currency_not_supported' => 'This currency is not supported.',
        'default' => 'Payment could not be processed. Please try again or use a different payment method.'
    ];

    /**
     * Handle payment error and return user-friendly message
     * 
     * @param string $errorCode Error code from payment gateway
     * @param string $rawMessage Raw error message
     * @param string $paymentMethod Payment method used
     * @return array Processed error information
     */
    public function handleError(string $errorCode, string $rawMessage, string $paymentMethod): array
    {
        // Log the error for debugging
        $this->logError($errorCode, $rawMessage, $paymentMethod);
        
        // Get user-friendly message
        $userMessage = $this->getUserMessage($errorCode, $rawMessage);
        
        // Determine if error is recoverable
        $isRecoverable = $this->isRecoverableError($errorCode);
        
        return [
            'success' => false,
            'error_code' => $errorCode,
            'message' => $userMessage,
            'recoverable' => $isRecoverable,
            'suggestion' => $this->getSuggestion($errorCode, $paymentMethod)
        ];
    }

    /**
     * Get user-friendly error message
     */
    private function getUserMessage(string $errorCode, string $rawMessage): string
    {
        // Check for known error codes
        if (isset(self::ERROR_MESSAGES[$errorCode])) {
            return self::ERROR_MESSAGES[$errorCode];
        }
        
        // Check for partial matches in raw message
        $lowerMessage = strtolower($rawMessage);
        
        if (str_contains($lowerMessage, 'declined')) {
            return self::ERROR_MESSAGES['card_declined'];
        }
        
        if (str_contains($lowerMessage, 'insufficient')) {
            return self::ERROR_MESSAGES['insufficient_funds'];
        }
        
        if (str_contains($lowerMessage, 'expired')) {
            return self::ERROR_MESSAGES['expired_card'];
        }
        
        if (str_contains($lowerMessage, 'cvc') || str_contains($lowerMessage, 'cvv')) {
            return self::ERROR_MESSAGES['incorrect_cvc'];
        }
        
        // Return default message
        return self::ERROR_MESSAGES['default'];
    }

    /**
     * Check if error is recoverable (user can retry)
     */
    private function isRecoverableError(string $errorCode): bool
    {
        $nonRecoverableErrors = [
            'authentication_failed',
            'gateway_unavailable',
            'PAYEE_BLOCKED_TRANSACTION'
        ];
        
        return !in_array($errorCode, $nonRecoverableErrors);
    }

    /**
     * Get suggestion for resolving the error
     */
    private function getSuggestion(string $errorCode, string $paymentMethod): string
    {
        $suggestions = [
            'card_declined' => 'Try using a different card or contact your bank.',
            'insufficient_funds' => 'Use a different card or try a smaller amount.',
            'expired_card' => 'Update your card information or use a different card.',
            'incorrect_cvc' => 'Check the 3-digit code on the back of your card.',
            'incorrect_number' => 'Double-check your card number.',
            'processing_error' => 'Wait a moment and try again.',
            'rate_limit' => 'Please wait 30 seconds before trying again.',
            'INSTRUMENT_DECLINED' => 'Try a different PayPal account or payment method.',
            'gateway_unavailable' => 'Try again in a few minutes or use bank transfer.'
        ];
        
        if (isset($suggestions[$errorCode])) {
            return $suggestions[$errorCode];
        }
        
        // Default suggestion based on payment method
        return match ($paymentMethod) {
            'stripe' => 'Try a different card or use PayPal.',
            'paypal' => 'Try a different PayPal account or use a credit card.',
            default => 'Please try a different payment method.'
        };
    }

    /**
     * Log payment error for debugging
     */
    private function logError(string $errorCode, string $rawMessage, string $paymentMethod): void
    {
        $logMessage = sprintf(
            "[%s] Payment Error - Method: %s, Code: %s, Message: %s",
            date('Y-m-d H:i:s'),
            $paymentMethod,
            $errorCode,
            $rawMessage
        );
        
        error_log($logMessage);
    }

    /**
     * Check if payment should be retried automatically
     */
    public function shouldRetry(string $errorCode, int $attemptCount): bool
    {
        // Only retry certain errors and limit attempts
        $retryableErrors = ['processing_error', 'rate_limit', 'gateway_unavailable'];
        
        return in_array($errorCode, $retryableErrors) && $attemptCount < 3;
    }
}
