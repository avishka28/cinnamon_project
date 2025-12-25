<?php
/**
 * Order Notification Service
 * Handles sending order-related email notifications
 * 
 * Requirements:
 * - 12.1: Send order confirmation email to customer
 * - 12.2: Send status update email when order status changes
 * - 12.3: Send shipping notification with tracking information
 * - 5.3: Send email notifications when order status changes
 */

declare(strict_types=1);

class OrderNotificationService
{
    private EmailService $emailService;
    private EmailTemplates $emailTemplates;
    private Order $orderModel;

    public function __construct()
    {
        $this->emailService = new EmailService();
        $this->emailTemplates = new EmailTemplates($this->emailService);
        $this->orderModel = new Order();
    }

    /**
     * Send order confirmation email
     * Requirement 12.1: Send order confirmation email to customer
     * 
     * @param string $orderNumber Order number
     * @return bool Success status
     */
    public function sendOrderConfirmation(string $orderNumber): bool
    {
        $order = $this->orderModel->findByOrderNumber($orderNumber);
        
        if (!$order) {
            $this->logError("Order not found for confirmation: {$orderNumber}");
            return false;
        }

        $items = $this->orderModel->getOrderItems((int) $order['id']);
        
        if (empty($items)) {
            $this->logError("No items found for order: {$orderNumber}");
            return false;
        }

        $subject = "Order Confirmation - {$orderNumber} | Ceylon Cinnamon";
        $htmlBody = $this->emailTemplates->orderConfirmation($order, $items);

        $result = $this->emailService->send($order['email'], $subject, $htmlBody);
        
        if ($result) {
            $this->logSuccess("Order confirmation sent for: {$orderNumber}");
        }
        
        return $result;
    }

    /**
     * Send order status update notification
     * Requirement 12.2: Send status update email when order status changes
     * Requirement 5.3: Send email notifications when order status changes
     * 
     * @param string $orderNumber Order number
     * @param string $newStatus New order status
     * @param string|null $message Optional custom message
     * @return bool Success status
     */
    public function sendStatusUpdate(string $orderNumber, string $newStatus, ?string $message = null): bool
    {
        $order = $this->orderModel->findByOrderNumber($orderNumber);
        
        if (!$order) {
            $this->logError("Order not found for status update: {$orderNumber}");
            return false;
        }

        // Use specific template for certain statuses
        switch (strtolower($newStatus)) {
            case 'shipped':
                return $this->sendShippingNotification($orderNumber);
            case 'delivered':
                return $this->sendDeliveryConfirmation($orderNumber);
            case 'cancelled':
                return $this->sendCancellationNotification($orderNumber, $message);
            default:
                // Generic status update
                $subject = "Order Update - {$orderNumber} | Ceylon Cinnamon";
                $htmlBody = $this->emailTemplates->orderStatusUpdate($order, $newStatus, $message);
                
                $result = $this->emailService->send($order['email'], $subject, $htmlBody);
                
                if ($result) {
                    $this->logSuccess("Status update sent for: {$orderNumber} (Status: {$newStatus})");
                }
                
                return $result;
        }
    }

    /**
     * Send shipping notification with tracking information
     * Requirement 12.3: Send shipping notification with tracking information
     * 
     * @param string $orderNumber Order number
     * @param string|null $trackingNumber Tracking number
     * @param string|null $carrier Shipping carrier
     * @param string|null $trackingUrl Tracking URL
     * @return bool Success status
     */
    public function sendShippingNotification(
        string $orderNumber, 
        ?string $trackingNumber = null, 
        ?string $carrier = null, 
        ?string $trackingUrl = null
    ): bool {
        $order = $this->orderModel->findByOrderNumber($orderNumber);
        
        if (!$order) {
            $this->logError("Order not found for shipping notification: {$orderNumber}");
            return false;
        }

        $subject = "Your Order Has Shipped! - {$orderNumber} | Ceylon Cinnamon";
        $htmlBody = $this->emailTemplates->shippingNotification($order, $trackingNumber, $carrier, $trackingUrl);

        $result = $this->emailService->send($order['email'], $subject, $htmlBody);
        
        if ($result) {
            $this->logSuccess("Shipping notification sent for: {$orderNumber}");
        }
        
        return $result;
    }

    /**
     * Send delivery confirmation notification
     * 
     * @param string $orderNumber Order number
     * @return bool Success status
     */
    public function sendDeliveryConfirmation(string $orderNumber): bool
    {
        $order = $this->orderModel->findByOrderNumber($orderNumber);
        
        if (!$order) {
            $this->logError("Order not found for delivery confirmation: {$orderNumber}");
            return false;
        }

        $subject = "Your Order Has Been Delivered! - {$orderNumber} | Ceylon Cinnamon";
        $htmlBody = $this->emailTemplates->deliveryConfirmation($order);

        $result = $this->emailService->send($order['email'], $subject, $htmlBody);
        
        if ($result) {
            $this->logSuccess("Delivery confirmation sent for: {$orderNumber}");
        }
        
        return $result;
    }

    /**
     * Send order cancellation notification
     * 
     * @param string $orderNumber Order number
     * @param string|null $reason Cancellation reason
     * @return bool Success status
     */
    public function sendCancellationNotification(string $orderNumber, ?string $reason = null): bool
    {
        $order = $this->orderModel->findByOrderNumber($orderNumber);
        
        if (!$order) {
            $this->logError("Order not found for cancellation notification: {$orderNumber}");
            return false;
        }

        $subject = "Order Cancelled - {$orderNumber} | Ceylon Cinnamon";
        $htmlBody = $this->emailTemplates->orderCancellation($order, $reason);

        $result = $this->emailService->send($order['email'], $subject, $htmlBody);
        
        if ($result) {
            $this->logSuccess("Cancellation notification sent for: {$orderNumber}");
        }
        
        return $result;
    }

    /**
     * Send notification for order status change
     * This is a convenience method that determines the appropriate notification type
     * 
     * @param int $orderId Order ID
     * @param string $oldStatus Previous status
     * @param string $newStatus New status
     * @param array $additionalData Additional data (tracking info, etc.)
     * @return bool Success status
     */
    public function notifyStatusChange(
        int $orderId, 
        string $oldStatus, 
        string $newStatus, 
        array $additionalData = []
    ): bool {
        $order = $this->orderModel->find($orderId);
        
        if (!$order) {
            $this->logError("Order not found for status change notification: {$orderId}");
            return false;
        }

        $orderNumber = $order['order_number'];

        // Don't send notification if status hasn't changed
        if ($oldStatus === $newStatus) {
            return true;
        }

        // Send appropriate notification based on new status
        switch (strtolower($newStatus)) {
            case 'shipped':
                return $this->sendShippingNotification(
                    $orderNumber,
                    $additionalData['tracking_number'] ?? null,
                    $additionalData['carrier'] ?? null,
                    $additionalData['tracking_url'] ?? null
                );
            
            case 'delivered':
                return $this->sendDeliveryConfirmation($orderNumber);
            
            case 'cancelled':
                return $this->sendCancellationNotification(
                    $orderNumber,
                    $additionalData['reason'] ?? null
                );
            
            default:
                return $this->sendStatusUpdate(
                    $orderNumber,
                    $newStatus,
                    $additionalData['message'] ?? null
                );
        }
    }

    /**
     * Check if email notifications are enabled
     * 
     * @return bool True if email notifications are enabled
     */
    public function isEnabled(): bool
    {
        // Check if SMTP is configured
        return !empty(SMTP_HOST) && !empty(SMTP_FROM_EMAIL);
    }

    /**
     * Get the email service instance
     * 
     * @return EmailService
     */
    public function getEmailService(): EmailService
    {
        return $this->emailService;
    }

    /**
     * Get the email templates instance
     * 
     * @return EmailTemplates
     */
    public function getEmailTemplates(): EmailTemplates
    {
        return $this->emailTemplates;
    }

    /**
     * Log successful notification
     */
    private function logSuccess(string $message): void
    {
        if (defined('APP_DEBUG') && APP_DEBUG) {
            error_log("[OrderNotificationService] SUCCESS: {$message}");
        }
    }

    /**
     * Log notification error
     */
    private function logError(string $message): void
    {
        error_log("[OrderNotificationService] ERROR: {$message}");
    }
}
