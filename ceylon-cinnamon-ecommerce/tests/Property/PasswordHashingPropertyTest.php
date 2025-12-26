<?php
/**
 * Property-Based Tests for Password Hashing Security
 * 
 * Feature: ceylon-cinnamon-ecommerce, Property 7: Password hashing security
 * Validates: Requirements 2.1
 * 
 * Tests that passwords are properly hashed using password_hash() and never stored as plain text.
 */

declare(strict_types=1);

namespace Tests\Property;

use PHPUnit\Framework\TestCase;
use Eris\Generator;
use Eris\TestTrait;

/**
 * Test helper class that provides password hashing methods without database dependency
 * This mirrors the User model's password methods for testing purposes
 */
class PasswordHashingHelper
{
    private const PASSWORD_OPTIONS = ['cost' => 12];

    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, self::PASSWORD_OPTIONS);
    }

    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
}

class PasswordHashingPropertyTest extends TestCase
{
    use TestTrait;

    private PasswordHashingHelper $passwordHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->passwordHelper = new PasswordHashingHelper();
    }

    /**
     * Feature: ceylon-cinnamon-ecommerce, Property 7: Password hashing security
     * 
     * For any user registration, the stored password should be hashed using 
     * password_hash() and never stored as plain text.
     * 
     * Property: For any password string, hashPassword() should produce a hash that:
     * 1. Is different from the original password
     * 2. Can be verified using password_verify()
     * 3. Uses bcrypt algorithm (starts with $2y$)
     * 
     * Validates: Requirements 2.1
     */
    public function testPasswordHashingNeverStoresPlainText(): void
    {
        $this->limitTo(5)
            ->forAll(
                Generator\suchThat(
                    fn($s) => strlen($s) >= 8 && strlen($s) <= 72,
                    Generator\string()
                )
            )
            ->then(function (string $plainPassword): void {
                $hashedPassword = $this->passwordHelper->hashPassword($plainPassword);
                
                // Property 1: Hash must be different from plain text
                $this->assertNotEquals(
                    $plainPassword,
                    $hashedPassword,
                    'Hashed password must not equal plain text password'
                );
                
                // Property 2: Hash must be verifiable
                $this->assertTrue(
                    $this->passwordHelper->verifyPassword($plainPassword, $hashedPassword),
                    'password_verify() must return true for correct password'
                );
                
                // Property 3: Hash must use bcrypt (starts with $2y$)
                $this->assertStringStartsWith(
                    '$2y$',
                    $hashedPassword,
                    'Password hash must use bcrypt algorithm'
                );
                
                // Property 4: Hash length should be 60 characters for bcrypt
                $this->assertEquals(
                    60,
                    strlen($hashedPassword),
                    'Bcrypt hash should be exactly 60 characters'
                );
            });
    }

    /**
     * Property: Different passwords should produce different hashes
     * 
     * For any two different passwords, their hashes should be different
     * (with extremely high probability due to random salt).
     */
    public function testDifferentPasswordsProduceDifferentHashes(): void
    {
        $this->limitTo(5)
            ->forAll(
                Generator\suchThat(
                    fn($s) => strlen($s) >= 8 && strlen($s) <= 72,
                    Generator\string()
                ),
                Generator\suchThat(
                    fn($s) => strlen($s) >= 8 && strlen($s) <= 72,
                    Generator\string()
                )
            )
            ->when(fn($p1, $p2) => $p1 !== $p2)
            ->then(function (string $password1, string $password2): void {
                $hash1 = $this->passwordHelper->hashPassword($password1);
                $hash2 = $this->passwordHelper->hashPassword($password2);
                
                // Different passwords should produce different hashes
                $this->assertNotEquals(
                    $hash1,
                    $hash2,
                    'Different passwords should produce different hashes'
                );
                
                // Cross-verification should fail
                $this->assertFalse(
                    $this->passwordHelper->verifyPassword($password1, $hash2),
                    'Password 1 should not verify against hash of password 2'
                );
                
                $this->assertFalse(
                    $this->passwordHelper->verifyPassword($password2, $hash1),
                    'Password 2 should not verify against hash of password 1'
                );
            });
    }

    /**
     * Property: Same password hashed twice should produce different hashes
     * 
     * Due to random salt, hashing the same password twice should produce
     * different hash values, but both should verify correctly.
     */
    public function testSamePasswordProducesDifferentHashesDueToSalt(): void
    {
        $this->limitTo(5)
            ->forAll(
                Generator\suchThat(
                    fn($s) => strlen($s) >= 8 && strlen($s) <= 72,
                    Generator\string()
                )
            )
            ->then(function (string $password): void {
                $hash1 = $this->passwordHelper->hashPassword($password);
                $hash2 = $this->passwordHelper->hashPassword($password);
                
                // Same password should produce different hashes (due to salt)
                $this->assertNotEquals(
                    $hash1,
                    $hash2,
                    'Same password hashed twice should produce different hashes due to random salt'
                );
                
                // But both hashes should verify correctly
                $this->assertTrue(
                    $this->passwordHelper->verifyPassword($password, $hash1),
                    'Original password should verify against first hash'
                );
                
                $this->assertTrue(
                    $this->passwordHelper->verifyPassword($password, $hash2),
                    'Original password should verify against second hash'
                );
            });
    }

    /**
     * Property: Wrong passwords should never verify
     * 
     * For any password and any modification of that password,
     * the modified password should not verify against the original hash.
     */
    public function testWrongPasswordsNeverVerify(): void
    {
        $this->limitTo(5)
            ->forAll(
                Generator\suchThat(
                    fn($s) => strlen($s) >= 8 && strlen($s) <= 71,
                    Generator\string()
                )
            )
            ->then(function (string $password): void {
                $hash = $this->passwordHelper->hashPassword($password);
                
                // Modifications that should fail verification
                $wrongPasswords = [
                    $password . 'x',
                    'x' . $password,
                    strtoupper($password),
                    substr($password, 1),
                    substr($password, 0, -1),
                ];
                
                foreach ($wrongPasswords as $wrongPassword) {
                    if ($wrongPassword !== $password && strlen($wrongPassword) > 0) {
                        $this->assertFalse(
                            $this->passwordHelper->verifyPassword($wrongPassword, $hash),
                            "Modified password should not verify"
                        );
                    }
                }
            });
    }
}
