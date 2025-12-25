<?php
/**
 * Dashboard Controller
 * Handles customer dashboard functionality
 * 
 * Requirements:
 * - 5.5: Display order history with details
 * - 5.6: Show detailed order information and invoice
 */

declare(strict_types=1);

class DashboardController extends Controller
{
    private $orderModel;
    private $userModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->orderModel = new Order();
        $this->userModel = new User();
    }
    
    /**
     * Display customer dashboard
     * Requirement 5.5
     */
    public function index(): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            redirect('/login');
            return;
        }
        
        $user = $this->userModel->find($userId);
        $recentOrders = $this->orderModel->getByUserId($userId, 5);
        $orderStats = $this->orderModel->getUserStats($userId);
        
        $this->view('pages/dashboard/index', [
            'title' => 'My Dashboard - Ceylon Cinnamon',
            'user' => $user,
            'recentOrders' => $recentOrders,
            'orderStats' => $orderStats
        ]);
    }
    
    /**
     * Display order history
     * Requirement 5.5
     */
    public function orders(): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            redirect('/login');
            return;
        }
        
        $page = (int) ($_GET['page'] ?? 1);
        $limit = 10;
        $offset = ($page - 1) * $limit;
        
        $orders = $this->orderModel->getByUserId($userId, $limit, $offset);
        $totalOrders = $this->orderModel->countByUserId($userId);
        $totalPages = ceil($totalOrders / $limit);
        
        $this->view('pages/dashboard/orders', [
            'title' => 'My Orders - Ceylon Cinnamon',
            'orders' => $orders,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalOrders' => $totalOrders
        ]);
    }
    
    /**
     * Display order detail
     * Requirement 5.6
     */
    public function orderDetail(int $id): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            redirect('/login');
            return;
        }
        
        $order = $this->orderModel->find($id);
        
        // Verify order belongs to user
        if (!$order || $order['user_id'] != $userId) {
            $_SESSION['flash']['error'] = 'Order not found.';
            redirect('/dashboard/orders');
            return;
        }
        
        $orderItems = $this->orderModel->getOrderItems($id);
        
        $this->view('pages/dashboard/order_detail', [
            'title' => 'Order #' . $order['order_number'] . ' - Ceylon Cinnamon',
            'order' => $order,
            'orderItems' => $orderItems
        ]);
    }
    
    /**
     * Display profile page
     */
    public function profile(): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            redirect('/login');
            return;
        }
        
        $user = $this->userModel->find($userId);
        
        $this->view('pages/dashboard/profile', [
            'title' => 'My Profile - Ceylon Cinnamon',
            'user' => $user,
            'csrf_token' => $_SESSION['csrf_token'] ?? ''
        ]);
    }
    
    /**
     * Update profile
     */
    public function updateProfile(): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            redirect('/login');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/dashboard/profile');
            return;
        }
        
        // Validate CSRF
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
            $_SESSION['flash']['error'] = 'Invalid form submission.';
            redirect('/dashboard/profile');
            return;
        }
        
        $data = [
            'first_name' => trim($_POST['first_name'] ?? ''),
            'last_name' => trim($_POST['last_name'] ?? ''),
            'phone' => trim($_POST['phone'] ?? '')
        ];
        
        // Validate
        if (empty($data['first_name']) || empty($data['last_name'])) {
            $_SESSION['flash']['error'] = 'First name and last name are required.';
            redirect('/dashboard/profile');
            return;
        }
        
        $this->userModel->update($userId, $data);
        $_SESSION['user_name'] = $data['first_name'];
        $_SESSION['flash']['success'] = 'Profile updated successfully.';
        redirect('/dashboard/profile');
    }
    
    /**
     * Change password
     */
    public function changePassword(): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            redirect('/login');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/dashboard/profile');
            return;
        }
        
        // Validate CSRF
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
            $_SESSION['flash']['error'] = 'Invalid form submission.';
            redirect('/dashboard/profile');
            return;
        }
        
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validate
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $_SESSION['flash']['error'] = 'All password fields are required.';
            redirect('/dashboard/profile');
            return;
        }
        
        if ($newPassword !== $confirmPassword) {
            $_SESSION['flash']['error'] = 'New passwords do not match.';
            redirect('/dashboard/profile');
            return;
        }
        
        if (strlen($newPassword) < 8) {
            $_SESSION['flash']['error'] = 'Password must be at least 8 characters.';
            redirect('/dashboard/profile');
            return;
        }
        
        $user = $this->userModel->find($userId);
        
        if (!password_verify($currentPassword, $user['password_hash'])) {
            $_SESSION['flash']['error'] = 'Current password is incorrect.';
            redirect('/dashboard/profile');
            return;
        }
        
        $this->userModel->update($userId, [
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT)
        ]);
        
        $_SESSION['flash']['success'] = 'Password changed successfully.';
        redirect('/dashboard/profile');
    }
    
    /**
     * Display addresses
     */
    public function addresses(): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            redirect('/login');
            return;
        }
        
        $user = $this->userModel->find($userId);
        $addresses = $this->userModel->getAddresses($userId);
        
        $this->view('pages/dashboard/addresses', [
            'title' => 'My Addresses - Ceylon Cinnamon',
            'user' => $user,
            'addresses' => $addresses,
            'csrf_token' => $_SESSION['csrf_token'] ?? ''
        ]);
    }
}
