<?php
require_once __DIR__ . '/includes/local_only.php';
/**
 * Fix User Passwords
 * Resets the password of every account to the default "admin123".
 * Localhost only - delete this file before deploying the system.
 */
require_once __DIR__ . '/config/config.php';

echo "<h1>Fix User Passwords</h1>";

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Update existing users with correct password hash (admin123)
    $password_hash = password_hash('admin123', PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE users SET password = ?")->execute([$password_hash]);
    echo "<div>✓ Updated all existing user passwords</div>";
    
    $users = $pdo->query("SELECT username, role, full_name FROM users ORDER BY user_id")->fetchAll();
    
    echo "<div style='color: green; font-weight: bold; margin-top: 1rem;'>✓ All user passwords reset successfully!</div>";
    echo "<p><strong>Password for all accounts:</strong> admin123</p>";
    echo "<p><strong>Available accounts:</strong></p>";
    echo "<ul>";
    foreach ($users as $user) {
        echo "<li><strong>" . htmlspecialchars($user['username']) . "</strong> - " . htmlspecialchars($user['full_name']) . " (" . htmlspecialchars($user['role']) . ")</li>";
    }
    echo "</ul>";
    
    echo "<div style='margin-top: 2rem;'>";
    echo "<a href='index.php' style='background: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Go to Login Page</a>";
    echo " ";
    echo "<a href='diagnose_login.php' style='background: #95a5a6; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Test Login Again</a>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div style='color: red;'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>
