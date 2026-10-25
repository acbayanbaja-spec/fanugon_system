<?php
/**
 * MedCare System - Staff: Edit Inventory Item
 */
require_once __DIR__ . '/../../includes/auth.php';
requireRole('staff');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/header.php';

$database = new Database();
$conn = $database->getConnection();

$inventory_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$inventory_id) {
    echo '<div class="alert alert-danger">Invalid inventory ID.</div>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit();
}

// Get inventory item
$query = "SELECT * FROM inventory WHERE inventory_id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$inventory_id]);
$item = $stmt->fetch();

if (!$item) {
    echo '<div class="alert alert-danger">Inventory item not found.</div>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_name = sanitizeInput($_POST['item_name'] ?? '');
    $item_type = $_POST['item_type'] ?? '';
    $category = sanitizeInput($_POST['category'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $minimum_stock_level = (int)($_POST['minimum_stock_level'] ?? DEFAULT_MIN_STOCK_LEVEL);
    $location = sanitizeInput($_POST['location'] ?? '');
    $manufacturer = sanitizeInput($_POST['manufacturer'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $batch_number = sanitizeInput($_POST['batch_number'] ?? '');
    $expiration_date = trim($_POST['expiration_date'] ?? '');
    
    if (empty($item_name) || !in_array($item_type, ['medication', 'medical_supply'], true)) {
        $error = 'Item name and type are required.';
    } elseif ($expiration_date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiration_date)) {
        $error = 'Please enter a valid expiration date.';
    } elseif ($minimum_stock_level < 0) {
        $error = 'Minimum stock level cannot be negative.';
    } else {
        try {
            $query = "
                UPDATE inventory 
                SET item_name = ?, item_type = ?, category = ?, description = ?, 
                    minimum_stock_level = ?, location = ?, manufacturer = ?, is_active = ?,
                    batch_number = ?, expiration_date = ?, updated_at = CURRENT_TIMESTAMP
                WHERE inventory_id = ?
            ";
            $stmt = $conn->prepare($query);
            $stmt->execute([
                $item_name, $item_type, $category, $description, 
                $minimum_stock_level, $location, $manufacturer, $is_active,
                $batch_number ?: null, $expiration_date ?: null, $inventory_id
            ]);
            
            $success = 'Inventory item updated successfully!';
            
            // Refresh item data
            $query = "SELECT * FROM inventory WHERE inventory_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$inventory_id]);
            $item = $stmt->fetch();
        } catch (PDOException $e) {
            $error = 'Error updating inventory item. Please try again.';
            error_log("Inventory update error: " . $e->getMessage());
        }
    }
}
?>

<div class="card">
    <div class="card-header">
        <h2>Edit Inventory Item</h2>
    </div>
    <div class="card-body">
        <div class="btn-group" style="margin-bottom: 1rem;">
            <a href="<?php echo BASE_URL; ?>/pages/staff/view_inventory.php?id=<?php echo $inventory_id; ?>" class="btn btn-secondary">Back to Item</a>
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
                            <input type="text" id="item_name" name="item_name" required value="<?php echo htmlspecialchars($item['item_name'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="item_type">Item Type *</label>
                            <select id="item_type" name="item_type" required>
                                <option value="">Select Type</option>
                                <option value="medication" <?php echo $item['item_type'] === 'medication' ? 'selected' : ''; ?>>Medication</option>
                                <option value="medical_supply" <?php echo $item['item_type'] === 'medical_supply' ? 'selected' : ''; ?>>Medical Supply</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="category">Category</label>
                            <input type="text" id="category" name="category" value="<?php echo htmlspecialchars($item['category'] ?? ''); ?>" placeholder="e.g., Analgesic, Antibiotic, PPE">
                        </div>
                        
                        <div class="form-group">
                            <label for="unit">Unit *</label>
                            <input type="text" id="unit" name="unit" required value="<?php echo htmlspecialchars($item['unit'] ?? ''); ?>" readonly>
                            <small>Unit cannot be changed. Use stock transactions to adjust quantity.</small>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="batch_number">Batch Number</label>
                            <input type="text" id="batch_number" name="batch_number" value="<?php echo htmlspecialchars($item['batch_number'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="expiration_date">Expiration Date</label>
                            <input type="date" id="expiration_date" name="expiration_date" value="<?php echo htmlspecialchars($item['expiration_date'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description"><?php echo htmlspecialchars($item['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="minimum_stock_level">Minimum Stock Level</label>
                            <input type="number" id="minimum_stock_level" name="minimum_stock_level" min="0" value="<?php echo $item['minimum_stock_level']; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="is_active">Active Status</label>
                            <div style="margin-top: 0.5rem;">
                                <input type="checkbox" id="is_active" name="is_active" <?php echo $item['is_active'] ? 'checked' : ''; ?>>
                                <label for="is_active" style="display: inline;">Item is active</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="location">Storage Location</label>
                            <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($item['location'] ?? ''); ?>" placeholder="e.g., Shelf A1, Cabinet B2">
                        </div>
                        
                        <div class="form-group">
                            <label for="manufacturer">Manufacturer</label>
                            <input type="text" id="manufacturer" name="manufacturer" value="<?php echo htmlspecialchars($item['manufacturer'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <strong>Note:</strong> To adjust stock quantity, use the "Add Stock" or stock adjustment features. Do not modify stock directly here.
                    </div>
                    
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">Update Item</button>
                        <a href="<?php echo BASE_URL; ?>/pages/staff/view_inventory.php?id=<?php echo $inventory_id; ?>" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
