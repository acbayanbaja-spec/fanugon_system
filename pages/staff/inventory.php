<?php
/**
 * MedCare System - Staff: Inventory Management
 */
require_once __DIR__ . '/../../includes/auth.php';
requireRole('staff');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/header.php';

$database = new Database();
$conn = $database->getConnection();

// Handle search and filters
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Build query
$query = "SELECT * FROM inventory WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (item_name LIKE ? OR category LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($type_filter) {
    $query .= " AND item_type = ?";
    $params[] = $type_filter;
}

if ($status_filter === 'low_stock') {
    $query .= " AND quantity <= minimum_stock_level";
} elseif ($status_filter === 'expired') {
    $query .= " AND expiration_date < CURDATE()";
} elseif ($status_filter === 'expiring_soon') {
    $query .= " AND expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL " . EXPIRY_WARNING_DAYS . " DAY)";
}

$query .= " ORDER BY item_name";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$inventory = $stmt->fetchAll();
?>

<div class="card">
    <div class="card-header">
        <h2>Inventory Management</h2>
    </div>
    <div class="card-body">
        <div class="form-row">
            <div class="form-group">
                <form method="GET" action="">
                    <input type="text" name="search" placeholder="Search items..." value="<?php echo htmlspecialchars($search); ?>">
                </form>
            </div>
            
            <div class="form-group">
                <form method="GET" action="">
                    <select name="type" onchange="this.form.submit()">
                        <option value="">All Types</option>
                        <option value="medication" <?php echo $type_filter === 'medication' ? 'selected' : ''; ?>>Medication</option>
                        <option value="medical_supply" <?php echo $type_filter === 'medical_supply' ? 'selected' : ''; ?>>Medical Supply</option>
                    </select>
                </form>
            </div>
            
            <div class="form-group">
                <form method="GET" action="">
                    <select name="status" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="low_stock" <?php echo $status_filter === 'low_stock' ? 'selected' : ''; ?>>Low Stock</option>
                        <option value="expired" <?php echo $status_filter === 'expired' ? 'selected' : ''; ?>>Expired</option>
                        <option value="expiring_soon" <?php echo $status_filter === 'expiring_soon' ? 'selected' : ''; ?>>Expiring Soon</option>
                    </select>
                </form>
            </div>
        </div>
        
        <div style="margin-bottom: 1rem;">
            <a href="<?php echo BASE_URL; ?>/pages/staff/add_inventory.php" class="btn btn-primary">Add New Item</a>
            <a href="<?php echo BASE_URL; ?>/pages/staff/stock_alerts.php" class="btn btn-warning">Stock Alerts</a>
            <a href="<?php echo BASE_URL; ?>/pages/staff/inventory_transactions.php" class="btn btn-info">Transactions</a>
        </div>
        
        <?php if (empty($inventory)): ?>
            <div class="empty-state">
                <p>No inventory items found.</p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Type</th>
                            <th>Category</th>
                            <th>Quantity</th>
                            <th>Unit</th>
                            <th>Expiration</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inventory as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['item_name'] ?? ''); ?></td>
                                <td><?php echo ucfirst($item['item_type'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($item['category'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php 
                                    $stock_status = 'normal';
                                    if ($item['quantity'] <= $item['minimum_stock_level']) {
                                        $stock_status = 'low';
                                    }
                                    if ($item['quantity'] <= 0) {
                                        $stock_status = 'out';
                                    }
                                    ?>
                                    <span class="badge badge-<?php echo $stock_status === 'low' ? 'warning' : ($stock_status === 'out' ? 'danger' : 'success'); ?>">
                                        <?php echo $item['quantity']; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($item['unit'] ?? ''); ?></td>
                                <td>
                                    <?php 
                                    $expiry_status = 'normal';
                                    if ($item['expiration_date'] < date('Y-m-d')) {
                                        $expiry_status = 'expired';
                                    } elseif ($item['expiration_date'] <= date('Y-m-d', strtotime('+' . EXPIRY_WARNING_DAYS . ' days'))) {
                                        $expiry_status = 'expiring_soon';
                                    }
                                    ?>
                                    <?php if ($item['expiration_date']): ?>
                                        <span class="badge badge-<?php echo $expiry_status === 'expired' ? 'danger' : ($expiry_status === 'expiring_soon' ? 'warning' : 'success'); ?>">
                                            <?php echo date('M d, Y', strtotime($item['expiration_date'])); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
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
                                </td>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>/pages/staff/view_inventory.php?id=<?php echo $item['inventory_id']; ?>" class="action-link">View</a>
                                    <a href="<?php echo BASE_URL; ?>/pages/staff/edit_inventory.php?id=<?php echo $item['inventory_id']; ?>" class="action-link edit">Edit</a>
                                    <a href="<?php echo BASE_URL; ?>/pages/staff/add_stock.php?id=<?php echo $item['inventory_id']; ?>" class="action-link">Add Stock</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
