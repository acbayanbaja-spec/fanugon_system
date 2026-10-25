<?php
/**
 * MedCare System - Staff: Add Inventory Item
 */
require_once __DIR__ . '/../../includes/auth.php';
requireRole('staff');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/header.php';

$database = new Database();
$conn = $database->getConnection();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_name = sanitizeInput($_POST['item_name'] ?? '');
    $item_type = $_POST['item_type'] ?? '';
    $category = sanitizeInput($_POST['category'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $quantity = (int)($_POST['quantity'] ?? 0);
    $unit = sanitizeInput($_POST['unit'] ?? '');
    $batch_number = sanitizeInput($_POST['batch_number'] ?? '');
    $expiration_date = $_POST['expiration_date'] ?? '';
    $minimum_stock_level = (int)($_POST['minimum_stock_level'] ?? DEFAULT_MIN_STOCK_LEVEL);
    $location = sanitizeInput($_POST['location'] ?? '');
    $manufacturer = sanitizeInput($_POST['manufacturer'] ?? '');
    
    if (empty($item_name) || !in_array($item_type, ['medication', 'medical_supply'], true) || empty($unit)) {
        $error = 'Item name, type, and unit are required.';
    } elseif ($quantity < 0 || $minimum_stock_level < 0) {
        $error = 'Quantity and minimum stock level cannot be negative.';
    } elseif ($expiration_date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiration_date)) {
        $error = 'Please enter a valid expiration date.';
    } else {
        try {
            $conn->beginTransaction();
            $query = "
                INSERT INTO inventory (item_name, item_type, category, description, quantity, unit, batch_number, 
                    expiration_date, minimum_stock_level, location, manufacturer)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
            $stmt = $conn->prepare($query);
            $stmt->execute([
                $item_name, $item_type, $category, $description, $quantity, $unit, $batch_number,
                $expiration_date ?: null, $minimum_stock_level, $location, $manufacturer
            ]);
            
            // Record inventory transaction
            $inventory_id = $conn->lastInsertId();
            $query = "
                INSERT INTO inventory_transactions (inventory_id, transaction_type, quantity, reference_type, notes, performed_by)
                VALUES (?, 'in', ?, 'purchase', 'Initial stock entry', ?)
            ";
            $stmt = $conn->prepare($query);
            $stmt->execute([$inventory_id, $quantity, $_SESSION['user_id']]);
            $conn->commit();
            
            $success = 'Inventory item added successfully!';
            $_POST = array();
        } catch (PDOException $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $error = 'Error adding inventory item. Please try again.';
            error_log("Inventory add error: " . $e->getMessage());
        }
    }
}
?>

<div class="card">
    <div class="card-header">
        <h2>Add Inventory Item</h2>
    </div>
    <div class="card-body">
        <div class="btn-group" style="margin-bottom: 1rem;">
            <a href="<?php echo BASE_URL; ?>/pages/staff/inventory.php" class="btn btn-secondary">Back to Inventory</a>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h3>Item Information</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="item_name">Item Name *</label>
                            <input type="text" id="item_name" name="item_name" required value="<?php echo htmlspecialchars($_POST['item_name'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="item_type">Item Type *</label>
                            <select id="item_type" name="item_type" required>
                                <option value="">Select Type</option>
                                <option value="medication" <?php echo (($_POST['item_type'] ?? '') === 'medication' ? 'selected' : ''); ?>>Medication</option>
                                <option value="medical_supply" <?php echo (($_POST['item_type'] ?? '') === 'medical_supply' ? 'selected' : ''); ?>>Medical Supply</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="category">Category</label>
                            <input type="text" id="category" name="category" value="<?php echo htmlspecialchars($_POST['category'] ?? ''); ?>" placeholder="e.g., Analgesic, Antibiotic, PPE">
                        </div>
                        
                        <div class="form-group">
                            <label for="unit">Unit *</label>
                            <input type="text" id="unit" name="unit" required value="<?php echo htmlspecialchars($_POST['unit'] ?? ''); ?>" placeholder="e.g., tablets, bottles, pieces">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="card-header" style="margin-top: 2rem;">
                        <h3>Stock Information</h3>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="quantity">Initial Quantity *</label>
                            <input type="number" id="quantity" name="quantity" min="0" required value="<?php echo htmlspecialchars($_POST['quantity'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="minimum_stock_level">Minimum Stock Level</label>
                            <input type="number" id="minimum_stock_level" name="minimum_stock_level" min="0" value="<?php echo htmlspecialchars($_POST['minimum_stock_level'] ?? DEFAULT_MIN_STOCK_LEVEL); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="batch_number">Batch Number</label>
                            <input type="text" id="batch_number" name="batch_number" value="<?php echo htmlspecialchars($_POST['batch_number'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="expiration_date">Expiration Date</label>
                            <input type="date" id="expiration_date" name="expiration_date" value="<?php echo htmlspecialchars($_POST['expiration_date'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="card-header" style="margin-top: 2rem;">
                        <h3>Additional Information</h3>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="location">Storage Location</label>
                            <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>" placeholder="e.g., Shelf A1, Cabinet B2">
                        </div>
                        
                        <div class="form-group">
                            <label for="manufacturer">Manufacturer</label>
                            <input type="text" id="manufacturer" name="manufacturer" value="<?php echo htmlspecialchars($_POST['manufacturer'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">Add Item</button>
                        <a href="<?php echo BASE_URL; ?>/pages/staff/inventory.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
