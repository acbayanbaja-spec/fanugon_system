<?php
require_once __DIR__ . '/includes/local_only.php';
/**
 * Login Diagnostic Script
 * This will check the database and test password verification
 */
echo "<h1>Login Diagnostic</h1>";

try {
    // Connect to database
    $pdo = new PDO("mysql:host=localhost;dbname=medcare_db", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div style='color: green;'>✓ Database connection successful</div>";
    
    // Check users table
    $stmt = $pdo->query("SELECT * FROM users");
    $users = $stmt->fetchAll();
    
    echo "<h2>Users in Database:</h2>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>User ID</th><th>Username</th><th>Role</th><th>Full Name</th><th>Password Hash</th><th>Active</th></tr>";
    
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>" . $user['user_id'] . "</td>";
        echo "<td><strong>" . htmlspecialchars($user['username']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($user['role']) . "</td>";
        echo "<td>" . htmlspecialchars($user['full_name']) . "</td>";
        echo "<td><small>" . htmlspecialchars(substr($user['password'], 0, 20)) . "...</small></td>";
        echo "<td>" . ($user['is_active'] ? 'Yes' : 'No') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test password verification for each user
    echo "<h2>Password Verification Test:</h2>";
    echo "<p>Testing password 'admin123' against each user:</p>";
    
    foreach ($users as $user) {
        $is_valid = password_verify('admin123', $user['password']);
        echo "<div>";
        echo "<strong>" . htmlspecialchars($user['username']) . ":</strong> ";
        if ($is_valid) {
            echo "<span style='color: green;'>✓ Password CORRECT</span>";
        } else {
            echo "<span style='color: red;'>✗ Password INCORRECT</span>";
        }
        echo "</div>";
    }
    
    // Test the actual login function
    echo "<h2>Simulate Login Attempt:</h2>";
    
    $test_username = 'admin';
    $test_password = 'admin123';
    
    echo "<p>Testing login with username: '$test_username' and password: '$test_password'</p>";
    
    $query = "SELECT user_id, username, password, role, full_name, is_active FROM users WHERE username = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$test_username]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "<div style='color: blue;'>✓ User found in database</div>";
        echo "<div>User ID: " . $user['user_id'] . "</div>";
        echo "<div>Username: " . htmlspecialchars($user['username']) . "</div>";
        echo "<div>Role: " . htmlspecialchars($user['role']) . "</div>";
        echo "<div>Active: " . ($user['is_active'] ? 'Yes' : 'No') . "</div>";
        
        if ($user['is_active']) {
            $is_valid = password_verify($test_password, $user['password']);
            if ($is_valid) {
                echo "<div style='color: green; font-weight: bold;'>✓ LOGIN WOULD SUCCEED</div>";
            } else {
                echo "<div style='color: red; font-weight: bold;'>✗ PASSWORD VERIFICATION FAILED</div>";
            }
        } else {
            echo "<div style='color: red;'>✗ User account is not active</div>";
        }
    } else {
        echo "<div style='color: red;'>✗ User not found in database</div>";
    }
    
    // If no users or passwords don't work, recreate them
    echo "<h2>Fix Options:</h2>";
    
    if (count($users) == 0) {
        echo "<div style='color: red;'>No users found in database!</div>";
        echo "<p><strong>Solution:</strong> Import the schema file to create users</p>";
    } else {
        $all_passwords_work = true;
        foreach ($users as $user) {
            if (!password_verify('admin123', $user['password'])) {
                $all_passwords_work = false;
                break;
            }
        }
        
        if (!$all_passwords_work) {
            echo "<div style='color: red;'>Password hashes don't match 'admin123'</div>";
            echo "<p><strong>Solution:</strong> Recreate users with correct password hash</p>";
            echo "<button onclick='fixPasswords()'>Fix Passwords</button>";
        }
    }
    
} catch (PDOException $e) {
    echo "<div style='color: red;'>✗ Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>

<script>
function fixPasswords() {
    if (confirm('This will recreate all user passwords to use admin123. Continue?')) {
        window.location.href = 'fix_passwords.php';
    }
}
</script>
