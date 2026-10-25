<?php
/**
 * MedCare System - Patient: My Prescriptions
 */
require_once __DIR__ . '/../../includes/auth.php';
requireRole('patient');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/header.php';

$database = new Database();
$conn = $database->getConnection();

// Get patient ID from user session
$user_id = $_SESSION['user_id'];
$query = "SELECT patient_id FROM patients WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$user_id]);
$patient = $stmt->fetch();

if (!$patient) {
    echo '<div class="alert alert-danger">Patient record not found. Please contact staff.</div>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit();
}

$patient_id = $patient['patient_id'];

// Get patient prescriptions
$query = "
    SELECT p.*, u.full_name as doctor_name 
    FROM prescriptions p 
    JOIN users u ON p.doctor_id = u.user_id 
    WHERE p.patient_id = ? 
    ORDER BY p.prescription_date DESC
";
$stmt = $conn->prepare($query);
$stmt->execute([$patient_id]);
$prescriptions = $stmt->fetchAll();
?>

<div class="card">
    <div class="card-header">
        <h2>My Prescriptions</h2>
    </div>
    <div class="card-body">
        <div class="btn-group" style="margin-bottom: 1rem;">
            <a href="<?php echo BASE_URL; ?>/pages/patient/dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
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
                            <th>Doctor</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($prescriptions as $prescription): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($prescription['prescription_date'])); ?></td>
                                <td><?php echo htmlspecialchars($prescription['doctor_name'] ?? ''); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $prescription['status'] === 'dispensed' ? 'success' : ($prescription['status'] === 'cancelled' ? 'danger' : 'warning'); ?>">
                                        <?php echo ucfirst($prescription['status'] ?? ''); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>/pages/patient/view_prescription.php?id=<?php echo $prescription['prescription_id']; ?>" class="action-link">View</a>
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
