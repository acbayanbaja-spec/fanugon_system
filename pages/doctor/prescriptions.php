<?php
/**
 * MedCare System - Doctor: Prescriptions List
 */
require_once __DIR__ . '/../../includes/auth.php';
requireRole('doctor');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/header.php';

$database = new Database();
$conn = $database->getConnection();

// Handle search
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Build query
$query = "
    SELECT p.*, patient.first_name, patient.last_name, u.full_name as doctor_name 
    FROM prescriptions p 
    JOIN patients patient ON p.patient_id = patient.patient_id 
    JOIN users u ON p.doctor_id = u.user_id 
    WHERE 1=1
";

$params = [];

if ($search) {
    $query .= " AND (patient.first_name LIKE ? OR patient.last_name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($status_filter) {
    $query .= " AND p.status = ?";
    $params[] = $status_filter;
}

$query .= " ORDER BY p.prescription_date DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$prescriptions = $stmt->fetchAll();
?>

<div class="card">
    <div class="card-header">
        <h2>Prescriptions</h2>
    </div>
    <div class="card-body">
        <div class="form-row">
            <div class="form-group">
                <form method="GET" action="">
                    <input type="text" name="search" placeholder="Search by patient name..." value="<?php echo htmlspecialchars($search); ?>">
                </form>
            </div>
            
            <div class="form-group">
                <form method="GET" action="">
                    <select name="status" onchange="this.form.submit()">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="dispensed" <?php echo $status_filter === 'dispensed' ? 'selected' : ''; ?>>Dispensed</option>
                        <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </form>
            </div>
        </div>
        
        <div style="margin-bottom: 1rem;">
            <a href="<?php echo BASE_URL; ?>/pages/doctor/patients.php" class="btn btn-primary">Create Prescription for Patient</a>
        </div>
        
        <?php if (empty($prescriptions)): ?>
            <div class="empty-state">
                <p>No prescriptions found.</p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($prescriptions as $prescription): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($prescription['prescription_date'])); ?></td>
                                <td><?php echo htmlspecialchars($prescription['first_name'] . ' ' . $prescription['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($prescription['doctor_name'] ?? ''); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $prescription['status'] === 'dispensed' ? 'success' : ($prescription['status'] === 'cancelled' ? 'danger' : 'warning'); ?>">
                                        <?php echo ucfirst($prescription['status'] ?? ''); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>/pages/doctor/view_prescription.php?id=<?php echo $prescription['prescription_id']; ?>" class="action-link">View</a>
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
