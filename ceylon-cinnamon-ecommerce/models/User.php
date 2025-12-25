<?php
/**
 * User Model
 * Handles user authentication, registration, and profile management
 * 
 * Requirements:
 * - 2.1: Password hashing using password_hash()
 * - 2.2: Secure login with password verification
 * - 2.5: Support three user roles (customer, admin, content_manager)
 * - 10.7: Strong password hashing algorithms
 * - 13.4: Wholesale customer identification
 */

declare(strict_types=1);

class User extends Model
{
    protected string $table = 'users';

    /**
     * Valid user roles
     */
    public const ROLE_CUSTOMER = 'customer';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_CONTENT_MANAGER = 'content_manager';

    /**
     * Password hashing algorithm options
     * Using bcrypt with cost factor of 12 for security
     */
    private const PASSWORD_OPTIONS = [
        'cost' => 12
    ];

    /**
     * Find a user by email address
     * 
     * @param string $email The email to search for
     * @return array|null User data or null if not found
     */
    public function findByEmail(string $email): ?array
    {
        return $this->findBy('email', $email);
    }

    /**
     * Create a new user with hashed password
     * Requirements: 2.1, 10.7 - Password hashing using password_hash()
     * 
     * @param array $data User data including plain text password
     * @return int The new user's ID
     * @throws InvalidArgumentException If required fields are missing
     */
    public function createUser(array $data): int
    {
        $this->validateUserData($data);

        // Hash the password using bcrypt (Requirements: 2.1, 10.7)
        $hashedPassword = $this->hashPassword($data['password']);

        $userData = [
            'email' => $data['email'],
            'password_hash' => $hashedPassword,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone' => $data['phone'] ?? null,
            'role' => $data['role'] ?? self::ROLE_CUSTOMER,
            'is_wholesale' => $data['is_wholesale'] ?? 0,
            'company_name' => $data['company_name'] ?? null,
            'is_active' => $data['is_active'] ?? 1
        ];

        return $this->create($userData);
    }


    /**
     * Hash a password using bcrypt
     * Requirements: 2.1, 10.7 - Strong password hashing
     * 
     * @param string $password Plain text password
     * @return string Hashed password
     */
    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, self::PASSWORD_OPTIONS);
    }

    /**
     * Verify a password against a hash
     * Requirements: 2.2 - Secure login with password verification
     * 
     * @param string $password Plain text password to verify
     * @param string $hash The stored password hash
     * @return bool True if password matches
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Authenticate a user by email and password
     * Requirements: 2.2 - Secure login with password verification
     * 
     * @param string $email User's email
     * @param string $password Plain text password
     * @return array|null User data (without password_hash) or null if authentication fails
     */
    public function authenticate(string $email, string $password): ?array
    {
        $user = $this->findByEmail($email);

        if ($user === null) {
            return null;
        }

        // Check if user is active
        if (!$user['is_active']) {
            return null;
        }

        // Verify password (Requirements: 2.2)
        if (!$this->verifyPassword($password, $user['password_hash'])) {
            return null;
        }

        // Remove sensitive data before returning
        unset($user['password_hash']);

        return $user;
    }

    /**
     * Update user's password
     * 
     * @param int $userId User ID
     * @param string $newPassword New plain text password
     * @return bool True on success
     */
    public function updatePassword(int $userId, string $newPassword): bool
    {
        $hashedPassword = $this->hashPassword($newPassword);
        return $this->update($userId, ['password_hash' => $hashedPassword]);
    }

    /**
     * Check if a user has a specific role
     * Requirements: 2.5 - Support three user roles
     * 
     * @param int $userId User ID
     * @param string $role Role to check
     * @return bool True if user has the role
     */
    public function hasRole(int $userId, string $role): bool
    {
        $user = $this->find($userId);
        return $user !== null && $user['role'] === $role;
    }

    /**
     * Check if a user is an admin
     * Requirements: 2.6 - Admin access to all administrative functions
     * 
     * @param int $userId User ID
     * @return bool True if user is admin
     */
    public function isAdmin(int $userId): bool
    {
        return $this->hasRole($userId, self::ROLE_ADMIN);
    }

    /**
     * Check if a user is a content manager
     * Requirements: 2.7 - Content manager limited access
     * 
     * @param int $userId User ID
     * @return bool True if user is content manager
     */
    public function isContentManager(int $userId): bool
    {
        return $this->hasRole($userId, self::ROLE_CONTENT_MANAGER);
    }

    /**
     * Check if email already exists
     * 
     * @param string $email Email to check
     * @param int|null $excludeUserId User ID to exclude (for updates)
     * @return bool True if email exists
     */
    public function emailExists(string $email, ?int $excludeUserId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE email = :email";
        $params = ['email' => $email];

        if ($excludeUserId !== null) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeUserId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Validate user data for creation
     * 
     * @param array $data User data to validate
     * @throws InvalidArgumentException If validation fails
     */
    private function validateUserData(array $data): void
    {
        $required = ['email', 'password', 'first_name', 'last_name'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new InvalidArgumentException("Field '{$field}' is required");
            }
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email format");
        }

        if (strlen($data['password']) < 8) {
            throw new InvalidArgumentException("Password must be at least 8 characters");
        }

        if (isset($data['role']) && !in_array($data['role'], [
            self::ROLE_CUSTOMER,
            self::ROLE_ADMIN,
            self::ROLE_CONTENT_MANAGER
        ], true)) {
            throw new InvalidArgumentException("Invalid user role");
        }
    }

    /**
     * Get all users with a specific role
     * 
     * @param string $role Role to filter by
     * @param int $limit Maximum number of results
     * @param int $offset Offset for pagination
     * @return array List of users
     */
    public function getByRole(string $role, int $limit = 100, int $offset = 0): array
    {
        $sql = "SELECT id, email, first_name, last_name, phone, role, is_active, created_at, updated_at 
                FROM {$this->table} 
                WHERE role = :role 
                ORDER BY created_at DESC 
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':role', $role, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Deactivate a user account
     * 
     * @param int $userId User ID
     * @return bool True on success
     */
    public function deactivate(int $userId): bool
    {
        return $this->update($userId, ['is_active' => 0]);
    }

    /**
     * Activate a user account
     * 
     * @param int $userId User ID
     * @return bool True on success
     */
    public function activate(int $userId): bool
    {
        return $this->update($userId, ['is_active' => 1]);
    }

    /**
     * Check if a user is a wholesale customer
     * Requirement 13.4: Wholesale customer identification
     * 
     * @param int $userId User ID
     * @return bool True if user is a wholesale customer
     */
    public function isWholesale(int $userId): bool
    {
        $user = $this->find($userId);
        return $user !== null && (bool) ($user['is_wholesale'] ?? false);
    }

    /**
     * Set user as wholesale customer
     * Requirement 13.4: Wholesale customer management
     * 
     * @param int $userId User ID
     * @param bool $isWholesale Whether user is wholesale
     * @param string|null $companyName Company name for wholesale customers
     * @return bool True on success
     */
    public function setWholesaleStatus(int $userId, bool $isWholesale, ?string $companyName = null): bool
    {
        $data = ['is_wholesale' => $isWholesale ? 1 : 0];
        if ($companyName !== null) {
            $data['company_name'] = $companyName;
        }
        return $this->update($userId, $data);
    }

    /**
     * Get all wholesale customers
     * 
     * @param int $limit Maximum number of results
     * @param int $offset Offset for pagination
     * @return array List of wholesale customers
     */
    public function getWholesaleCustomers(int $limit = 100, int $offset = 0): array
    {
        $sql = "SELECT id, email, first_name, last_name, phone, company_name, is_active, created_at, updated_at 
                FROM {$this->table} 
                WHERE is_wholesale = 1 
                ORDER BY created_at DESC 
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
}
