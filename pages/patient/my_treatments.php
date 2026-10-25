<?php
/**
 * MedCare System - Patient: My Treatments
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

// Get patient treatments
$query = "
    SELECT t.*, d.diagnosis_name, u.full_name as doctor_name 
    FROM treatments t 
    LEFT JOIN diagnoses d ON t.diagnosis_id = d.diagnosis_id 
    LEFT JOIN users u ON t.prescribed_by = u.user_id 
    WHERE t.patient_id = ? 
    ORDER BY t.start_date DESC
";
$stmt = $conn->prepare($query);
$stmt->execute([$patient_id]);
$treatments = $stmt->fetchAll();
?>

<div class="card">
    <div class="card-header">
        <h2>My Treatments</h2>
    </div>
    <div class="card-body">
        <div class="btn-group" style="margin-bottom: 1rem;">
            <a href="<?php echo BASE_URL; ?>/pages/patient/dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
        
        <?php if (empty($treatments)): ?>
            <div class="empty-state">
                <p>No treatments found.</p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Treatment</th>
                            <th>Diagnosis</th>
                            <th>Prescribed By</th>
                            <th>Status</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($treatments as $treatment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($treatment['treatment_name'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($treatment['diagnosis_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($treatment['doctor_name'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $treatment['status'] === 'completed' ? 'success' : ($treatment['status'] === 'ongoing' ? 'info' : 'warning'); ?>">
                                        <?php echo ucfirst($treatment['status'] ?? ''); ?>
                                    </span>
                                </td>
                                <td><?php echo $treatment['start_date'] ? date('M d, Y', strtotime($treatment['start_date'])) : 'N/A'; ?></td>
                                <td><?php echo $treatment['end_date'] ? date('M d, Y', strtotime($treatment['end_date'])) : 'N/A'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
