<?php
/**
 * MedCare System - Staff: View Inventory Item Details
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

// Get recent transactions
$query = "
    SELECT it.*, u.full_name as performed_by_name 
    FROM inventory_transactions it 
    JOIN users u ON it.performed_by = u.user_id 
    WHERE it.inventory_id = ? 
    ORDER BY it.transaction_date DESC 
    LIMIT 10
";
$stmt = $conn->prepare($query);
$stmt->execute([$inventory_id]);
$transactions = $stmt->fetchAll();

// Calculate stock status
$stock_status = 'normal';
if ($item['quantity'] <= $item['minimum_stock_level']) {
    $stock_status = 'low';
}
if ($item['quantity'] <= 0) {
    $stock_status = 'out';
}

// Calculate expiry status
$expiry_status = 'normal';
if ($item['expiration_date'] && $item['expiration_date'] < date('Y-m-d')) {
    $expiry_status = 'expired';
} elseif ($item['expiration_date'] && $item['expiration_date'] <= date('Y-m-d', strtotime('+' . EXPIRY_WARNING_DAYS . ' days'))) {
    $expiry_status = 'expiring_soon';
}
?>

<div class="card">
    <div class="card-header">
        <h2>Inventory Item Details</h2>
    </div>
    <div class="card-body">
        <div class="btn-group" style="margin-bottom: 1rem;">
            <a href="<?php echo BASE_URL; ?>/pages/staff/inventory.php" class="btn btn-secondary">Back to Inventory</a>
            <a href="<?php echo BASE_URL; ?>/pages/staff/edit_inventory.php?id=<?php echo $inventory_id; ?>" class="btn btn-primary">Edit Item</a>
            <a href="<?php echo BASE_URL; ?>/pages/staff/add_stock.php?id=<?php echo $inventory_id; ?>" class="btn btn-success">Add Stock</a>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3>Item Information</h3>
            </div>
            <div class="card-body">
                <div class="form-row">
                    <div><strong>Item Name:</strong> <?php echo htmlspecialchars($item['item_name'] ?? ''); ?></div>
                    <div><strong>Type:</strong> <?php echo ucfirst($item['item_type'] ?? ''); ?></div>
                </div>
                <div class="form-row">
                    <div><strong>Category:</strong> <?php echo htmlspecialchars($item['category'] ?? 'N/A'); ?></div>
                    <div><strong>Unit:</strong> <?php echo htmlspecialchars($item['unit'] ?? ''); ?></div>
                </div>
                <div><strong>Description:</strong> <?php echo htmlspecialchars($item['description'] ?? 'N/A'); ?></div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3>Stock Information</h3>
            </div>
            <div class="card-body">
                <div class="form-row">
                    <div><strong>Current Quantity:</strong> 
                        <span class="badge badge-<?php echo $stock_status === 'low' ? 'warning' : ($stock_status === 'out' ? 'danger' : 'success'); ?>">
                            <?php echo $item['quantity']; ?> <?php echo htmlspecialchars($item['unit'] ?? ''); ?>
                        </span>
                    </div>
                    <div><strong>Minimum Stock Level:</strong> <?php echo $item['minimum_stock_level']; ?> <?php echo htmlspecialchars($item['unit'] ?? ''); ?></div>
                </div>
                <div class="form-row">
                    <div><strong>Batch Number:</strong> <?php echo htmlspecialchars($item['batch_number'] ?? 'N/A'); ?></div>
                    <div><strong>Expiration Date:</strong> 
                        <?php if ($item['expiration_date']): ?>
                            <span class="badge badge-<?php echo $expiry_status === 'expired' ? 'danger' : ($expiry_status === 'expiring_soon' ? 'warning' : 'success'); ?>">
                                <?php echo date('M d, Y', strtotime($item['expiration_date'])); ?>
                            </span>
                        <?php else: ?>
                            <span class="badge badge-secondary">N/A</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="form-row">
                    <div><strong>Location:</strong> <?php echo htmlspecialchars($item['location'] ?? 'N/A'); ?></div>
                    <div><strong>Manufacturer:</strong> <?php echo htmlspecialchars($item['manufacturer'] ?? 'N/A'); ?></div>
                </div>
                <div><strong>Status:</strong> 
                    <?php if (!$item['is_active']): ?>
                        <span class="badge badge-danger">Inactive</span>
                    <?php elseif ($expiry_status === 'expired'): ?>
                        <span class="badge badge-danger">Expired</span>
                    <?php elseif ($stock_status === 'out'): ?>
                        <span class="badge badge-danger">Out of Stock</span>
                    <?php elseif ($stock_status === 'low'): ?>
                        <span class="badge badge-warning">Low Stock</span>
                    <?php else: ?>
                        <span class="badge badge-success">Normal</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php if (!empty($transactions)): ?>
        <div class="card">
            <div class="card-header">
                <h3>Recent Transactions</h3>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Quantity</th>
                                <th>Reference</th>
                                <th>Performed By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                                <tr>
                                    <td><?php echo date('M d, Y h:i A', strtotime($transaction['transaction_date'])); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $transaction['transaction_type'] === 'in' ? 'success' : ($transaction['transaction_type'] === 'out' ? 'danger' : 'warning'); ?>">
                                            <?php echo ucfirst($transaction['transaction_type'] ?? ''); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $transaction['quantity']; ?></td>
                                    <td><?php echo ucfirst($transaction['reference_type'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['performed_by_name'] ?? ''); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
