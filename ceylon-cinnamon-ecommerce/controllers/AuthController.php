<?php
/**
 * Authentication Controller
 * Handles user login, registration, and logout functionality
 * 
 * Requirements:
 * - 2.1: Password hashing using password_hash()
 * - 2.2: Secure login with password verification
 * - 10.3: Input sanitization and validation
 */

declare(strict_types=1);

class AuthController extends Controller
{
    private User $userModel;
    private SessionManager $sessionManager;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
        $this->sessionManager = new SessionManager();
    }

    /**
     * Display login form
     */
    public function showLogin(): void
    {
        // Redirect if already logged in
        if ($this->sessionManager->isLoggedIn()) {
            $this->redirect('/dashboard');
        }

        $redirect = $this->sanitize($this->input('redirect', ''));
        $this->view('auth.login', [
            'redirect' => $redirect,
            'csrf_token' => $this->sessionManager->getCsrfToken()
        ]);
    }

    /**
     * Process login request
     * Requirements: 2.2 - Secure login with password verification
     */
    public function login(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/login');
        }

        // Validate CSRF token
        if (!$this->sessionManager->validateCsrfToken($this->input('csrf_token', ''))) {
            $this->handleLoginError('Invalid security token. Please try again.');
            return;
        }

        // Get and sanitize input (Requirements: 10.3)
        $email = $this->sanitize($this->input('email', ''));
        $password = $this->input('password', ''); // Don't sanitize password
        $redirect = $this->sanitize($this->input('redirect', '/dashboard'));

        // Validate input
        $errors = $this->validateLoginInput($email, $password);
        if (!empty($errors)) {
            $this->handleLoginError($errors[0], $email);
            return;
        }

        // Authenticate user (Requirements: 2.2)
        $user = $this->userModel->authenticate($email, $password);
        
        if ($user === null) {
            $this->handleLoginError('Invalid email or password.', $email);
            return;
        }

        // Create secure session
        $this->sessionManager->login($user);

        // Redirect to intended destination
        $this->redirect($redirect);
    }

    /**
     * Display registration form
     */
    public function showRegister(): void
    {
        // Redirect if already logged in
        if ($this->sessionManager->isLoggedIn()) {
            $this->redirect('/dashboard');
        }

        $this->view('auth.register', [
            'csrf_token' => $this->sessionManager->getCsrfToken()
        ]);
    }

    /**
     * Process registration request
     * Requirements: 2.1 - Password hashing, 10.3 - Input validation
     */
    public function register(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/register');
        }

        // Validate CSRF token
        if (!$this->sessionManager->validateCsrfToken($this->input('csrf_token', ''))) {
            $this->handleRegisterError('Invalid security token. Please try again.', []);
            return;
        }

        // Get and sanitize input (Requirements: 10.3)
        $data = [
            'email' => $this->sanitize($this->input('email', '')),
            'password' => $this->input('password', ''), // Don't sanitize password
            'password_confirm' => $this->input('password_confirm', ''),
            'first_name' => $this->sanitize($this->input('first_name', '')),
            'last_name' => $this->sanitize($this->input('last_name', '')),
            'phone' => $this->sanitize($this->input('phone', ''))
        ];

        // Validate input
        $errors = $this->validateRegistrationInput($data);
        if (!empty($errors)) {
            $this->handleRegisterError($errors, $data);
            return;
        }

        // Check if email already exists
        if ($this->userModel->emailExists($data['email'])) {
            $this->handleRegisterError(['Email address is already registered.'], $data);
            return;
        }

        try {
            // Create user (password will be hashed in User model - Requirements: 2.1)
            $userId = $this->userModel->createUser($data);

            // Auto-login after registration
            $user = $this->userModel->find($userId);
            unset($user['password_hash']);
            $this->sessionManager->login($user);

            $this->redirect('/dashboard');
        } catch (InvalidArgumentException $e) {
            $this->handleRegisterError([$e->getMessage()], $data);
        }
    }

    /**
     * Process logout request
     */
    public function logout(): void
    {
        $this->sessionManager->logout();
        $this->redirect('/');
    }

    /**
     * Validate login input
     * Requirements: 10.3 - Input validation
     */
    private function validateLoginInput(string $email, string $password): array
    {
        $errors = [];

        if (empty($email)) {
            $errors[] = 'Email address is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }

        if (empty($password)) {
            $errors[] = 'Password is required.';
        }

        return $errors;
    }

    /**
     * Validate registration input
     * Requirements: 10.3 - Input validation
     */
    private function validateRegistrationInput(array $data): array
    {
        $errors = [];

        // Email validation
        if (empty($data['email'])) {
            $errors[] = 'Email address is required.';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        } elseif (strlen($data['email']) > 255) {
            $errors[] = 'Email address is too long.';
        }

        // Password validation
        if (empty($data['password'])) {
            $errors[] = 'Password is required.';
        } elseif (strlen($data['password']) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        } elseif (strlen($data['password']) > 72) {
            $errors[] = 'Password is too long (maximum 72 characters).';
        }

        // Password confirmation
        if ($data['password'] !== $data['password_confirm']) {
            $errors[] = 'Passwords do not match.';
        }

        // First name validation
        if (empty($data['first_name'])) {
            $errors[] = 'First name is required.';
        } elseif (strlen($data['first_name']) > 100) {
            $errors[] = 'First name is too long.';
        }

        // Last name validation
        if (empty($data['last_name'])) {
            $errors[] = 'Last name is required.';
        } elseif (strlen($data['last_name']) > 100) {
            $errors[] = 'Last name is too long.';
        }

        // Phone validation (optional)
        if (!empty($data['phone']) && strlen($data['phone']) > 20) {
            $errors[] = 'Phone number is too long.';
        }

        return $errors;
    }

    /**
     * Handle login error - show form with error message
     */
    private function handleLoginError(string $error, string $email = ''): void
    {
        $this->view('auth.login', [
            'error' => $error,
            'email' => $email,
            'redirect' => $this->sanitize($this->input('redirect', '')),
            'csrf_token' => $this->sessionManager->getCsrfToken()
        ]);
    }

    /**
     * Handle registration error - show form with error messages
     */
    private function handleRegisterError(array $errors, array $data): void
    {
        // Remove sensitive data
        unset($data['password'], $data['password_confirm']);
        
        $this->view('auth.register', [
            'errors' => $errors,
            'old' => $data,
            'csrf_token' => $this->sessionManager->getCsrfToken()
        ]);
    }
}
