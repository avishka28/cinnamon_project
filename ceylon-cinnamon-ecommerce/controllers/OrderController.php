<?php
/**
 * Order Controller
 * Handles order tracking functionality
 * 
 * Requirements:
 * - 5.6: Show detailed order information
 */

declare(strict_types=1);

class OrderController extends Controller
{
    private Order $orderModel;

    public function __construct()
    {
        parent::__construct();
        $this->orderModel = new Order();
    }

    /**
     * Show order tracking form
     */
    public function showTrack(): void
    {
        $orderNumber = $_GET['order'] ?? '';
        $email = $_GET['email'] ?? '';
        
        $this->view('pages/order_track', [
            'title' => 'Track Your Order - Ceylon Cinnamon',
            'orderNumber' => $orderNumber,
            'email' => $email,
            'csrf_token' => $_SESSION['csrf_token'] ?? ''
        ]);
    }

    /**
     * Track order by order number and email
     */
    public function track(): void
    {
        $orderNumber = trim($_POST['order_number'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        // Validate CSRF
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
            $_SESSION['flash']['error'] = 'Invalid form submission. Please try again.';
            $this->redirect('/order/track');
            return;
        }

        if (empty($orderNumber) || empty($email)) {
            $_SESSION['flash']['error'] = 'Please enter both order number and email address.';
            $this->redirect('/order/track');
            return;
        }

        // Find order by order number and email
        $order = $this->orderModel->findByOrderNumberAndEmail($orderNumber, $email);

        if (!$order) {
            $_SESSION['flash']['error'] = 'Order not found. Please check your order number and email address.';
            $this->redirect('/order/track?order=' . urlencode($orderNumber) . '&email=' . urlencode($email));
            return;
        }

        // Get order items
        $orderItems = $this->orderModel->getOrderItems($order['id']);

        $this->view('pages/order_track_result', [
            'title' => 'Order #' . $order['order_number'] . ' - Ceylon Cinnamon',
            'order' => $order,
            'orderItems' => $orderItems,
            'csrf_token' => $_SESSION['csrf_token'] ?? ''
        ]);
    }
}
