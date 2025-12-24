<?php
/**
 * Property-Based Tests for Role-Based Access Control
 * 
 * Feature: ceylon-cinnamon-ecommerce, Property 10: Role-based access control
 * Validates: Requirements 2.5, 2.6, 2.7
 * 
 * Tests that users only have access to functions appropriate for their role.
 */

declare(strict_types=1);

namespace Tests\Property;

use PHPUnit\Framework\TestCase;
use Eris\Generator;
use Eris\TestTrait;

class AccessControlPropertyTest extends TestCase
{
    use TestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Load required files
        require_once __DIR__ . '/../../config/env.php';
        require_once __DIR__ . '/../../includes/SessionManager.php';
        require_once __DIR__ . '/../../includes/Middleware.php';
        require_once __DIR__ . '/../../includes/RoleMiddleware.php';
    }

    protected function tearDown(): void
    {
        // Clean up session if active
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        
        parent::tearDown();
    }

    /**
     * Feature: ceylon-cinnamon-ecommerce, Property 10: Role-based access control
     * 
     * For any user with a specific role, they should only have access to 
     * functions appropriate for that role.
     * 
     * Property: Admin role should have access to all functions
     * Requirements: 2.6 - Admin access to all administrative functions
     * 
     * Validates: Requirements 2.5, 2.6, 2.7
     */
    public function testAdminHasAccessToAllFunctions(): void
    {
        $this->limitTo(20)
            ->forAll(
                Generator\elements(['admin', 'content_manager', 'customer']) // Target roles
            )
            ->then(function (string $targetRole): void {
                // Create middleware that requires the target role
                $middleware = new \RoleMiddleware([$targetRole]);
                
                // Admin should always have access (role hierarchy)
                $this->assertTrue(
                    $middleware->isRoleAllowed('admin'),
                    "Admin should have access to {$targetRole} functions"
                );
            });
    }

    /**
     * Property: Content manager has limited access
     * Requirements: 2.7 - Content manager limited access to content management only
     * 
     * Validates: Requirements 2.7
     */
    public function testContentManagerHasLimitedAccess(): void
    {
        $this->limitTo(20)
            ->forAll(
                Generator\bool() // Random test variation
            )
            ->then(function (bool $variation): void {
                // Content manager should have access to content_manager and customer roles
                $contentManagerMiddleware = new \RoleMiddleware(['content_manager']);
                $customerMiddleware = new \RoleMiddleware(['customer']);
                
                // Content manager can access content_manager functions
                $this->assertTrue(
                    $contentManagerMiddleware->isRoleAllowed('content_manager'),
                    'Content manager should have access to content_manager functions'
                );
                
                // Content manager can access customer functions
                $this->assertTrue(
                    $customerMiddleware->isRoleAllowed('content_manager'),
                    'Content manager should have access to customer functions'
                );
                
                // Content manager should NOT have access to admin-only functions
                $adminOnlyMiddleware = new \RoleMiddleware(['admin']);
                $this->assertFalse(
                    $adminOnlyMiddleware->isRoleAllowed('content_manager'),
                    'Content manager should NOT have access to admin-only functions'
                );
            });
    }

    /**
     * Property: Customer has most restricted access
     * Requirements: 2.5 - Support three user roles
     * 
     * Validates: Requirements 2.5
     */
    public function testCustomerHasMostRestrictedAccess(): void
    {
        $this->limitTo(20)
            ->forAll(
                Generator\bool() // Random test variation
            )
            ->then(function (bool $variation): void {
                // Customer should only have access to customer functions
                $customerMiddleware = new \RoleMiddleware(['customer']);
                
                // Customer can access customer functions
                $this->assertTrue(
                    $customerMiddleware->isRoleAllowed('customer'),
                    'Customer should have access to customer functions'
                );
                
                // Customer should NOT have access to content_manager functions
                $contentManagerMiddleware = new \RoleMiddleware(['content_manager']);
                $this->assertFalse(
                    $contentManagerMiddleware->isRoleAllowed('customer'),
                    'Customer should NOT have access to content_manager functions'
                );
                
                // Customer should NOT have access to admin functions
                $adminMiddleware = new \RoleMiddleware(['admin']);
                $this->assertFalse(
                    $adminMiddleware->isRoleAllowed('customer'),
                    'Customer should NOT have access to admin functions'
                );
            });
    }

    /**
     * Property: Role hierarchy is correctly enforced
     * 
     * For any role combination, the hierarchy should be:
     * admin > content_manager > customer
     * 
     * Validates: Requirements 2.5, 2.6, 2.7
     */
    public function testRoleHierarchyIsCorrectlyEnforced(): void
    {
        $this->limitTo(20)
            ->forAll(
                Generator\elements(['admin', 'content_manager', 'customer']), // User role
                Generator\elements(['admin', 'content_manager', 'customer'])  // Required role
            )
            ->then(function (string $userRole, string $requiredRole): void {
                $middleware = new \RoleMiddleware([$requiredRole]);
                $hasAccess = $middleware->isRoleAllowed($userRole);
                
                // Define expected access based on hierarchy
                $expectedAccess = $this->shouldHaveAccess($userRole, $requiredRole);
                
                $this->assertEquals(
                    $expectedAccess,
                    $hasAccess,
                    "User with role '{$userRole}' " . 
                    ($expectedAccess ? 'should' : 'should NOT') . 
                    " have access to '{$requiredRole}' functions"
                );
            });
    }

    /**
     * Property: Static factory methods create correct middleware
     * 
     * Validates: Requirements 2.5, 2.6, 2.7
     */
    public function testStaticFactoryMethodsCreateCorrectMiddleware(): void
    {
        $this->limitTo(20)
            ->forAll(
                Generator\elements(['admin', 'content_manager', 'customer']) // User role
            )
            ->then(function (string $userRole): void {
                // Test adminOnly factory
                $adminOnly = \RoleMiddleware::adminOnly();
                $expectedAdminAccess = ($userRole === 'admin');
                $this->assertEquals(
                    $expectedAdminAccess,
                    $adminOnly->isRoleAllowed($userRole),
                    "adminOnly() should " . ($expectedAdminAccess ? '' : 'NOT ') . 
                    "allow {$userRole}"
                );
                
                // Test contentManager factory
                $contentManager = \RoleMiddleware::contentManager();
                $expectedContentAccess = in_array($userRole, ['admin', 'content_manager'], true);
                $this->assertEquals(
                    $expectedContentAccess,
                    $contentManager->isRoleAllowed($userRole),
                    "contentManager() should " . ($expectedContentAccess ? '' : 'NOT ') . 
                    "allow {$userRole}"
                );
                
                // Test customer factory
                $customer = \RoleMiddleware::customer();
                $expectedCustomerAccess = true; // All roles can access customer functions
                $this->assertEquals(
                    $expectedCustomerAccess,
                    $customer->isRoleAllowed($userRole),
                    "customer() should allow {$userRole}"
                );
            });
    }

    /**
     * Property: Multiple allowed roles work correctly
     * 
     * For any combination of allowed roles, access should be granted
     * if the user has any of the allowed roles (or higher in hierarchy).
     * 
     * Validates: Requirements 2.5
     */
    public function testMultipleAllowedRolesWorkCorrectly(): void
    {
        // Use predefined role combinations to avoid evaluation ratio issues
        $roleCombinations = [
            ['admin'],
            ['content_manager'],
            ['customer'],
            ['admin', 'content_manager'],
            ['admin', 'customer'],
            ['content_manager', 'customer'],
            ['admin', 'content_manager', 'customer'],
        ];
        
        $this->limitTo(20)
            ->forAll(
                Generator\elements(['admin', 'content_manager', 'customer']), // User role
                Generator\elements($roleCombinations) // Allowed roles (predefined non-empty combinations)
            )
            ->then(function (string $userRole, array $allowedRoles): void {
                $middleware = new \RoleMiddleware($allowedRoles);
                $hasAccess = $middleware->isRoleAllowed($userRole);
                
                // User should have access if they have any of the allowed roles
                // or a higher role in the hierarchy
                $expectedAccess = false;
                foreach ($allowedRoles as $allowedRole) {
                    if ($this->shouldHaveAccess($userRole, $allowedRole)) {
                        $expectedAccess = true;
                        break;
                    }
                }
                
                $this->assertEquals(
                    $expectedAccess,
                    $hasAccess,
                    "User '{$userRole}' with allowed roles [" . implode(', ', $allowedRoles) . "] " .
                    ($expectedAccess ? 'should' : 'should NOT') . " have access"
                );
            });
    }

    /**
     * Property: Empty allowed roles requires only authentication
     * 
     * When no specific roles are required, any authenticated user should pass.
     * 
     * Validates: Requirements 2.5
     */
    public function testEmptyAllowedRolesRequiresOnlyAuthentication(): void
    {
        $this->limitTo(20)
            ->forAll(
                Generator\elements(['admin', 'content_manager', 'customer']) // User role
            )
            ->then(function (string $userRole): void {
                // Empty allowed roles means any authenticated user can access
                $middleware = new \RoleMiddleware([]);
                
                // getAllowedRoles should return empty array
                $this->assertEmpty(
                    $middleware->getAllowedRoles(),
                    'Empty middleware should have no required roles'
                );
            });
    }

    /**
     * Property: Invalid roles are rejected
     * 
     * For any invalid role, access should be denied.
     * 
     * Validates: Requirements 2.5
     */
    public function testInvalidRolesAreRejected(): void
    {
        $this->limitTo(20)
            ->forAll(
                Generator\string() // Random invalid role
            )
            ->when(fn($role) => !in_array($role, ['admin', 'content_manager', 'customer'], true))
            ->then(function (string $invalidRole): void {
                // Any middleware should reject invalid roles
                $adminMiddleware = new \RoleMiddleware(['admin']);
                $contentMiddleware = new \RoleMiddleware(['content_manager']);
                $customerMiddleware = new \RoleMiddleware(['customer']);
                
                $this->assertFalse(
                    $adminMiddleware->isRoleAllowed($invalidRole),
                    "Invalid role '{$invalidRole}' should not have admin access"
                );
                
                $this->assertFalse(
                    $contentMiddleware->isRoleAllowed($invalidRole),
                    "Invalid role '{$invalidRole}' should not have content_manager access"
                );
                
                $this->assertFalse(
                    $customerMiddleware->isRoleAllowed($invalidRole),
                    "Invalid role '{$invalidRole}' should not have customer access"
                );
            });
    }

    /**
     * Helper method to determine if a user role should have access to a required role
     * Based on role hierarchy: admin > content_manager > customer
     * 
     * @param string $userRole User's role
     * @param string $requiredRole Required role for access
     * @return bool True if user should have access
     */
    private function shouldHaveAccess(string $userRole, string $requiredRole): bool
    {
        $hierarchy = [
            'admin' => 3,
            'content_manager' => 2,
            'customer' => 1
        ];
        
        $userLevel = $hierarchy[$userRole] ?? 0;
        $requiredLevel = $hierarchy[$requiredRole] ?? 0;
        
        return $userLevel >= $requiredLevel;
    }
}
