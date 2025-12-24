<?php
/**
 * Database Configuration and Connection Class
 * Uses PDO with prepared statements for SQL injection prevention
 * Requirements: 10.1 (SQL injection prevention via prepared statements)
 */

declare(strict_types=1);

class Database
{
    private static ?PDO $instance = null;
    
    /**
     * PDO connection options for secure database operations
     * - ERRMODE_EXCEPTION: Throw exceptions on errors
     * - FETCH_ASSOC: Return associative arrays by default
     * - EMULATE_PREPARES false: Use real prepared statements for security
     */
    private static array $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];

    /**
     * Prevent direct instantiation (Singleton pattern)
     */
    private function __construct() {}
    
    /**
     * Prevent cloning (Singleton pattern)
     */
    private function __clone() {}

    /**
     * Prevent unserialization (Singleton pattern)
     */
    public function __wakeup()
    {
        throw new RuntimeException("Cannot unserialize singleton");
    }

    /**
     * Get the singleton PDO instance
     * Creates a new connection if one doesn't exist
     * 
     * @return PDO The database connection instance
     * @throws RuntimeException If connection fails
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::$instance = self::createConnection();
        }
        return self::$instance;
    }

    /**
     * Create a new PDO connection with secure settings
     * 
     * @return PDO The new database connection
     * @throws RuntimeException If connection fails
     */
    private static function createConnection(): PDO
    {
        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $dbname = $_ENV['DB_NAME'] ?? 'ceylon_cinnamon';
        $username = $_ENV['DB_USER'] ?? 'root';
        $password = $_ENV['DB_PASS'] ?? '';
        $port = $_ENV['DB_PORT'] ?? '3306';
        $charset = 'utf8mb4';

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

        try {
            $pdo = new PDO($dsn, $username, $password, self::$options);
            return $pdo;
        } catch (PDOException $e) {
            self::handleConnectionError($e);
        }
    }

    /**
     * Handle database connection errors
     * Logs the error and throws a user-friendly exception
     * 
     * @param PDOException $e The caught exception
     * @throws RuntimeException Always throws with sanitized message
     */
    private static function handleConnectionError(PDOException $e): never
    {
        $errorMessage = "Database connection failed: " . $e->getMessage();
        error_log($errorMessage);
        
        // In debug mode, provide more details
        $isDebug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
        
        if ($isDebug) {
            throw new RuntimeException($errorMessage, 500);
        }
        
        // In production, hide sensitive details
        throw new RuntimeException("Database connection failed. Please try again later.", 503);
    }

    /**
     * Close the database connection
     * Useful for long-running scripts or testing
     */
    public static function closeConnection(): void
    {
        self::$instance = null;
    }

    /**
     * Execute a prepared statement with parameters
     * Convenience method for simple queries
     * 
     * @param string $sql The SQL query with placeholders
     * @param array $params The parameters to bind
     * @return PDOStatement The executed statement
     * @throws PDOException If query fails
     */
    public static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Begin a database transaction
     * 
     * @return bool True on success
     */
    public static function beginTransaction(): bool
    {
        return self::getInstance()->beginTransaction();
    }

    /**
     * Commit the current transaction
     * 
     * @return bool True on success
     */
    public static function commit(): bool
    {
        return self::getInstance()->commit();
    }

    /**
     * Roll back the current transaction
     * 
     * @return bool True on success
     */
    public static function rollback(): bool
    {
        return self::getInstance()->rollBack();
    }

    /**
     * Check if currently in a transaction
     * 
     * @return bool True if in transaction
     */
    public static function inTransaction(): bool
    {
        return self::getInstance()->inTransaction();
    }

    /**
     * Get the last inserted ID
     * 
     * @return string The last insert ID
     */
    public static function lastInsertId(): string
    {
        return self::getInstance()->lastInsertId();
    }
}
