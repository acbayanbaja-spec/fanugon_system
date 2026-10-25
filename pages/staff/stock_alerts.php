<?php
/**
 * MedCare System - Staff: Stock Alerts
 */
require_once __DIR__ . '/../../includes/auth.php';
requireRole('staff');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/header.php';

$database = new Database();
$conn = $database->getConnection();

// Get low stock items
$query = "
    SELECT * FROM inventory 
    WHERE quantity <= minimum_stock_level AND is_active = 1
    ORDER BY quantity ASC
";
$stmt = $conn->query($query);
$low_stock_items = $stmt->fetchAll();

// Get expired items
$query = "
    SELECT * FROM inventory 
    WHERE expiration_date < CURDATE() AND is_active = 1
    ORDER BY expiration_date DESC
";
$stmt = $conn->query($query);
$expired_items = $stmt->fetchAll();

// Get items expiring soon
$query = "
    SELECT * FROM inventory 
    WHERE expiration_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL " . EXPIRY_WARNING_DAYS . " DAY) 
    AND is_active = 1
    ORDER BY expiration_date ASC
";
$stmt = $conn->query($query);
$expiring_soon_items = $stmt->fetchAll();
?>

<div class="card">
    <div class="card-header">
        <h2>Stock Alerts</h2>
    </div>
    <div class="card-body">
        <div class="btn-group" style="margin-bottom: 1rem;">
            <a href="<?php echo BASE_URL; ?>/pages/staff/dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            <a href="<?php echo BASE_URL; ?>/pages/staff/inventory.php" class="btn btn-primary">Go to Inventory</a>
        </div>
        
        <?php if (!empty($low_stock_items)): ?>
        <div class="card">
            <div class="card-header">
                <h3>Low Stock Alerts</h3>
                <span class="badge badge-danger"><?php echo count($low_stock_items); ?> items</span>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <strong>Attention:</strong> The following items have reached or fallen below their minimum stock level.
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Item Name</th>
                                <th>Type</th>
                                <th>Current Stock</th>
                                <th>Minimum Level</th>
                                <th>Unit</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($low_stock_items as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['item_name'] ?? ''); ?></td>
                                    <td><?php echo ucfirst($item['item_type'] ?? ''); ?></td>
                                    <td>
                                        <span class="badge badge-danger"><?php echo $item['quantity']; ?></span>
                                    </td>
                                    <td><?php echo $item['minimum_stock_level']; ?></td>
                                    <td><?php echo htmlspecialchars($item['unit'] ?? ''); ?></td>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>/pages/staff/add_stock.php?id=<?php echo $item['inventory_id']; ?>" class="btn btn-success btn-sm">Add Stock</a>
                                        <a href="<?php echo BASE_URL; ?>/pages/staff/view_inventory.php?id=<?php echo $item['inventory_id']; ?>" class="btn btn-secondary btn-sm">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($expired_items)): ?>
        <div class="card">
            <div class="card-header">
                <h3>Expired Items</h3>
                <span class="badge badge-danger"><?php echo count($expired_items); ?> items</span>
            </div>
            <div class="card-body">
                <div class="alert alert-danger">
                    <strong>Urgent:</strong> The following items have expired and should not be used or dispensed.
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Item Name</th>
                                <th>Type</th>
                                <th>Expiration Date</th>
                                <th>Batch Number</th>
                                <th>Current Stock</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($expired_items as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['item_name'] ?? ''); ?></td>
                                    <td><?php echo ucfirst($item['item_type'] ?? ''); ?></td>
                                    <td>
                                        <span class="badge badge-danger"><?php echo date('M d, Y', strtotime($item['expiration_date'])); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['batch_number'] ?? 'N/A'); ?></td>
                                    <td><?php echo $item['quantity']; ?> <?php echo htmlspecialchars($item['unit'] ?? ''); ?></td>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>/pages/staff/edit_inventory.php?id=<?php echo $item['inventory_id']; ?>" class="btn btn-warning btn-sm">Manage</a>
                                        <a href="<?php echo BASE_URL; ?>/pages/staff/view_inventory.php?id=<?php echo $item['inventory_id']; ?>" class="btn btn-secondary btn-sm">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($expiring_soon_items)): ?>
        <div class="card">
            <div class="card-header">
                <h3>Items Expiring Soon</h3>
                <span class="badge badge-warning"><?php echo count($expiring_soon_items); ?> items</span>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <strong>Notice:</strong> The following items will expire within the next <?php echo EXPIRY_WARNING_DAYS; ?> days.
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Item Name</th>
                                <th>Type</th>
                                <th>Expiration Date</th>
                                <th>Days Until Expiry</th>
                                <th>Current Stock</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($expiring_soon_items as $item): ?>
                                <?php 
                                $days_until_expiry = floor((strtotime($item['expiration_date']) - time()) / (60 * 60 * 24));
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['item_name'] ?? ''); ?></td>
                                    <td><?php echo ucfirst($item['item_type'] ?? ''); ?></td>
                                    <td>
                                        <span class="badge badge-warning"><?php echo date('M d, Y', strtotime($item['expiration_date'])); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $days_until_expiry <= 7 ? 'danger' : 'warning'; ?>">
                                            <?php echo $days_until_expiry; ?> days
                                        </span>
                                    </td>
                                    <td><?php echo $item['quantity']; ?> <?php echo htmlspecialchars($item['unit'] ?? ''); ?></td>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>/pages/staff/view_inventory.php?id=<?php echo $item['inventory_id']; ?>" class="btn btn-secondary btn-sm">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (empty($low_stock_items) && empty($expired_items) && empty($expiring_soon_items)): ?>
        <div class="card">
            <div class="card-body">
                <div class="alert alert-success">
                    <strong>Good news!</strong> No stock alerts at this time. All inventory levels are within normal parameters.
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
