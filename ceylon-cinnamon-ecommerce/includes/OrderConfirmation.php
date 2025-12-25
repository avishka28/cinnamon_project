<?php
/**
 * Order Confirmation Generator
 * Generates order confirmations and invoices
 * 
 * Requirements:
 * - 4.6: Generate order confirmation and invoice on success
 * - 7.3: Create PDF invoice with order details
 */

declare(strict_types=1);

class OrderConfirmation
{
    private Order $orderModel;
    
    public function __construct()
    {
        $this->orderModel = new Order();
    }

    /**
     * Generate order confirmation data
     * Requirement 4.6: Generate order confirmation
     * 
     * @param string $orderNumber Order number
     * @return array|null Confirmation data or null if order not found
     */
    public function generate(string $orderNumber): ?array
    {
        // Get order by order number
        $order = $this->orderModel->findByOrderNumber($orderNumber);
        
        if (!$order) {
            return null;
        }
        
        // Get order items
        $items = $this->orderModel->getOrderItems((int) $order['id']);
        
        return [
            'order' => $order,
            'items' => $items,
            'confirmation_number' => $this->generateConfirmationNumber($orderNumber),
            'generated_at' => date('Y-m-d H:i:s'),
            'company' => $this->getCompanyInfo(),
            'summary' => $this->generateSummary($order, $items)
        ];
    }

    /**
     * Generate confirmation number
     */
    private function generateConfirmationNumber(string $orderNumber): string
    {
        return 'CONF-' . $orderNumber . '-' . strtoupper(substr(md5($orderNumber . date('Y')), 0, 6));
    }

    /**
     * Get company information for invoices
     */
    private function getCompanyInfo(): array
    {
        return [
            'name' => 'Ceylon Cinnamon Exports Ltd',
            'address' => '123 Spice Garden Road',
            'city' => 'Colombo',
            'country' => 'Sri Lanka',
            'postal_code' => '00100',
            'phone' => '+94 11 234 5678',
            'email' => 'orders@ceyloncinnamon.com',
            'website' => 'www.ceyloncinnamon.com',
            'tax_id' => 'LK123456789'
        ];
    }

    /**
     * Generate order summary
     */
    private function generateSummary(array $order, array $items): array
    {
        $itemCount = count($items);
        $totalQuantity = array_sum(array_column($items, 'quantity'));
        
        return [
            'item_count' => $itemCount,
            'total_quantity' => $totalQuantity,
            'subtotal' => (float) $order['subtotal'],
            'shipping' => (float) $order['shipping_cost'],
            'tax' => (float) $order['tax_amount'],
            'total' => (float) $order['total_amount'],
            'payment_method' => $this->formatPaymentMethod($order['payment_method']),
            'payment_status' => ucfirst($order['payment_status']),
            'order_status' => ucfirst($order['order_status'])
        ];
    }

    /**
     * Format payment method for display
     */
    private function formatPaymentMethod(string $method): string
    {
        return match ($method) {
            'stripe' => 'Credit/Debit Card (Stripe)',
            'paypal' => 'PayPal',
            'bank_transfer' => 'Bank Transfer',
            default => ucfirst($method)
        };
    }

    /**
     * Generate HTML invoice
     * Requirement 7.3: Create invoice with order details
     * 
     * @param string $orderNumber Order number
     * @return string|null HTML invoice or null if order not found
     */
    public function generateInvoiceHtml(string $orderNumber): ?string
    {
        $confirmation = $this->generate($orderNumber);
        
        if (!$confirmation) {
            return null;
        }
        
        $order = $confirmation['order'];
        $items = $confirmation['items'];
        $company = $confirmation['company'];
        $summary = $confirmation['summary'];
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Invoice - <?= htmlspecialchars($order['order_number']) ?></title>
            <style>
                body { font-family: Arial, sans-serif; font-size: 14px; line-height: 1.6; color: #333; }
                .invoice { max-width: 800px; margin: 0 auto; padding: 20px; }
                .header { display: flex; justify-content: space-between; margin-bottom: 30px; }
                .company-info { text-align: right; }
                .invoice-title { font-size: 28px; color: #8B4513; margin-bottom: 5px; }
                .invoice-number { color: #666; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
                th { background-color: #f5f5f5; }
                .text-right { text-align: right; }
                .totals { margin-top: 20px; }
                .totals td { border: none; }
                .grand-total { font-size: 18px; font-weight: bold; color: #8B4513; }
                .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center; color: #666; }
                .status-badge { display: inline-block; padding: 4px 12px; border-radius: 4px; font-size: 12px; }
                .status-paid { background-color: #d4edda; color: #155724; }
                .status-pending { background-color: #fff3cd; color: #856404; }
            </style>
        </head>
        <body>
            <div class="invoice">
                <div class="header">
                    <div>
                        <div class="invoice-title">INVOICE</div>
                        <div class="invoice-number">#<?= htmlspecialchars($order['order_number']) ?></div>
                        <div>Date: <?= date('F j, Y', strtotime($order['created_at'])) ?></div>
                    </div>
                    <div class="company-info">
                        <strong><?= htmlspecialchars($company['name']) ?></strong><br>
                        <?= htmlspecialchars($company['address']) ?><br>
                        <?= htmlspecialchars($company['city']) ?>, <?= htmlspecialchars($company['country']) ?><br>
                        <?= htmlspecialchars($company['phone']) ?><br>
                        <?= htmlspecialchars($company['email']) ?>
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; margin-bottom: 30px;">
                    <div>
                        <strong>Bill To:</strong><br>
                        <?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?><br>
                        <?= nl2br(htmlspecialchars($order['billing_address'] ?: $order['shipping_address'])) ?><br>
                        <?= htmlspecialchars($order['email']) ?>
                    </div>
                    <div>
                        <strong>Ship To:</strong><br>
                        <?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?><br>
                        <?= nl2br(htmlspecialchars($order['shipping_address'])) ?>
                    </div>
                </div>

                <div style="margin-bottom: 20px;">
                    <strong>Payment Method:</strong> <?= htmlspecialchars($summary['payment_method']) ?>
                    <span class="status-badge status-<?= $order['payment_status'] ?>">
                        <?= htmlspecialchars($summary['payment_status']) ?>
                    </span>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>SKU</th>
                            <th class="text-right">Qty</th>
                            <th class="text-right">Price</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['product_name']) ?></td>
                            <td><?= htmlspecialchars($item['product_sku']) ?></td>
                            <td class="text-right"><?= $item['quantity'] ?></td>
                            <td class="text-right">$<?= number_format($item['price'], 2) ?></td>
                            <td class="text-right">$<?= number_format($item['total'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <table class="totals">
                    <tr>
                        <td></td>
                        <td class="text-right" style="width: 150px;">Subtotal:</td>
                        <td class="text-right" style="width: 100px;">$<?= number_format($summary['subtotal'], 2) ?></td>
                    </tr>
                    <?php if ($summary['shipping'] > 0): ?>
                    <tr>
                        <td></td>
                        <td class="text-right">Shipping:</td>
                        <td class="text-right">$<?= number_format($summary['shipping'], 2) ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($summary['tax'] > 0): ?>
                    <tr>
                        <td></td>
                        <td class="text-right">Tax:</td>
                        <td class="text-right">$<?= number_format($summary['tax'], 2) ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr class="grand-total">
                        <td></td>
                        <td class="text-right">Total:</td>
                        <td class="text-right">$<?= number_format($summary['total'], 2) ?></td>
                    </tr>
                </table>

                <div class="footer">
                    <p>Thank you for your order!</p>
                    <p><?= htmlspecialchars($company['website']) ?></p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Get invoice data for PDF generation
     * 
     * @param string $orderNumber Order number
     * @return array|null Invoice data or null if order not found
     */
    public function getInvoiceData(string $orderNumber): ?array
    {
        $confirmation = $this->generate($orderNumber);
        
        if (!$confirmation) {
            return null;
        }
        
        return [
            'order' => $confirmation['order'],
            'items' => $confirmation['items'],
            'company' => $confirmation['company'],
            'summary' => $confirmation['summary'],
            'invoice_number' => 'INV-' . $confirmation['order']['order_number'],
            'invoice_date' => date('Y-m-d'),
            'due_date' => date('Y-m-d', strtotime('+30 days'))
        ];
    }
}
