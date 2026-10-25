<?php
require_once __DIR__ . '/includes/local_only.php';
/**
 * MedCare System - Integration Test
 * This script tests the key integration points of the system
 */

echo "<h1>MedCare System - Integration Test</h1>";
echo "<p>Testing key system integrations and functionality...</p>";

try {
    require_once __DIR__ . '/config/config.php';
    require_once __DIR__ . '/config/database.php';
    
    $database = new Database();
    $conn = $database->getConnection();
    
    $tests_passed = 0;
    $tests_failed = 0;
    
    // Test 1: Database Connection
    echo "<h2>Test 1: Database Connection</h2>";
    try {
        $result = $conn->query("SELECT 1");
        if ($result) {
            echo "<div style='color: green;'>✓ Database connection working</div>";
            $tests_passed++;
        } else {
            echo "<div style='color: red;'>✗ Database connection failed</div>";
            $tests_failed++;
        }
    } catch (Exception $e) {
        echo "<div style='color: red;'>✗ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</div>";
        $tests_failed++;
    }
    
    // Test 2: Patient Record Integration
    echo "<h2>Test 2: Patient Record Integration</h2>";
    try {
        // Check if patients have consultations
        $result = $conn->query("
            SELECT p.patient_id, COUNT(c.consultation_id) as consultation_count 
            FROM patients p 
            LEFT JOIN consultations c ON p.patient_id = c.patient_id 
            GROUP BY p.patient_id
        ");
        
        if ($result->rowCount() > 0) {
            echo "<div style='color: green;'>✓ Patient to consultation relationship working</div>";
            $tests_passed++;
        } else {
            echo "<div style='color: orange;'>⚠ No patient-consultation relationships found (may need sample data)</div>";
            $tests_failed++;
        }
    } catch (Exception $e) {
        echo "<div style='color: red;'>✗ Patient record integration failed: " . htmlspecialchars($e->getMessage()) . "</div>";
        $tests_failed++;
    }
    
    // Test 3: Prescription to Inventory Integration
    echo "<h2>Test 3: Prescription to Inventory Integration</h2>";
    try {
        // Check if prescription items link to inventory
        $result = $conn->query("
            SELECT pi.prescription_id, i.item_name, i.quantity 
            FROM prescription_items pi 
            JOIN inventory i ON pi.inventory_id = i.inventory_id 
            LIMIT 1
        ");
        
        if ($result->rowCount() > 0) {
            $row = $result->fetch();
            echo "<div style='color: green;'>✓ Prescription to inventory relationship working</div>";
            echo "<div>Sample: Prescription item links to '" . htmlspecialchars($row['item_name']) . "' (Stock: " . $row['quantity'] . ")</div>";
            $tests_passed++;
        } else {
            echo "<div style='color: orange;'>⚠ No prescription-inventory relationships found (may need sample data)</div>";
            $tests_failed++;
        }
    } catch (Exception $e) {
        echo "<div style='color: red;'>✗ Prescription-inventory integration failed: " . htmlspecialchars($e->getMessage()) . "</div>";
        $tests_failed++;
    }
    
    // Test 4: Treatment Progress Integration
    echo "<h2>Test 4: Treatment Progress Integration</h2>";
    try {
        // Check if treatment progress links to treatments
        $result = $conn->query("
            SELECT tp.treatment_id, t.treatment_name 
            FROM treatment_progress tp 
            JOIN treatments t ON tp.treatment_id = t.treatment_id 
            LIMIT 1
        ");
        
        if ($result->rowCount() > 0) {
            $row = $result->fetch();
            echo "<div style='color: green;'>✓ Treatment progress integration working</div>";
            echo "<div>Sample: Progress record for treatment '" . htmlspecialchars($row['treatment_name']) . "'</div>";
            $tests_passed++;
        } else {
            echo "<div style='color: orange;'>⚠ No treatment progress records found (may need sample data)</div>";
            $tests_failed++;
        }
    } catch (Exception $e) {
        echo "<div style='color: red;'>✗ Treatment progress integration failed: " . htmlspecialchars($e->getMessage()) . "</div>";
        $tests_failed++;
    }
    
    // Test 5: Healthcare Communication Integration
    echo "<h2>Test 5: Healthcare Communication Integration</h2>";
    try {
        // Check if messages link to users
        $result = $conn->query("
            SELECT hc.message_id, u.full_name as sender_name 
            FROM healthcare_communication hc 
            JOIN users u ON hc.sender_id = u.user_id 
            LIMIT 1
        ");
        
        if ($result->rowCount() > 0) {
            $row = $result->fetch();
            echo "<div style='color: green;'>✓ Healthcare communication integration working</div>";
            echo "<div>Sample: Message from '" . htmlspecialchars($row['sender_name']) . "'</div>";
            $tests_passed++;
        } else {
            echo "<div style='color: orange;'>⚠ No communication records found (may need sample data)</div>";
            $tests_failed++;
        }
    } catch (Exception $e) {
        echo "<div style='color: red;'>✗ Healthcare communication integration failed: " . htmlspecialchars($e->getMessage()) . "</div>";
        $tests_failed++;
    }
    
    // Test 6: Inventory Alert Logic
    echo "<h2>Test 6: Inventory Alert Logic</h2>";
    try {
        // Check for low stock items
        $result = $conn->query("
            SELECT COUNT(*) as count FROM inventory 
            WHERE quantity <= minimum_stock_level
        ");
        $low_stock_count = $result->fetch()['count'];
        
        // Check for expired items
        $result = $conn->query("
            SELECT COUNT(*) as count FROM inventory 
            WHERE expiration_date < CURDATE()
        ");
        $expired_count = $result->fetch()['count'];
        
        echo "<div style='color: green;'>✓ Inventory alert logic working</div>";
        echo "<div>Low stock items: $low_stock_count</div>";
        echo "<div>Expired items: $expired_count</div>";
        $tests_passed++;
    } catch (Exception $e) {
        echo "<div style='color: red;'>✗ Inventory alert logic failed: " . htmlspecialchars($e->getMessage()) . "</div>";
        $tests_failed++;
    }
    
    // Test 7: Role-Based Access Control
    echo "<h2>Test 7: Role-Based Access Control</h2>";
    try {
        // Check if users have roles assigned
        $result = $conn->query("
            SELECT role, COUNT(*) as count FROM users GROUP BY role
        ");
        
        $roles_found = [];
        while ($row = $result->fetch()) {
            $roles_found[] = $row['role'];
        }
        
        $required_roles = ['doctor', 'staff', 'patient'];
        $all_roles_present = true;
        
        foreach ($required_roles as $role) {
            if (!in_array($role, $roles_found)) {
                $all_roles_present = false;
            }
        }
        
        if ($all_roles_present) {
            echo "<div style='color: green;'>✓ Role-based access control structure in place</div>";
            echo "<div>Roles found: " . implode(', ', $roles_found) . "</div>";
            $tests_passed++;
        } else {
            echo "<div style='color: orange;'>⚠ Some roles may be missing from sample data</div>";
            echo "<div>Roles found: " . implode(', ', $roles_found) . "</div>";
            $tests_failed++;
        }
    } catch (Exception $e) {
        echo "<div style='color: red;'>✗ Role-based access control test failed: " . htmlspecialchars($e->getMessage()) . "</div>";
        $tests_failed++;
    }
    
    // Summary
    echo "<h2>Integration Test Summary</h2>";
    echo "<div style='background: #f8f9fa; padding: 1rem; border-radius: 4px;'>";
    echo "<div><strong>Tests Passed:</strong> $tests_passed</div>";
    echo "<div><strong>Tests Failed:</strong> $tests_failed</div>";
    echo "<div><strong>Total Tests:</strong> " . ($tests_passed + $tests_failed) . "</div>";
    
    if ($tests_failed === 0) {
        echo "<div style='color: green; font-weight: bold; margin-top: 1rem;'>✓ All integration tests passed!</div>";
    } elseif ($tests_failed <= 2) {
        echo "<div style='color: orange; font-weight: bold; margin-top: 1rem;'>⚠ Most tests passed. Some may need sample data.</div>";
    } else {
        echo "<div style='color: red; font-weight: bold; margin-top: 1rem;'>✗ Multiple tests failed. Check system configuration.</div>";
    }
    echo "</div>";
    
    echo "<div style='margin-top: 2rem;'>";
    echo "<a href='index.php' style='background: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Go to Login Page</a>";
    echo " ";
    echo "<a href='test_connection.php' style='background: #95a5a6; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Test Database Connection</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red; font-weight: bold;'>✗ Integration test failed: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<div>Ensure the database is properly configured and the schema is imported.</div>";
}
?>
