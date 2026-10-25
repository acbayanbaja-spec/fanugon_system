<?php
require_once __DIR__ . '/includes/local_only.php';
/**
 * MedCare System - Database Connection Test
 * This script tests the database connection and reports the status
 */

echo "<h1>MedCare System - Database Connection Test</h1>";

try {
    require_once __DIR__ . '/config/config.php';
    require_once __DIR__ . '/config/database.php';
    
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "<div style='color: green; font-weight: bold;'>✓ Database connection successful!</div>";
    
    // Test if tables exist
    $tables = ['users', 'patients', 'consultations', 'diagnoses', 'treatments', 'prescriptions', 'inventory', 'treatment_progress', 'healthcare_communication', 'health_education'];
    
    echo "<h2>Table Status:</h2>";
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->rowCount() > 0) {
            $count = $conn->query("SELECT COUNT(*) as count FROM $table")->fetch()['count'];
            echo "<div style='color: green;'>✓ Table '$table' exists ($count records)</div>";
        } else {
            echo "<div style='color: red;'>✗ Table '$table' not found</div>";
        }
    }
    
    // Test sample data
    echo "<h2>Sample Data Check:</h2>";
    $user_count = $conn->query("SELECT COUNT(*) as count FROM users")->fetch()['count'];
    $patient_count = $conn->query("SELECT COUNT(*) as count FROM patients")->fetch()['count'];
    $inventory_count = $conn->query("SELECT COUNT(*) as count FROM inventory")->fetch()['count'];
    
    echo "<div>Users: $user_count</div>";
    echo "<div>Patients: $patient_count</div>";
    echo "<div>Inventory Items: $inventory_count</div>";
    
    if ($user_count > 0 && $patient_count > 0 && $inventory_count > 0) {
        echo "<div style='color: green; font-weight: bold; margin-top: 1rem;'>✓ Sample data loaded successfully!</div>";
    } else {
        echo "<div style='color: orange; font-weight: bold; margin-top: 1rem;'>⚠ Some sample data may be missing. Import the schema file.</div>";
    }
    
    echo "<h2>Test Login Credentials:</h2>";
    echo "<div><strong>Admin:</strong> admin / admin123</div>";
    echo "<div><strong>Doctor:</strong> doctor1 / admin123</div>";
    echo "<div><strong>Staff:</strong> staff1 / admin123</div>";
    
    echo "<div style='margin-top: 2rem;'>";
    echo "<a href='index.php' style='background: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Go to Login Page</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red; font-weight: bold;'>✗ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<div style='margin-top: 1rem;'>";
    echo "<strong>Troubleshooting:</strong>";
    echo "<ul>";
    echo "<li>Ensure XAMPP MySQL service is running</li>";
    echo "<li>Check database credentials in config/database.php</li>";
    echo "<li>Import the database schema from database/schema.sql</li>";
    echo "<li>Run setup.php for guided installation</li>";
    echo "</ul>";
    echo "</div>";
}
?>
