<?php
/**
 * Database Setup Script
 * This script will create the database schema and populate it with seed data
 * 
 * Usage: Run this file in your browser: http://localhost/ceylon-cinnamon-ecommerce/setup_database.php
 * Or run from command line: php setup_database.php
 */

declare(strict_types=1);

// Load configuration
require_once __DIR__ . '/includes/autoload.php';

// Set execution time limit for large imports
set_time_limit(300); // 5 minutes

echo "<!DOCTYPE html>\n<html>\n<head>\n<title>Ceylon Cinnamon Database Setup</title>\n";
echo "<style>body{font-family:Arial,sans-serif;margin:40px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>\n";
echo "</head>\n<body>\n";
echo "<h1>Ceylon Cinnamon E-commerce Database Setup</h1>\n";

try {
    // Database configuration
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $dbname = $_ENV['DB_NAME'] ?? 'ceylon_cinnamon';
    $username = $_ENV['DB_USER'] ?? 'root';
    $password = $_ENV['DB_PASS'] ?? '';
    $port = $_ENV['DB_PORT'] ?? '3306';

    echo "<div class='info'><strong>Database Configuration:</strong></div>\n";
    echo "<ul>\n";
    echo "<li>Host: {$host}:{$port}</li>\n";
    echo "<li>Database: {$dbname}</li>\n";
    echo "<li>User: {$username}</li>\n";
    echo "</ul>\n";

    // Connect to MySQL server (without database)
    $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    echo "<div class='success'>✓ Connected to MySQL server</div>\n";

    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<div class='success'>✓ Database '{$dbname}' created/verified</div>\n";

    // Connect to the specific database
    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    echo "<div class='success'>✓ Connected to database '{$dbname}'</div>\n";

    // Check if schema exists
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "<div class='info'>📋 No tables found. Creating schema first...</div>\n";
        
        // Execute schema.sql
        $schemaFile = __DIR__ . '/sql/schema.sql';
        if (file_exists($schemaFile)) {
            $schema = file_get_contents($schemaFile);
            
            // Split by semicolon and execute each statement
            $statements = array_filter(array_map('trim', explode(';', $schema)));
            
            foreach ($statements as $statement) {
                if (!empty($statement) && !preg_match('/^(--|\/\*|\s*$)/', $statement)) {
                    try {
                        $pdo->exec($statement);
                    } catch (PDOException $e) {
                        // Ignore some common warnings
                        if (!str_contains($e->getMessage(), 'already exists') && 
                            !str_contains($e->getMessage(), 'Unknown table')) {
                            throw $e;
                        }
                    }
                }
            }
            
            echo "<div class='success'>✓ Database schema created successfully</div>\n";
        } else {
            throw new Exception("Schema file not found: {$schemaFile}");
        }
    } else {
        echo "<div class='info'>📋 Found " . count($tables) . " existing tables</div>\n";
    }

    // Check if seed data already exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE email = 'admin@ceyloncinnamon.com'");
    $adminExists = (int) $stmt->fetchColumn() > 0;

    if ($adminExists) {
        echo "<div class='info'>⚠️ Seed data appears to already exist (admin user found)</div>\n";
        echo "<div class='info'>Do you want to proceed anyway? This will add duplicate data.</div>\n";
        
        // In web context, show a form to confirm
        if (isset($_SERVER['HTTP_HOST']) && !isset($_POST['confirm'])) {
            echo "<form method='POST'>\n";
            echo "<input type='hidden' name='confirm' value='1'>\n";
            echo "<button type='submit' style='padding:10px 20px; background:#007cba; color:white; border:none; cursor:pointer;'>Yes, Import Seed Data Anyway</button>\n";
            echo "</form>\n";
            echo "<p><a href='public/'>← Go to Website</a></p>\n";
            echo "</body></html>";
            exit;
        }
        
        // In CLI context, ask for confirmation
        if (!isset($_SERVER['HTTP_HOST']) && !isset($_POST['confirm'])) {
            echo "Seed data already exists. Continue anyway? (y/N): ";
            $handle = fopen("php://stdin", "r");
            $line = fgets($handle);
            if (trim(strtolower($line)) !== 'y') {
                echo "Aborted.\n";
                exit;
            }
        }
    }

    // Execute seed data
    echo "<div class='info'>📦 Importing seed data...</div>\n";
    
    $seedFile = __DIR__ . '/sql/seed_data.sql';
    if (file_exists($seedFile)) {
        $seedData = file_get_contents($seedFile);
        
        // Split by semicolon and execute each statement
        $statements = array_filter(array_map('trim', explode(';', $seedData)));
        $successCount = 0;
        
        foreach ($statements as $statement) {
            if (!empty($statement) && !preg_match('/^(--|\/\*|\s*$)/', $statement)) {
                try {
                    $pdo->exec($statement);
                    $successCount++;
                } catch (PDOException $e) {
                    // Show warning but continue
                    echo "<div class='error'>⚠️ Warning: " . htmlspecialchars($e->getMessage()) . "</div>\n";
                }
            }
        }
        
        echo "<div class='success'>✓ Seed data imported successfully ({$successCount} statements executed)</div>\n";
    } else {
        throw new Exception("Seed data file not found: {$seedFile}");
    }

    // Verify data
    echo "<div class='info'>🔍 Verifying imported data...</div>\n";
    
    $checks = [
        'users' => 'SELECT COUNT(*) FROM users',
        'categories' => 'SELECT COUNT(*) FROM categories', 
        'products' => 'SELECT COUNT(*) FROM products',
        'blog_posts' => 'SELECT COUNT(*) FROM blog_posts',
        'orders' => 'SELECT COUNT(*) FROM orders'
    ];
    
    echo "<ul>\n";
    foreach ($checks as $table => $query) {
        $stmt = $pdo->query($query);
        $count = $stmt->fetchColumn();
        echo "<li>{$table}: {$count} records</li>\n";
    }
    echo "</ul>\n";

    echo "<div class='success'><strong>🎉 Database setup completed successfully!</strong></div>\n";
    
    echo "<h2>Default Login Credentials</h2>\n";
    echo "<table border='1' cellpadding='10' style='border-collapse:collapse;'>\n";
    echo "<tr><th>Role</th><th>Email</th><th>Password</th></tr>\n";
    echo "<tr><td>Admin</td><td>admin@ceyloncinnamon.com</td><td>password</td></tr>\n";
    echo "<tr><td>Content Manager</td><td>content@ceyloncinnamon.com</td><td>password</td></tr>\n";
    echo "<tr><td>Customer</td><td>customer@example.com</td><td>password</td></tr>\n";
    echo "<tr><td>Wholesale</td><td>wholesale@example.com</td><td>password</td></tr>\n";
    echo "</table>\n";
    
    echo "<p><strong>⚠️ Important:</strong> Change these passwords immediately in production!</p>\n";
    
    echo "<h2>Next Steps</h2>\n";
    echo "<ol>\n";
    echo "<li><a href='public/' target='_blank'>Visit the website</a></li>\n";
    echo "<li><a href='public/admin' target='_blank'>Access admin panel</a></li>\n";
    echo "<li>Configure payment gateways in .env file</li>\n";
    echo "<li>Set up email configuration</li>\n";
    echo "<li>Customize content and products</li>\n";
    echo "</ol>\n";

} catch (Exception $e) {
    echo "<div class='error'><strong>❌ Error:</strong> " . htmlspecialchars($e->getMessage()) . "</div>\n";
    echo "<div class='info'>Please check your database configuration in the .env file.</div>\n";
}

echo "</body></html>";
?>