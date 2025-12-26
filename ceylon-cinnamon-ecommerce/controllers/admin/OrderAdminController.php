<?php
/**
 * Order Admin Controller
 * Handles order management for admin
 * 
 * Requirements:
 * - 7.1: Order listing with filtering and sorting
 * - 7.2: Order status updates with notifications
 * - 7.3: Invoice generation
 * - 7.4: Order detail view
 * - 7.5: Order notes functionality
 */

declare(strict_types=1);

class OrderAdminController extends Controller
{
    private SessionManager $sessionManager;
    private Order $orderModel;
    private OrderNotificationService $notificationService;

    public function __construct()
    {
        parent::__construct();
        $this->sessionManager = new SessionManager();
        $this->orderModel = new Order();
        $this->notificationService = new OrderNotificationService();
    }

    /**
     * Display order list with filtering and sorting
     * Requirements: 7.1 - Order listing with filtering and sorting options
     */
    public function index(): void
    {
        $this->sessionManager->start();
        
        // Get filter parameters
        $filters = [
            'search' => $this->input('search', ''),
            'order_status' => $this->input('status', ''),
            'payment_status' => $this->input('payment', ''),
            'date_from' => $this->input('date_from', ''),
            'date_to' => $this->input('date_to', '')
        ];
        
        $page = max(1, (int) $this->input('page', 1));
        $limit = ITEMS_PER_PAGE;
        $offset = ($page - 1) * $limit;

        // Get filtered orders
        $result = $this->orderModel->getFiltered($filters, $limit, $offset);

        $this->adminView('orders/index', [
            'title' => 'Orders - Admin',
            'orders' => $result['orders'],
            'total' => $result['total'],
            'pages' => $result['pages'],
            'currentPage' => $page,
            'filters' => $filters,
            'orderStatuses' => Order::STATUSES,
            'paymentStatuses' => Order::PAYMENT_STATUSES,
            'csrf_token' => $this->sessionManager->getCsrfToken()
        ]);
    }

    /**
     * Display order detail view
     * Requirements: 7.4 - Order detail view showing customer info, products, payment status
     */
    public function show($id): void
    {
        $id = (int) $id;
        $this->sessionManager->start();
        
        $order = $this->orderModel->getFullDetails($id);
        
        if (!$order) {
            $this->sessionManager->setFlash('error', 'Order not found.');
            $this->redirect('/admin/orders');
        }

        $this->adminView('orders/show', [
            'title' => 'Order #' . $order['order_number'] . ' - Admin',
            'order' => $order,
            'orderStatuses' => Order::STATUSES,
            'paymentStatuses' => Order::PAYMENT_STATUSES,
            'csrf_token' => $this->sessionManager->getCsrfToken()
        ]);
    }

    /**
     * Update order status
     * Requirements: 7.2 - Order status updates with customer notification
     */
    public function updateStatus($id): void
    {
        $id = (int) $id;
        if (!$this->isPost()) {
            $this->redirect('/admin/orders/' . $id);
        }

        $this->sessionManager->start();

        // Validate CSRF token
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $this->input('csrf_token', '');
        if (!$this->sessionManager->validateCsrfToken($token)) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Invalid security token'], 403);
            }
            $this->sessionManager->setFlash('error', 'Invalid security token.');
            $this->redirect('/admin/orders/' . $id);
        }

        $order = $this->orderModel->find($id);
        if (!$order) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Order not found'], 404);
            }
            $this->redirect('/admin/orders');
        }

        $newStatus = $this->sanitize($this->input('status', ''));
        $sendNotification = (bool) $this->input('send_notification', true);
        $trackingNumber = $this->sanitize($this->input('tracking_number', ''));
        $carrier = $this->sanitize($this->input('carrier', ''));

        // Validate status
        if (!in_array($newStatus, Order::STATUSES)) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Invalid order status'], 400);
            }
            $this->sessionManager->setFlash('error', 'Invalid order status.');
            $this->redirect('/admin/orders/' . $id);
        }

        try {
            $oldStatus = $order['order_status'];

            // Handle special cases
            if ($newStatus === 'cancelled') {
                $this->orderModel->cancelOrder($id, null, $sendNotification);
            } elseif ($newStatus === 'shipped' && !empty($trackingNumber)) {
                $this->orderModel->markAsShipped($id, $trackingNumber, $carrier);
            } else {
                $this->orderModel->updateStatus($id, $newStatus, $sendNotification);
            }

            if ($this->isAjax()) {
                $this->json([
                    'success' => true,
                    'message' => 'Order status updated successfully',
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus
                ]);
            }

            $this->sessionManager->setFlash('success', 'Order status updated successfully.');
            $this->redirect('/admin/orders/' . $id);

        } catch (Exception $e) {
            if ($this->isAjax()) {
                $this->json(['error' => $e->getMessage()], 500);
            }
            $this->sessionManager->setFlash('error', $e->getMessage());
            $this->redirect('/admin/orders/' . $id);
        }
    }

    /**
     * Add note to order
     * Requirements: 7.5 - Order notes functionality
     */
    public function addNote($id): void
    {
        $id = (int) $id;
        if (!$this->isPost()) {
            $this->redirect('/admin/orders/' . $id);
        }

        $this->sessionManager->start();

        // Validate CSRF token
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $this->input('csrf_token', '');
        if (!$this->sessionManager->validateCsrfToken($token)) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Invalid security token'], 403);
            }
            $this->sessionManager->setFlash('error', 'Invalid security token.');
            $this->redirect('/admin/orders/' . $id);
        }

        $order = $this->orderModel->find($id);
        if (!$order) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Order not found'], 404);
            }
            $this->redirect('/admin/orders');
        }

        $note = $this->sanitize($this->input('note', ''));
        
        if (empty($note)) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Note cannot be empty'], 400);
            }
            $this->sessionManager->setFlash('error', 'Note cannot be empty.');
            $this->redirect('/admin/orders/' . $id);
        }

        try {
            $user = $this->sessionManager->getUser();
            $noteWithUser = "[{$user['first_name']} {$user['last_name']}] {$note}";
            
            $this->orderModel->addNote($id, $noteWithUser);

            if ($this->isAjax()) {
                $this->json([
                    'success' => true,
                    'message' => 'Note added successfully'
                ]);
            }

            $this->sessionManager->setFlash('success', 'Note added successfully.');
            $this->redirect('/admin/orders/' . $id);

        } catch (Exception $e) {
            if ($this->isAjax()) {
                $this->json(['error' => $e->getMessage()], 500);
            }
            $this->sessionManager->setFlash('error', $e->getMessage());
            $this->redirect('/admin/orders/' . $id);
        }
    }

    /**
     * Generate and download invoice PDF
     * Requirements: 7.3 - Invoice generation with order details and company branding
     */
    public function invoice($id): void
    {
        $id = (int) $id;
        $this->sessionManager->start();

        $order = $this->orderModel->getFullDetails($id);
        
        if (!$order) {
            $this->sessionManager->setFlash('error', 'Order not found.');
            $this->redirect('/admin/orders');
        }

        // Generate invoice HTML
        $invoiceHtml = $this->generateInvoiceHtml($order);

        // For now, output as HTML (PDF generation would require a library like TCPDF or Dompdf)
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: inline; filename="invoice_' . $order['order_number'] . '.html"');
        
        echo $invoiceHtml;
        exit;
    }

    /**
     * Generate invoice HTML
     * Requirements: 7.3 - Invoice with order details and company branding
     * Requirements: 6.6 - Display both original and sale prices
     * 
     * @param array $order Order with items
     * @return string Invoice HTML
     */
    private function generateInvoiceHtml(array $order): string
    {
        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice - ' . htmlspecialchars($order['order_number']) . '</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; line-height: 1.6; color: #333; margin: 0; padding: 20px; }
        .invoice-container { max-width: 800px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 40px; border-bottom: 2px solid #8B4513; padding-bottom: 20px; }
        .company-info { }
        .company-name { font-size: 24px; font-weight: bold; color: #8B4513; margin-bottom: 5px; }
        .company-details { font-size: 12px; color: #666; }
        .invoice-title { text-align: right; }
        .invoice-title h1 { font-size: 28px; color: #8B4513; margin: 0; }
        .invoice-number { font-size: 14px; color: #666; }
        .addresses { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .address-block { width: 45%; }
        .address-block h3 { font-size: 14px; color: #8B4513; margin-bottom: 10px; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
        .order-info { background: #f9f9f9; padding: 15px; margin-bottom: 30px; border-radius: 5px; }
        .order-info-row { display: flex; justify-content: space-between; margin-bottom: 5px; }
        .order-info-label { font-weight: bold; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th { background: #8B4513; color: white; padding: 12px; text-align: left; }
        td { padding: 12px; border-bottom: 1px solid #ddd; }
        .text-right { text-align: right; }
        .original-price { text-decoration: line-through; color: #999; font-size: 12px; }
        .sale-price { color: #c00; font-weight: bold; }
        .totals { width: 300px; margin-left: auto; }
        .totals-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; }
        .totals-row.total { font-size: 18px; font-weight: bold; color: #8B4513; border-top: 2px solid #8B4513; border-bottom: none; padding-top: 15px; }
        .footer { margin-top: 50px; text-align: center; font-size: 12px; color: #666; border-top: 1px solid #ddd; padding-top: 20px; }
        @media print { body { padding: 0; } .invoice-container { max-width: 100%; } }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="header">
            <div class="company-info">
                <div class="company-name">' . htmlspecialchars(APP_NAME) . '</div>
                <div class="company-details">
                    Premium Ceylon Cinnamon Products<br>
                    Sri Lanka<br>
                    Email: info@ceyloncinnamon.com
                </div>
            </div>
            <div class="invoice-title">
                <h1>INVOICE</h1>
                <div class="invoice-number">#' . htmlspecialchars($order['order_number']) . '</div>
            </div>
        </div>

        <div class="addresses">
            <div class="address-block">
                <h3>Bill To</h3>
                <strong>' . htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) . '</strong><br>
                ' . nl2br(htmlspecialchars($order['billing_address'] ?? $order['shipping_address'])) . '<br>
                Email: ' . htmlspecialchars($order['email']) . '
                ' . ($order['phone'] ? '<br>Phone: ' . htmlspecialchars($order['phone']) : '') . '
            </div>
            <div class="address-block">
                <h3>Ship To</h3>
                <strong>' . htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) . '</strong><br>
                ' . nl2br(htmlspecialchars($order['shipping_address'])) . '
            </div>
        </div>

        <div class="order-info">
            <div class="order-info-row">
                <span class="order-info-label">Order Date:</span>
                <span>' . date('F j, Y', strtotime($order['created_at'])) . '</span>
            </div>
            <div class="order-info-row">
                <span class="order-info-label">Payment Method:</span>
                <span>' . ucfirst(str_replace('_', ' ', $order['payment_method'])) . '</span>
            </div>
            <div class="order-info-row">
                <span class="order-info-label">Payment Status:</span>
                <span>' . ucfirst($order['payment_status']) . '</span>
            </div>
            <div class="order-info-row">
                <span class="order-info-label">Order Status:</span>
                <span>' . ucfirst($order['order_status']) . '</span>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>SKU</th>
                    <th class="text-right">Price</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($order['items'] as $item) {
            // Check if there's a sale price (Property 23: Sale price display)
            $priceDisplay = '$' . number_format((float)$item['price'], 2);
            
            // Get original product to check for sale price
            $productModel = new Product();
            $product = $productModel->find((int)$item['product_id']);
            
            if ($product && $product['sale_price'] && (float)$product['sale_price'] < (float)$product['price']) {
                // Show both original and sale price
                $priceDisplay = '<span class="original-price">$' . number_format((float)$product['price'], 2) . '</span><br>'
                              . '<span class="sale-price">$' . number_format((float)$item['price'], 2) . '</span>';
            }

            $html .= '
                <tr>
                    <td>' . htmlspecialchars($item['product_name']) . '</td>
                    <td>' . htmlspecialchars($item['product_sku']) . '</td>
                    <td class="text-right">' . $priceDisplay . '</td>
                    <td class="text-right">' . (int)$item['quantity'] . '</td>
                    <td class="text-right">$' . number_format((float)$item['total'], 2) . '</td>
                </tr>';
        }

        $html .= '
            </tbody>
        </table>

        <div class="totals">
            <div class="totals-row">
                <span>Subtotal:</span>
                <span>$' . number_format((float)$order['subtotal'], 2) . '</span>
            </div>
            <div class="totals-row">
                <span>Shipping:</span>
                <span>$' . number_format((float)$order['shipping_cost'], 2) . '</span>
            </div>
            <div class="totals-row">
                <span>Tax:</span>
                <span>$' . number_format((float)$order['tax_amount'], 2) . '</span>
            </div>
            <div class="totals-row total">
                <span>Total:</span>
                <span>$' . number_format((float)$order['total_amount'], 2) . '</span>
            </div>
        </div>

        <div class="footer">
            <p>Thank you for your business!</p>
            <p>' . htmlspecialchars(APP_NAME) . ' - Premium Ceylon Cinnamon Products</p>
        </div>
    </div>
</body>
</html>';

        return $html;
    }

    /**
     * Render admin view with layout
     */
    protected function adminView(string $view, array $data = []): void
    {
        extract($data);
        
        $viewFile = VIEWS_PATH . '/admin/' . $view . '.php';
        
        if (!file_exists($viewFile)) {
            throw new RuntimeException("Admin view '{$view}' not found.");
        }

        include $viewFile;
    }
}
