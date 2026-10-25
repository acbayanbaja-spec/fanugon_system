<?php
require_once __DIR__ . '/includes/local_only.php';
/**
 * MedCare System - Setup Script
 * This script helps set up the database and verify the installation
 */

// Prevent access if already configured
if (file_exists(__DIR__ . '/config/config.php') && file_exists(__DIR__ . '/.installed')) {
    die('System is already installed. Delete the .installed file to re-run setup.');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedCare System Setup</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
            background: #f4f4f4;
        }
        .container {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 0.5rem;
        }
        .step {
            margin: 2rem 0;
            padding: 1rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .step h3 {
            color: #3498db;
            margin-top: 0;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 4px;
            margin: 1rem 0;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 4px;
            margin: 1rem 0;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 1rem;
            border-radius: 4px;
            margin: 1rem 0;
        }
        form {
            margin: 1rem 0;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 1rem;
            box-sizing: border-box;
        }
        button {
            background: #3498db;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
        }
        button:hover {
            background: #2980b9;
        }
        .status {
            font-weight: 600;
        }
        .status.pass {
            color: #28a745;
        }
        .status.fail {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>MedCare System Setup</h1>
        
        <div class="step">
            <h3>Step 1: System Requirements Check</h3>
            <?php
            $requirements = [
                'PHP Version >= 7.4' => version_compare(PHP_VERSION, '7.4.0', '>='),
                'PDO Extension' => extension_loaded('pdo'),
                'PDO MySQL Extension' => extension_loaded('pdo_mysql'),
                'MySQLi Extension' => extension_loaded('mysqli'),
                'Session Support' => extension_loaded('session'),
            ];
            
            foreach ($requirements as $requirement => $passed) {
                echo '<div><span class="status ' . ($passed ? 'pass' : 'fail') . '">' . 
                     ($passed ? '✓' : '✗') . '</span> ' . $requirement . '</div>';
            }
            
            $all_passed = array_filter($requirements);
            if (count($all_passed) == count($requirements)) {
                echo '<div class="success">All requirements met!</div>';
            } else {
                echo '<div class="error">Some requirements are not met. Please install missing extensions.</div>';
            }
            ?>
        </div>
        
        <div class="step">
            <h3>Step 2: Database Configuration</h3>
            <form method="POST" action="">
                <label for="db_host">Database Host:</label>
                <input type="text" id="db_host" name="db_host" value="localhost" required>
                
                <label for="db_name">Database Name:</label>
                <input type="text" id="db_name" name="db_name" value="medcare_db" required>
                
                <label for="db_user">Database Username:</label>
                <input type="text" id="db_user" name="db_user" value="root" required>
                
                <label for="db_pass">Database Password:</label>
                <input type="password" id="db_pass" name="db_pass" value="">
                
                <button type="submit" name="test_connection">Test Connection</button>
            </form>
            
            <?php
            if (isset($_POST['test_connection'])) {
                $db_host = $_POST['db_host'];
                $db_name = $_POST['db_name'];
                $db_user = $_POST['db_user'];
                $db_pass = $_POST['db_pass'];
                
                try {
                    $dsn = "mysql:host=$db_host;charset=utf8mb4";
                    $pdo = new PDO($dsn, $db_user, $db_pass);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    echo '<div class="success">Database connection successful!</div>';
                    
                    // Test if database exists
                    $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$db_name'");
                    if ($stmt->rowCount() > 0) {
                        echo '<div class="warning">Database "' . htmlspecialchars($db_name) . '" already exists.</div>';
                    } else {
                        echo '<div class="success">Database "' . htmlspecialchars($db_name) . '" does not exist yet. It will be created during installation.</div>';
                    }
                    
                    // Store connection info for next step
                    echo '<form method="POST" action="">
                        <input type="hidden" name="db_host" value="' . htmlspecialchars($db_host) . '">
                        <input type="hidden" name="db_name" value="' . htmlspecialchars($db_name) . '">
                        <input type="hidden" name="db_user" value="' . htmlspecialchars($db_user) . '">
                        <input type="hidden" name="db_pass" value="' . htmlspecialchars($db_pass) . '">
                        <button type="submit" name="install_database">Install Database</button>
                    </form>';
                    
                } catch (PDOException $e) {
                    echo '<div class="error">Database connection failed: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
            }
            ?>
        </div>
        
        <?php
        if (isset($_POST['install_database'])) {
            $db_host = $_POST['db_host'];
            $db_name = $_POST['db_name'];
            $db_user = $_POST['db_user'];
            $db_pass = $_POST['db_pass'];
            
            try {
                $dsn = "mysql:host=$db_host;charset=utf8mb4";
                $pdo = new PDO($dsn, $db_user, $db_pass);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Read schema file
                $schema_file = __DIR__ . '/database/schema.sql';
                if (!file_exists($schema_file)) {
                    throw new Exception('Schema file not found: ' . $schema_file);
                }
                
                $schema = file_get_contents($schema_file);
                
                // Split by semicolon and execute each statement
                $statements = explode(';', $schema);
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $pdo->exec("USE `$db_name`");
                
                $error_count = 0;
                foreach ($statements as $statement) {
                    $statement = trim($statement);
                    if (!empty($statement)) {
                        try {
                            $pdo->exec($statement);
                        } catch (PDOException $e) {
                            $error_count++;
                            // Ignore errors for existing data
                        }
                    }
                }
                
                echo '<div class="step">
                    <h3>Step 3: Database Installation</h3>';
                
                if ($error_count > 0 && $error_count < 10) {
                    echo '<div class="warning">Database installed with some existing data (this is normal if re-installing).</div>';
                } elseif ($error_count >= 10) {
                    echo '<div class="error">Database installation encountered multiple errors.</div>';
                } else {
                    echo '<div class="success">Database installed successfully!</div>';
                }
                
                // Update database configuration
                $config_content = "<?php
/**
 * MedCare System - Database Configuration
 */

\$host = '$db_host';
\$db_name = '$db_name';
\$username = '$db_user';
\$password = '$db_pass';
\$charset = 'utf8mb4';

try {
    \$dsn = \"mysql:host=\$host;dbname=\$db_name;charset=\$charset\";
    \$pdo = new PDO(\$dsn, \$username, \$password);
    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo 'Database connection successful!';
} catch(PDOException \$e) {
    die('Database connection failed: ' . \$e->getMessage());
}
?>";
                
                file_put_contents(__DIR__ . '/config/test_db.php', $config_content);
                
                echo '<div class="success">Configuration file created!</div>';
                echo '<p><strong>Next steps:</strong></p>
                    <ol>
                        <li>Delete or rename setup.php</li>
                        <li>Remove the test_db.php file from config/</li>
                        <li>Access the system at <a href="index.php">index.php</a></li>
                        <li>Login with default credentials (username: admin, password: admin123)</li>
                    </ol>';
                
                // Create installation marker
                file_put_contents(__DIR__ . '/.installed', date('Y-m-d H:i:s'));
                
                echo '</div>';
                
            } catch (Exception $e) {
                echo '<div class="error">Installation failed: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        }
        ?>
        
        <div class="step">
            <h3>Default Login Credentials</h3>
            <p>After installation, you can login with:</p>
            <ul>
                <li><strong>Username:</strong> admin</li>
                <li><strong>Password:</strong> admin123</li>
                <li><strong>Role:</strong> Staff (System Administrator)</li>
            </ul>
            <p class="warning">Please change the default password after first login!</p>
        </div>
    </div>
</body>
</html>
