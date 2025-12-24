<?php
/**
 * Property-Based Tests for Database Schema Integrity
 * 
 * Feature: ceylon-cinnamon-ecommerce, Property 25: SQL injection prevention
 * Validates: Requirements 10.1
 * 
 * Tests that all database queries use prepared statements to prevent SQL injection.
 */

declare(strict_types=1);

namespace Tests\Property;

use PHPUnit\Framework\TestCase;
use Eris\Generator;
use Eris\TestTrait;

class DatabaseSchemaPropertyTest extends TestCase
{
    use TestTrait;

    private \PDO $db;
    private bool $dbAvailable = false;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Try to connect to test database
        try {
            $_ENV['DB_NAME'] = $_ENV['DB_NAME'] ?? 'ceylon_cinnamon_test';
            require_once __DIR__ . '/../../config/database.php';
            $this->db = \Database::getInstance();
            $this->dbAvailable = true;
            $this->setupTestTables();
        } catch (\Exception $e) {
            $this->dbAvailable = false;
        }
    }

    protected function tearDown(): void
    {
        if ($this->dbAvailable) {
            $this->cleanupTestTables();
            \Database::closeConnection();
        }
        parent::tearDown();
    }

    private function setupTestTables(): void
    {
        // Create a minimal test table for SQL injection testing
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS `test_users` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `email` VARCHAR(255) NOT NULL,
                `name` VARCHAR(100) NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    private function cleanupTestTables(): void
    {
        $this->db->exec("DROP TABLE IF EXISTS `test_users`");
    }


    /**
     * Feature: ceylon-cinnamon-ecommerce, Property 25: SQL injection prevention
     * 
     * For any user input containing SQL injection patterns, the system should
     * safely handle the input without executing malicious SQL when using
     * prepared statements.
     * 
     * Validates: Requirements 10.1
     */
    public function testSqlInjectionPreventionWithPreparedStatements(): void
    {
        if (!$this->dbAvailable) {
            $this->markTestSkipped('Database not available for testing');
        }

        $this->limitTo(20)
            ->forAll(
                Generator\elements([
                    "'; DROP TABLE users; --",
                    "1' OR '1'='1",
                    "admin'--",
                    "1; DELETE FROM users WHERE '1'='1",
                    "' UNION SELECT * FROM users --",
                    "Robert'); DROP TABLE students;--",
                    "1' AND 1=1 UNION SELECT * FROM users--",
                    "' OR ''='",
                    "admin' #",
                    "1' OR 1=1#",
                    "' OR 'x'='x",
                    "'; EXEC xp_cmdshell('dir'); --",
                    "test@example.com' AND 1=1--",
                    "normal_input",
                    "test@test.com",
                    "John O'Brien",
                    "user123",
                ]),
                Generator\suchThat(
                    fn($s) => strlen($s) > 0 && strlen($s) <= 100,
                    Generator\string()
                )
            )
            ->then(function (string $maliciousEmail, string $randomName): void {
                // Insert using prepared statement - should safely escape input
                $stmt = $this->db->prepare(
                    "INSERT INTO test_users (email, name) VALUES (:email, :name)"
                );
                
                $result = $stmt->execute([
                    'email' => $maliciousEmail,
                    'name' => $randomName
                ]);
                
                $this->assertTrue($result, 'Prepared statement should execute successfully');
                
                $insertedId = (int) $this->db->lastInsertId();
                $this->assertGreaterThan(0, $insertedId, 'Should have inserted a record');
                
                // Verify the data was stored literally, not executed as SQL
                $selectStmt = $this->db->prepare(
                    "SELECT email, name FROM test_users WHERE id = :id"
                );
                $selectStmt->execute(['id' => $insertedId]);
                $row = $selectStmt->fetch(\PDO::FETCH_ASSOC);
                
                $this->assertNotNull($row, 'Should retrieve the inserted record');
                $this->assertEquals($maliciousEmail, $row['email'], 
                    'Email should be stored literally without SQL execution');
                $this->assertEquals($randomName, $row['name'],
                    'Name should be stored literally without SQL execution');
                
                // Clean up this test record
                $deleteStmt = $this->db->prepare("DELETE FROM test_users WHERE id = :id");
                $deleteStmt->execute(['id' => $insertedId]);
            });
    }

    /**
     * Test that the Model base class uses prepared statements for all operations.
     */
    public function testModelBaseClassUsesPreparedStatements(): void
    {
        if (!$this->dbAvailable) {
            $this->markTestSkipped('Database not available for testing');
        }

        require_once __DIR__ . '/../../includes/Model.php';
        
        // Create a concrete test model
        $testModel = new class extends \Model {
            protected string $table = 'test_users';
        };

        $this->limitTo(20)
            ->forAll(
                Generator\elements([
                    "'; DROP TABLE test_users; --",
                    "1' OR '1'='1",
                    "test@example.com",
                    "normal_user",
                ])
            )
            ->then(function (string $testInput) use ($testModel): void {
                // Test create operation with potentially malicious input
                $id = $testModel->create([
                    'email' => $testInput,
                    'name' => 'Test User'
                ]);
                
                $this->assertGreaterThan(0, $id, 'Create should return valid ID');
                
                // Verify data integrity
                $record = $testModel->find($id);
                $this->assertNotNull($record);
                $this->assertEquals($testInput, $record['email'],
                    'Input should be stored literally via Model class');
                
                // Clean up
                $testModel->delete($id);
            });
    }
}
