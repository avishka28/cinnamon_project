<?php
/**
 * Home Controller
 * Handles the main landing page
 * 
 * Requirements:
 * - 1.1: Display products
 * - 11.1: Include appropriate meta tags and Open Graph data
 * - 11.2: Include JSON-LD structured data
 * - 11.5: Responsive design
 */

declare(strict_types=1);

class HomeController extends Controller
{
    private $productModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->productModel = new Product();
    }
    
    public function index(): void
    {
        // Configure SEO for home page (Requirements 11.1, 11.2)
        $seo = new SeoHelper();
        $seo->configureForHome();
        
        // Get featured products (Requirement 1.1)
        $featuredProducts = $this->productModel->getFeatured(8);

        $this->view('pages/home', [
            'title' => 'Welcome to Ceylon Cinnamon - Premium Sri Lankan Cinnamon',
            'seo' => $seo,
            'featuredProducts' => $featuredProducts
        ]);
    }
    
    public function about(): void
    {
        $seo = new SeoHelper();
        $seo->setTitle('About Us - Ceylon Cinnamon')
            ->setDescription('Learn about our story and commitment to bringing you the finest Ceylon cinnamon from Sri Lanka.')
            ->setCanonicalUrl(url('/about'));
        
        $this->view('pages/about', [
            'title' => 'About Us - Ceylon Cinnamon',
            'seo' => $seo
        ]);
    }
    
    public function contact(): void
    {
        $seo = new SeoHelper();
        $seo->setTitle('Contact Us - Ceylon Cinnamon')
            ->setDescription('Get in touch with us for questions about our Ceylon cinnamon products, wholesale inquiries, or customer support.')
            ->setCanonicalUrl(url('/contact'));
        
        $this->view('pages/contact', [
            'title' => 'Contact Us - Ceylon Cinnamon',
            'seo' => $seo,
            'csrf_token' => $_SESSION['csrf_token'] ?? ''
        ]);
    }
    
    public function contactSubmit(): void
    {
        // Handle contact form submission
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/contact');
            return;
        }
        
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
            $this->view('pages/contact', [
                'title' => 'Contact Us - Ceylon Cinnamon',
                'error' => 'Invalid form submission. Please try again.',
                'csrf_token' => $_SESSION['csrf_token'] ?? ''
            ]);
            return;
        }
        
        // Validate input
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');
        
        if (empty($name) || empty($email) || empty($subject) || empty($message)) {
            $this->view('pages/contact', [
                'title' => 'Contact Us - Ceylon Cinnamon',
                'error' => 'Please fill in all required fields.',
                'csrf_token' => $_SESSION['csrf_token'] ?? ''
            ]);
            return;
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->view('pages/contact', [
                'title' => 'Contact Us - Ceylon Cinnamon',
                'error' => 'Please enter a valid email address.',
                'csrf_token' => $_SESSION['csrf_token'] ?? ''
            ]);
            return;
        }
        
        // In a real application, you would send an email or save to database here
        // For now, we'll just show a success message
        
        $this->view('pages/contact', [
            'title' => 'Contact Us - Ceylon Cinnamon',
            'success' => 'Thank you for your message! We will get back to you within 24-48 hours.',
            'csrf_token' => $_SESSION['csrf_token'] ?? ''
        ]);
    }
    
    public function privacy(): void
    {
        $seo = new SeoHelper();
        $seo->setTitle('Privacy Policy - Ceylon Cinnamon')
            ->setDescription('Read our privacy policy to understand how we collect, use, and protect your personal information.')
            ->setCanonicalUrl(url('/privacy'));
        
        $this->view('pages/privacy', [
            'title' => 'Privacy Policy - Ceylon Cinnamon',
            'seo' => $seo
        ]);
    }
    
    public function terms(): void
    {
        $seo = new SeoHelper();
        $seo->setTitle('Terms of Service - Ceylon Cinnamon')
            ->setDescription('Read our terms of service for information about ordering, shipping, returns, and more.')
            ->setCanonicalUrl(url('/terms'));
        
        $this->view('pages/terms', [
            'title' => 'Terms of Service - Ceylon Cinnamon',
            'seo' => $seo
        ]);
    }
}
