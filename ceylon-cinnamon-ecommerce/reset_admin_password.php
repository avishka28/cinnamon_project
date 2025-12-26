<?php
/**
 * Reset All User Passwords Script
 * Run this to reset all user passwords to 'Admin@123'
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/autoload.php';

echo "<!DOCTYPE html>\n<html>\n<head>\n<title>Reset User Passwords</title>\n";
echo "<style>body{font-family:Arial,sans-serif;margin:40px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>\n";
echo "</head>\n<body>\n";
echo "<h1>Reset User Passwords</h1>\n";

try {
    // Database configuration
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $dbname = $_ENV['DB_NAME'] ?? 'ceylon_cinnamon';
    $username = $_ENV['DB_USER'] ?? 'root';
    $dbPassword = $_ENV['DB_PASS'] ?? '';
    $port = $_ENV['DB_PORT'] ?? '3306';

    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $dbPassword, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    echo "<div class='success'>✓ Connected to database</div>\n";

    // New password for all users
    $newPassword = 'Admin@123';
    $newHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
    
    echo "<div class='info'>Generated new password hash for 'Admin@123'</div>\n";

    // Get all users
    $stmt = $pdo->query("SELECT id, email, role, is_active FROM users");
    $users = $stmt->fetchAll();

    if (empty($users)) {
        echo "<div class='error'>No users found in database! Creating admin user...</div>\n";
        
        $insertStmt = $pdo->prepare("INSERT INTO users (email, password_hash, first_name, last_name, phone, role, is_wholesale, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $insertStmt->execute([
            'admin@ceyloncinnamon.com',
            $newHash,
            'Admin',
            'User',
            '+94 11 234 5678',
            'admin',
            0,
            1
        ]);
        
        echo "<div class='success'>✓ Admin user created</div>\n";
    } else {
        echo "<div class='info'>Found " . count($users) . " users. Updating passwords...</div>\n";
        
        // Update all user passwords
        $updateStmt = $pdo->prepare("UPDATE users SET password_hash = ?, is_active = 1 WHERE id = ?");
        
        echo "<ul>\n";
        foreach ($users as $user) {
            $updateStmt->execute([$newHash, $user['id']]);
            echo "<li>Updated: {$user['email']} (Role: {$user['role']})</li>\n";
        }
        echo "</ul>\n";
        
        echo "<div class='success'>✓ All passwords updated to 'Admin@123'</div>\n";
    }

    echo "<h2>Login Credentials</h2>\n";
    echo "<table border='1' cellpadding='10' style='border-collapse:collapse;'>\n";
    echo "<tr><th>Role</th><th>Email</th><th>Password</th></tr>\n";
    echo "<tr><td>Admin</td><td>admin@ceyloncinnamon.com</td><td>Admin@123</td></tr>\n";
    echo "<tr><td>Content Manager</td><td>content@ceyloncinnamon.com</td><td>Admin@123</td></tr>\n";
    echo "<tr><td>Customer</td><td>customer@example.com</td><td>Admin@123</td></tr>\n";
    echo "<tr><td>Wholesale</td><td>wholesale@example.com</td><td>Admin@123</td></tr>\n";
    echo "</table>\n";
    
    echo "<p><strong>⚠️ Important:</strong> Change these passwords in production!</p>\n";
    echo "<p><a href='public/admin/login'>Go to Admin Login</a></p>\n";

} catch (Exception $e) {
    echo "<div class='error'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</div>\n";
}

echo "</body></html>";
