<?php
require_once __DIR__ . '/includes/local_only.php';
/**
 * Quick Database Status Check
 */
echo "<h1>Database Status Check</h1>";

try {
    // Try to connect to MySQL first
    $pdo = new PDO("mysql:host=localhost", "root", "");
    echo "<div style='color: green;'>✓ MySQL connection successful</div>";
    
    // Check if medcare_db exists
    $stmt = $pdo->query("SHOW DATABASES LIKE 'medcare_db'");
    if ($stmt->rowCount() > 0) {
        echo "<div style='color: green;'>✓ Database 'medcare_db' exists</div>";
        
        // Connect to the database
        $pdo = new PDO("mysql:host=localhost;dbname=medcare_db", "root", "");
        
        // Check if users table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
        if ($stmt->rowCount() > 0) {
            echo "<div style='color: green;'>✓ Users table exists</div>";
            
            // Count users
            $user_count = $pdo->query("SELECT COUNT(*) as count FROM users")->fetch()['count'];
            echo "<div>Users in database: $user_count</div>";
            
            // Show users
            $users = $pdo->query("SELECT username, role, full_name FROM users")->fetchAll();
            echo "<h3>Available Accounts:</h3>";
            echo "<table border='1' cellpadding='10'>";
            echo "<tr><th>Username</th><th>Role</th><th>Full Name</th></tr>";
            foreach ($users as $user) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($user['username']) . "</td>";
                echo "<td>" . htmlspecialchars($user['role']) . "</td>";
                echo "<td>" . htmlspecialchars($user['full_name']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            echo "<div style='color: green; font-weight: bold; margin-top: 1rem;'>✓ Database is properly set up!</div>";
            echo "<p>You can login with any of the usernames above using password: <strong>admin123</strong></p>";
            echo "<a href='index.php' style='background: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Go to Login Page</a>";
            
        } else {
            echo "<div style='color: red;'>✗ Users table does not exist</div>";
            echo "<p>The database exists but the tables haven't been created yet.</p>";
            echo "<p><strong>You need to import the schema file:</strong></p>";
            echo "<ol>";
            echo "<li>Go to phpMyAdmin: <a href='http://localhost/phpmyadmin' target='_blank'>http://localhost/phpmyadmin</a></li>";
            echo "<li>Click on 'medcare_db' database</li>";
            echo "<li>Click 'Import' tab</li>";
            echo "<li>Choose file: C:\\xampp\\htdocs\\Fanugon_System\\database\\schema.sql</li>";
            echo "<li>Click 'Go'</li>";
            echo "</ol>";
        }
    } else {
        echo "<div style='color: red;'>✗ Database 'medcare_db' does not exist</div>";
        echo "<p>You need to create the database first.</p>";
        echo "<p><strong>Option 1:</strong> Import the schema file (it will create the database automatically)</p>";
        echo "<p><strong>Option 2:</strong> Go to <a href='setup.php' target='_blank'>setup.php</a> for guided installation</p>";
    }
    
} catch (PDOException $e) {
    echo "<div style='color: red;'>✗ MySQL connection failed: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<p>Please make sure XAMPP MySQL service is running.</p>";
}
?>
