<?php
/**
 * Email Templates Class
 * Generates HTML email templates for order notifications
 * 
 * Requirements:
 * - 12.1: Order confirmation email
 * - 12.2: Order status update email
 * - 12.3: Shipping notification with tracking
 * - 12.5: Include order details and company branding
 */

declare(strict_types=1);

class EmailTemplates
{
    private EmailService $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * Generate order confirmation email HTML
     * Requirement 12.1: Order confirmation email with order details
     * 
     * @param array $order Order data
     * @param array $items Order items
     * @return string HTML email content
     */
    public function orderConfirmation(array $order, array $items): string
    {
        $header = $this->emailService->getEmailHeader();
        $footer = $this->emailService->getEmailFooter();
        
        $itemsHtml = $this->generateItemsTable($items);
        $totalsHtml = $this->generateTotalsSection($order);
        $shippingHtml = $this->generateShippingSection($order);
        
        $orderNumber = htmlspecialchars($order['order_number']);
        $customerName = htmlspecialchars($order['first_name'] . ' ' . $order['last_name']);
        $orderDate = date('F j, Y', strtotime($order['created_at']));
        $paymentMethod = $this->formatPaymentMethod($order['payment_method']);
        
        $content = <<<HTML
            <h2 style="color: #8B4513; margin-top: 0;">Thank You for Your Order!</h2>
            
            <p>Dear {$customerName},</p>
            
            <p>Thank you for shopping with Ceylon Cinnamon! We're excited to confirm that we've received your order and it's being processed.</p>
            
            <div style="background-color: #f9f9f9; padding: 15px; border-radius: 4px; margin: 20px 0;">
                <p style="margin: 0;"><strong>Order Number:</strong> {$orderNumber}</p>
                <p style="margin: 5px 0 0 0;"><strong>Order Date:</strong> {$orderDate}</p>
                <p style="margin: 5px 0 0 0;"><strong>Payment Method:</strong> {$paymentMethod}</p>
            </div>
            
            <h3 style="color: #8B4513;">Order Details</h3>
            {$itemsHtml}
            {$totalsHtml}
            
            <h3 style="color: #8B4513;">Shipping Information</h3>
            {$shippingHtml}
            
            <p style="margin-top: 30px;">You can track your order status at any time by visiting our website and entering your order number.</p>
            
            <p style="text-align: center;">
                <a href="{$this->getTrackOrderUrl($orderNumber)}" class="btn">Track Your Order</a>
            </p>
            
            <p>If you have any questions about your order, please don't hesitate to contact us.</p>
            
            <p>Best regards,<br>The Ceylon Cinnamon Team</p>
HTML;

        return $header . $content . $footer;
    }

    /**
     * Generate order status update email HTML
     * Requirement 12.2: Order status update email
     * 
     * @param array $order Order data
     * @param string $newStatus New order status
     * @param string|null $message Optional custom message
     * @return string HTML email content
     */
    public function orderStatusUpdate(array $order, string $newStatus, ?string $message = null): string
    {
        $header = $this->emailService->getEmailHeader();
        $footer = $this->emailService->getEmailFooter();
        
        $orderNumber = htmlspecialchars($order['order_number']);
        $customerName = htmlspecialchars($order['first_name'] . ' ' . $order['last_name']);
        $statusBadge = $this->getStatusBadge($newStatus);
        $statusMessage = $this->getStatusMessage($newStatus);
        
        $customMessage = $message ? "<p>" . htmlspecialchars($message) . "</p>" : "";
        
        $content = <<<HTML
            <h2 style="color: #8B4513; margin-top: 0;">Order Status Update</h2>
            
            <p>Dear {$customerName},</p>
            
            <p>We wanted to let you know that your order status has been updated.</p>
            
            <div style="background-color: #f9f9f9; padding: 20px; border-radius: 4px; margin: 20px 0; text-align: center;">
                <p style="margin: 0 0 10px 0;"><strong>Order Number:</strong> {$orderNumber}</p>
                <p style="margin: 0;"><strong>New Status:</strong> {$statusBadge}</p>
            </div>
            
            <p>{$statusMessage}</p>
            
            {$customMessage}
            
            <p style="text-align: center; margin-top: 30px;">
                <a href="{$this->getTrackOrderUrl($orderNumber)}" class="btn">View Order Details</a>
            </p>
            
            <p>If you have any questions, please contact our customer support team.</p>
            
            <p>Best regards,<br>The Ceylon Cinnamon Team</p>
HTML;

        return $header . $content . $footer;
    }

    /**
     * Generate shipping notification email HTML
     * Requirement 12.3: Shipping notification with tracking information
     * 
     * @param array $order Order data
     * @param string|null $trackingNumber Shipping tracking number
     * @param string|null $carrier Shipping carrier name
     * @param string|null $trackingUrl Tracking URL
     * @return string HTML email content
     */
    public function shippingNotification(array $order, ?string $trackingNumber = null, ?string $carrier = null, ?string $trackingUrl = null): string
    {
        $header = $this->emailService->getEmailHeader();
        $footer = $this->emailService->getEmailFooter();
        
        $orderNumber = htmlspecialchars($order['order_number']);
        $customerName = htmlspecialchars($order['first_name'] . ' ' . $order['last_name']);
        $shippingAddress = nl2br(htmlspecialchars($order['shipping_address']));
        
        $trackingHtml = '';
        if ($trackingNumber) {
            $carrierName = $carrier ? htmlspecialchars($carrier) : 'Shipping Carrier';
            $trackingHtml = <<<HTML
            <div style="background-color: #d4edda; padding: 20px; border-radius: 4px; margin: 20px 0;">
                <h4 style="margin: 0 0 10px 0; color: #155724;">Tracking Information</h4>
                <p style="margin: 0;"><strong>Carrier:</strong> {$carrierName}</p>
                <p style="margin: 5px 0 0 0;"><strong>Tracking Number:</strong> {$trackingNumber}</p>
HTML;
            if ($trackingUrl) {
                $trackingHtml .= <<<HTML
                <p style="margin: 15px 0 0 0;">
                    <a href="{$trackingUrl}" style="color: #155724; font-weight: bold;">Track Your Package →</a>
                </p>
HTML;
            }
            $trackingHtml .= "</div>";
        }
        
        $content = <<<HTML
            <h2 style="color: #8B4513; margin-top: 0;">Your Order Has Been Shipped! 📦</h2>
            
            <p>Dear {$customerName},</p>
            
            <p>Great news! Your order <strong>{$orderNumber}</strong> has been shipped and is on its way to you.</p>
            
            {$trackingHtml}
            
            <h3 style="color: #8B4513;">Shipping Address</h3>
            <div style="background-color: #f9f9f9; padding: 15px; border-radius: 4px;">
                <p style="margin: 0;">{$shippingAddress}</p>
            </div>
            
            <p style="margin-top: 20px;">Please allow 3-7 business days for delivery, depending on your location.</p>
            
            <p style="text-align: center; margin-top: 30px;">
                <a href="{$this->getTrackOrderUrl($orderNumber)}" class="btn">View Order Details</a>
            </p>
            
            <p>Thank you for choosing Ceylon Cinnamon!</p>
            
            <p>Best regards,<br>The Ceylon Cinnamon Team</p>
HTML;

        return $header . $content . $footer;
    }

    /**
     * Generate delivery confirmation email HTML
     * 
     * @param array $order Order data
     * @return string HTML email content
     */
    public function deliveryConfirmation(array $order): string
    {
        $header = $this->emailService->getEmailHeader();
        $footer = $this->emailService->getEmailFooter();
        
        $orderNumber = htmlspecialchars($order['order_number']);
        $customerName = htmlspecialchars($order['first_name'] . ' ' . $order['last_name']);
        
        $content = <<<HTML
            <h2 style="color: #8B4513; margin-top: 0;">Your Order Has Been Delivered! ✓</h2>
            
            <p>Dear {$customerName},</p>
            
            <p>We're happy to confirm that your order <strong>{$orderNumber}</strong> has been delivered.</p>
            
            <div style="background-color: #d4edda; padding: 20px; border-radius: 4px; margin: 20px 0; text-align: center;">
                <p style="margin: 0; font-size: 18px; color: #155724;">
                    <strong>Order Delivered Successfully</strong>
                </p>
            </div>
            
            <p>We hope you enjoy your Ceylon cinnamon products! If you have a moment, we'd love to hear your feedback.</p>
            
            <p>If you have any questions or concerns about your order, please don't hesitate to contact us.</p>
            
            <p>Thank you for shopping with Ceylon Cinnamon!</p>
            
            <p>Best regards,<br>The Ceylon Cinnamon Team</p>
HTML;

        return $header . $content . $footer;
    }

    /**
     * Generate order cancellation email HTML
     * 
     * @param array $order Order data
     * @param string|null $reason Cancellation reason
     * @return string HTML email content
     */
    public function orderCancellation(array $order, ?string $reason = null): string
    {
        $header = $this->emailService->getEmailHeader();
        $footer = $this->emailService->getEmailFooter();
        
        $orderNumber = htmlspecialchars($order['order_number']);
        $customerName = htmlspecialchars($order['first_name'] . ' ' . $order['last_name']);
        
        $reasonHtml = $reason 
            ? "<p><strong>Reason:</strong> " . htmlspecialchars($reason) . "</p>" 
            : "";
        
        $content = <<<HTML
            <h2 style="color: #8B4513; margin-top: 0;">Order Cancellation Confirmation</h2>
            
            <p>Dear {$customerName},</p>
            
            <p>This email confirms that your order <strong>{$orderNumber}</strong> has been cancelled.</p>
            
            <div style="background-color: #f8d7da; padding: 20px; border-radius: 4px; margin: 20px 0;">
                <p style="margin: 0; color: #721c24;"><strong>Order Status:</strong> Cancelled</p>
                {$reasonHtml}
            </div>
            
            <p>If you paid for this order, a refund will be processed within 5-7 business days.</p>
            
            <p>If you have any questions about this cancellation, please contact our customer support team.</p>
            
            <p>We hope to serve you again in the future!</p>
            
            <p>Best regards,<br>The Ceylon Cinnamon Team</p>
HTML;

        return $header . $content . $footer;
    }

    /**
     * Generate items table HTML
     */
    private function generateItemsTable(array $items): string
    {
        $rows = '';
        foreach ($items as $item) {
            $name = htmlspecialchars($item['product_name']);
            $sku = htmlspecialchars($item['product_sku']);
            $qty = (int) $item['quantity'];
            $price = number_format((float) $item['price'], 2);
            $total = number_format((float) $item['total'], 2);
            
            $rows .= <<<HTML
                <tr>
                    <td>{$name}<br><small style="color: #666;">SKU: {$sku}</small></td>
                    <td class="text-right">{$qty}</td>
                    <td class="text-right">\${$price}</td>
                    <td class="text-right">\${$total}</td>
                </tr>
HTML;
        }
        
        return <<<HTML
            <table class="order-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th class="text-right">Qty</th>
                        <th class="text-right">Price</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    {$rows}
                </tbody>
            </table>
HTML;
    }

    /**
     * Generate totals section HTML
     */
    private function generateTotalsSection(array $order): string
    {
        $subtotal = number_format((float) $order['subtotal'], 2);
        $shipping = number_format((float) $order['shipping_cost'], 2);
        $tax = number_format((float) $order['tax_amount'], 2);
        $total = number_format((float) $order['total_amount'], 2);
        
        $shippingRow = (float) $order['shipping_cost'] > 0 
            ? "<tr><td class=\"text-right\">Shipping:</td><td class=\"text-right\">\${$shipping}</td></tr>" 
            : "";
        
        $taxRow = (float) $order['tax_amount'] > 0 
            ? "<tr><td class=\"text-right\">Tax:</td><td class=\"text-right\">\${$tax}</td></tr>" 
            : "";
        
        return <<<HTML
            <table style="width: 100%; margin-top: 10px;">
                <tr>
                    <td class="text-right">Subtotal:</td>
                    <td class="text-right" style="width: 100px;">\${$subtotal}</td>
                </tr>
                {$shippingRow}
                {$taxRow}
                <tr class="total-row">
                    <td class="text-right" style="padding-top: 10px; border-top: 2px solid #8B4513;"><strong>Total:</strong></td>
                    <td class="text-right" style="padding-top: 10px; border-top: 2px solid #8B4513;"><strong>\${$total}</strong></td>
                </tr>
            </table>
HTML;
    }

    /**
     * Generate shipping section HTML
     */
    private function generateShippingSection(array $order): string
    {
        $name = htmlspecialchars($order['first_name'] . ' ' . $order['last_name']);
        $address = nl2br(htmlspecialchars($order['shipping_address']));
        $email = htmlspecialchars($order['email']);
        $phone = !empty($order['phone']) ? htmlspecialchars($order['phone']) : 'Not provided';
        
        return <<<HTML
            <div style="background-color: #f9f9f9; padding: 15px; border-radius: 4px;">
                <p style="margin: 0;"><strong>{$name}</strong></p>
                <p style="margin: 5px 0;">{$address}</p>
                <p style="margin: 5px 0;">Email: {$email}</p>
                <p style="margin: 5px 0 0 0;">Phone: {$phone}</p>
            </div>
HTML;
    }

    /**
     * Get status badge HTML
     */
    private function getStatusBadge(string $status): string
    {
        $statusClass = 'status-' . strtolower($status);
        $statusText = ucfirst($status);
        return "<span class=\"status-badge {$statusClass}\">{$statusText}</span>";
    }

    /**
     * Get status message based on order status
     */
    private function getStatusMessage(string $status): string
    {
        return match (strtolower($status)) {
            'pending' => 'Your order is pending and will be processed soon.',
            'processing' => 'Your order is being processed and prepared for shipping.',
            'shipped' => 'Your order has been shipped and is on its way to you!',
            'delivered' => 'Your order has been delivered. We hope you enjoy your products!',
            'cancelled' => 'Your order has been cancelled. If you have any questions, please contact us.',
            'returned' => 'Your return has been processed. A refund will be issued shortly.',
            default => 'Your order status has been updated.'
        };
    }

    /**
     * Format payment method for display
     */
    private function formatPaymentMethod(string $method): string
    {
        return match ($method) {
            'stripe' => 'Credit/Debit Card',
            'paypal' => 'PayPal',
            'bank_transfer' => 'Bank Transfer',
            default => ucfirst($method)
        };
    }

    /**
     * Get track order URL
     */
    private function getTrackOrderUrl(string $orderNumber): string
    {
        return APP_URL . '/order/track?order=' . urlencode($orderNumber);
    }
}
