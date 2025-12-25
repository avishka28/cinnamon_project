<?php
/**
 * Admin Controller
 * Handles admin dashboard and authentication
 * 
 * Requirements:
 * - 2.6: Admin access to all administrative functions
 */

declare(strict_types=1);

class AdminController extends Controller
{
    private SessionManager $sessionManager;
    private User $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->sessionManager = new SessionManager();
        $this->userModel = new User();
    }

    /**
     * Display admin dashboard
     * Requirements: 2.6 - Admin access to all administrative functions
     */
    public function index(): void
    {
        $this->sessionManager->start();
        
        // Get dashboard statistics
        $stats = $this->getDashboardStats();
        
        $this->adminView('dashboard', [
            'title' => 'Admin Dashboard - ' . APP_NAME,
            'stats' => $stats,
            'user' => $this->sessionManager->getUser()
        ]);
    }

    /**
     * Display admin login form
     */
    public function showLogin(): void
    {
        $this->sessionManager->start();
        
        // Redirect if already logged in as admin
        if ($this->sessionManager->isLoggedIn()) {
            $role = $this->sessionManager->getUserRole();
            if (in_array($role, ['admin', 'content_manager'])) {
                $this->redirect('/admin');
            }
        }

        $this->adminView('login', [
            'title' => 'Admin Login - ' . APP_NAME,
            'csrf_token' => $this->sessionManager->getCsrfToken()
        ]);
    }

    /**
     * Process admin login
     * Requirements: 2.6 - Admin access verification
     */
    public function login(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/login');
        }

        $this->sessionManager->start();

        // Validate CSRF token
        if (!$this->sessionManager->validateCsrfToken($this->input('csrf_token', ''))) {
            $this->handleLoginError('Invalid security token. Please try again.');
            return;
        }

        $email = $this->sanitize($this->input('email', ''));
        $password = $this->input('password', '');

        // Validate input
        if (empty($email) || empty($password)) {
            $this->handleLoginError('Email and password are required.', $email);
            return;
        }

        // Authenticate user
        $user = $this->userModel->authenticate($email, $password);
        
        if ($user === null) {
            $this->handleLoginError('Invalid email or password.', $email);
            return;
        }

        // Verify admin or content_manager role
        if (!in_array($user['role'], ['admin', 'content_manager'])) {
            $this->handleLoginError('You do not have admin access.', $email);
            return;
        }

        // Create secure session
        $this->sessionManager->login($user);

        $this->redirect('/admin');
    }

    /**
     * Process admin logout
     */
    public function logout(): void
    {
        $this->sessionManager->logout();
        $this->redirect('/admin/login');
    }

    /**
     * Get dashboard statistics using Analytics model
     * Requirements: 15.1, 15.2, 15.3, 15.4, 15.5
     * 
     * @return array Dashboard stats
     */
    private function getDashboardStats(): array
    {
        $analytics = new Analytics();
        
        // Get comprehensive analytics data
        $todayStats = $analytics->getTodayStats();
        $weekStats = $analytics->getWeekStats();
        $monthStats = $analytics->getMonthStats();
        $customerAnalytics = $analytics->getCustomerAnalytics();
        $inventoryStatus = $analytics->getInventoryStatus();
        $topProducts = $analytics->getTopSellingProducts(5);
        $recentOrders = $analytics->getRecentOrders(5);
        $revenueComparison = $analytics->getRevenueComparison('month');
        $chartData = $analytics->getRevenueChartData(30);
        $orderStatusDist = $analytics->getOrderStatusDistribution();
        $paymentMethodDist = $analytics->getPaymentMethodDistribution();

        return [
            // Basic stats
            'total_orders' => $this->getTotalOrders(),
            'pending_orders' => $orderStatusDist['pending'] ?? 0,
            'total_products' => $inventoryStatus['total_products'],
            'low_stock' => $inventoryStatus['low_stock_count'],
            'total_customers' => $customerAnalytics['total_customers'],
            'today_revenue' => $todayStats['paid_revenue'],
            'recent_orders' => $recentOrders,
            
            // Extended analytics (Requirement 15.1)
            'today_stats' => $todayStats,
            'week_stats' => $weekStats,
            'month_stats' => $monthStats,
            
            // Revenue comparison
            'revenue_comparison' => $revenueComparison,
            
            // Top products (Requirement 15.2)
            'top_products' => $topProducts,
            
            // Customer analytics (Requirement 15.3)
            'customer_analytics' => $customerAnalytics,
            
            // Inventory status (Requirement 15.5)
            'inventory_status' => $inventoryStatus,
            'low_stock_products' => $inventoryStatus['low_stock_products'],
            'out_of_stock_products' => $inventoryStatus['out_of_stock_products'],
            
            // Chart data
            'chart_data' => $chartData,
            
            // Order status distribution
            'order_status_distribution' => $orderStatusDist,
            
            // Payment method distribution
            'payment_method_distribution' => $paymentMethodDist
        ];
    }

    /**
     * Get total orders count
     * 
     * @return int Total orders
     */
    private function getTotalOrders(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM orders");
        return (int) $stmt->fetchColumn();
    }

    /**
     * Render admin view with layout
     * 
     * @param string $view View name
     * @param array $data View data
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

    /**
     * Handle login error
     * 
     * @param string $error Error message
     * @param string $email Email to preserve
     */
    private function handleLoginError(string $error, string $email = ''): void
    {
        $this->adminView('login', [
            'title' => 'Admin Login - ' . APP_NAME,
            'error' => $error,
            'email' => $email,
            'csrf_token' => $this->sessionManager->getCsrfToken()
        ]);
    }
}
