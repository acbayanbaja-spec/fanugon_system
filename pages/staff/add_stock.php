<?php
/**
 * MedCare System - Staff: Add Stock to Inventory Item
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
    $transaction_type = $_POST['transaction_type'] ?? '';
    $quantity = (int)($_POST['quantity'] ?? 0);
    $reference_type = $_POST['reference_type'] ?? '';
    $notes = sanitizeInput($_POST['notes'] ?? '');
    
    // Optional ENUM: store NULL instead of '' (MySQL strict mode rejects '')
    $valid_reference_types = ['purchase', 'dispensing', 'prescription', 'adjustment', 'return', 'expiry'];
    $reference_type = in_array($reference_type, $valid_reference_types, true) ? $reference_type : null;
    
    if (!in_array($transaction_type, ['in', 'out', 'adjustment'], true)) {
        $error = 'Please select a valid transaction type.';
    } elseif ($quantity < 0 || ($quantity === 0 && $transaction_type !== 'adjustment')) {
        $error = 'Please enter a quantity greater than zero (an adjustment may set the stock to 0).';
    } else {
        try {
            $conn->beginTransaction();
            
            // Re-read the stock inside the transaction and lock the row so two
            // staff members cannot update the same item at the same time
            $stmt = $conn->prepare("SELECT quantity FROM inventory WHERE inventory_id = ? FOR UPDATE");
            $stmt->execute([$inventory_id]);
            $current_quantity = (int)$stmt->fetchColumn();
            
            // Calculate new quantity
            if ($transaction_type === 'in') {
                $new_quantity = $current_quantity + $quantity;
                $logged_quantity = $quantity;
            } elseif ($transaction_type === 'out') {
                if ($current_quantity < $quantity) {
                    throw new Exception('Insufficient stock for this transaction. Only ' . $current_quantity . ' ' . $item['unit'] . ' available.');
                }
                $new_quantity = $current_quantity - $quantity;
                $logged_quantity = $quantity;
            } else {
                // Adjustment: the entered value is the new total; log the size of the change
                $new_quantity = $quantity;
                $logged_quantity = abs($new_quantity - $current_quantity);
                $notes = trim('Adjusted from ' . $current_quantity . ' to ' . $new_quantity . '. ' . $notes);
            }
            
            // Update inventory quantity
            $query = "UPDATE inventory SET quantity = ?, updated_at = CURRENT_TIMESTAMP WHERE inventory_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$new_quantity, $inventory_id]);
            
            // Record transaction
            $query = "
                INSERT INTO inventory_transactions (inventory_id, transaction_type, quantity, reference_type, notes, performed_by)
                VALUES (?, ?, ?, ?, ?, ?)
            ";
            $stmt = $conn->prepare($query);
            $stmt->execute([$inventory_id, $transaction_type, $logged_quantity, $reference_type, $notes, $_SESSION['user_id']]);
            
            $conn->commit();
            $success = 'Stock transaction recorded successfully!';
            $_POST = [];
            
            // Refresh item data
            $query = "SELECT * FROM inventory WHERE inventory_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$inventory_id]);
            $item = $stmt->fetch();
        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $error = ($e instanceof PDOException) ? 'Error recording the stock transaction. Please try again.' : $e->getMessage();
            error_log("Stock transaction error: " . $e->getMessage());
        }
    }
}
?>

<div class="card">
    <div class="card-header">
        <h2>Stock Transaction</h2>
    </div>
    <div class="card-body">
        <div class="btn-group" style="margin-bottom: 1rem;">
            <a href="<?php echo BASE_URL; ?>/pages/staff/view_inventory.php?id=<?php echo $inventory_id; ?>" class="btn btn-secondary">Back to Item</a>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3>Item Information</h3>
            </div>
            <div class="card-body">
                <p><strong>Item Name:</strong> <?php echo htmlspecialchars($item['item_name'] ?? ''); ?></p>
                <p><strong>Type:</strong> <?php echo ucfirst($item['item_type'] ?? ''); ?></p>
                <p><strong>Current Stock:</strong> <?php echo $item['quantity']; ?> <?php echo htmlspecialchars($item['unit'] ?? ''); ?></p>
                <p><strong>Minimum Stock Level:</strong> <?php echo $item['minimum_stock_level']; ?> <?php echo htmlspecialchars($item['unit'] ?? ''); ?></p>
            </div>
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
                <h3>Record Stock Transaction</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="transaction_type">Transaction Type *</label>
                        <select id="transaction_type" name="transaction_type" required onchange="updateReferenceOptions()">
                            <option value="">Select Type</option>
                            <option value="in" <?php echo (($_POST['transaction_type'] ?? '') === 'in' ? 'selected' : ''); ?>>Stock In (Add)</option>
                            <option value="out" <?php echo (($_POST['transaction_type'] ?? '') === 'out' ? 'selected' : ''); ?>>Stock Out (Remove)</option>
                            <option value="adjustment" <?php echo (($_POST['transaction_type'] ?? '') === 'adjustment' ? 'selected' : ''); ?>>Adjustment (Set New Quantity)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="quantity">Quantity *</label>
                        <input type="number" id="quantity" name="quantity" min="0" required value="<?php echo htmlspecialchars($_POST['quantity'] ?? ''); ?>">
                        <small id="quantity_help">For Stock In: Quantity to add. For Stock Out: Quantity to remove. For Adjustment: New total quantity.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="reference_type">Reference Type</label>
                        <select id="reference_type" name="reference_type">
                            <option value="">Select Reference</option>
                            <option value="purchase" <?php echo (($_POST['reference_type'] ?? '') === 'purchase' ? 'selected' : ''); ?>>Purchase</option>
                            <option value="dispensing" <?php echo (($_POST['reference_type'] ?? '') === 'dispensing' ? 'selected' : ''); ?>>Dispensing</option>
                            <option value="prescription" <?php echo (($_POST['reference_type'] ?? '') === 'prescription' ? 'selected' : ''); ?>>Prescription</option>
                            <option value="adjustment" <?php echo (($_POST['reference_type'] ?? '') === 'adjustment' ? 'selected' : ''); ?>>Adjustment</option>
                            <option value="return" <?php echo (($_POST['reference_type'] ?? '') === 'return' ? 'selected' : ''); ?>>Return</option>
                            <option value="expiry" <?php echo (($_POST['reference_type'] ?? '') === 'expiry' ? 'selected' : ''); ?>>Expiry</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes"><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">Record Transaction</button>
                        <a href="<?php echo BASE_URL; ?>/pages/staff/view_inventory.php?id=<?php echo $inventory_id; ?>" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function updateReferenceOptions() {
    const transactionType = document.getElementById('transaction_type').value;
    const quantityHelp = document.getElementById('quantity_help');
    
    if (transactionType === 'in') {
        quantityHelp.textContent = 'For Stock In: Quantity to add to current stock.';
    } else if (transactionType === 'out') {
        quantityHelp.textContent = 'For Stock Out: Quantity to remove from current stock.';
    } else if (transactionType === 'adjustment') {
        quantityHelp.textContent = 'For Adjustment: New total quantity to set.';
    } else {
        quantityHelp.textContent = 'For Stock In: Quantity to add. For Stock Out: Quantity to remove. For Adjustment: New total quantity.';
    }
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
