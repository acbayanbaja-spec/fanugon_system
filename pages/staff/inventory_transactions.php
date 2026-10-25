<?php
/**
 * MedCare System - Staff: Inventory Transactions History
 */
require_once __DIR__ . '/../../includes/auth.php';
requireRole('staff');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/header.php';

$database = new Database();
$conn = $database->getConnection();

// Handle filters
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build query
$query = "
    SELECT it.*, i.item_name, i.item_type, u.full_name as performed_by_name 
    FROM inventory_transactions it 
    JOIN inventory i ON it.inventory_id = i.inventory_id 
    JOIN users u ON it.performed_by = u.user_id 
    WHERE 1=1
";
$params = [];

if ($search) {
    $query .= " AND (i.item_name LIKE ? OR u.full_name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($type_filter) {
    $query .= " AND it.transaction_type = ?";
    $params[] = $type_filter;
}

if ($date_from) {
    $query .= " AND DATE(it.transaction_date) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $query .= " AND DATE(it.transaction_date) <= ?";
    $params[] = $date_to;
}

$query .= " ORDER BY it.transaction_date DESC LIMIT 50";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$transactions = $stmt->fetchAll();
?>

<div class="card">
    <div class="card-header">
        <h2>Inventory Transactions</h2>
    </div>
    <div class="card-body">
        <div class="form-row">
            <div class="form-group">
                <form method="GET" action="">
                    <input type="text" name="search" placeholder="Search by item or staff..." value="<?php echo htmlspecialchars($search); ?>">
                </form>
            </div>
            
            <div class="form-group">
                <form method="GET" action="">
                    <select name="type" onchange="this.form.submit()">
                        <option value="">All Types</option>
                        <option value="in" <?php echo $type_filter === 'in' ? 'selected' : ''; ?>>Stock In</option>
                        <option value="out" <?php echo $type_filter === 'out' ? 'selected' : ''; ?>>Stock Out</option>
                        <option value="adjustment" <?php echo $type_filter === 'adjustment' ? 'selected' : ''; ?>>Adjustment</option>
                    </select>
                </form>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <form method="GET" action="">
                    <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" placeholder="From date">
                </form>
            </div>
            
            <div class="form-group">
                <form method="GET" action="">
                    <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" placeholder="To date">
                </form>
            </div>
        </div>
        
        <div style="margin-bottom: 1rem;">
            <a href="<?php echo BASE_URL; ?>/pages/staff/inventory.php" class="btn btn-primary">Go to Inventory</a>
            <a href="<?php echo BASE_URL; ?>/pages/staff/stock_alerts.php" class="btn btn-warning">Stock Alerts</a>
        </div>
        
        <?php if (empty($transactions)): ?>
            <div class="empty-state">
                <p>No transactions found.</p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Item</th>
                            <th>Type</th>
                            <th>Quantity</th>
                            <th>Reference</th>
                            <th>Performed By</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td><?php echo date('M d, Y h:i A', strtotime($transaction['transaction_date'])); ?></td>
                                <td><?php echo htmlspecialchars($transaction['item_name'] ?? ''); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $transaction['transaction_type'] === 'in' ? 'success' : ($transaction['transaction_type'] === 'out' ? 'danger' : 'warning'); ?>">
                                        <?php echo ucfirst($transaction['transaction_type'] ?? ''); ?>
                                    </span>
                                </td>
                                <td><?php echo $transaction['quantity']; ?></td>
                                <td><?php echo ucfirst($transaction['reference_type'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($transaction['performed_by_name'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars(substr($transaction['notes'] ?? '', 0, 30)) . '...'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
