<?php
/**
 * MedCare System - Patient: View Prescription Details
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

$prescription_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$prescription_id) {
    echo '<div class="alert alert-danger">Invalid prescription ID.</div>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit();
}

// Get prescription information (verify it belongs to this patient)
$query = "
    SELECT p.*, patient.first_name, patient.last_name, u.full_name as doctor_name 
    FROM prescriptions p 
    JOIN patients patient ON p.patient_id = patient.patient_id 
    JOIN users u ON p.doctor_id = u.user_id 
    WHERE p.prescription_id = ? AND p.patient_id = ?
";
$stmt = $conn->prepare($query);
$stmt->execute([$prescription_id, $patient_id]);
$prescription = $stmt->fetch();

if (!$prescription) {
    echo '<div class="alert alert-danger">Prescription not found.</div>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit();
}

// Get prescription items
$query = "
    SELECT pi.*, i.unit 
    FROM prescription_items pi 
    JOIN inventory i ON pi.inventory_id = i.inventory_id 
    WHERE pi.prescription_id = ?
";
$stmt = $conn->prepare($query);
$stmt->execute([$prescription_id]);
$prescription_items = $stmt->fetchAll();
?>

<div class="card">
    <div class="card-header">
        <h2>Prescription Details</h2>
    </div>
    <div class="card-body">
        <div class="btn-group" style="margin-bottom: 1rem;">
            <a href="<?php echo BASE_URL; ?>/pages/patient/prescriptions.php" class="btn btn-secondary">Back to Prescriptions</a>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3>Prescription Information</h3>
            </div>
            <div class="card-body">
                <div class="form-row">
                    <div><strong>Doctor:</strong> <?php echo htmlspecialchars($prescription['doctor_name'] ?? ''); ?></div>
                    <div><strong>Date:</strong> <?php echo date('M d, Y', strtotime($prescription['prescription_date'])); ?></div>
                </div>
                <div><strong>Status:</strong> 
                    <span class="badge badge-<?php echo $prescription['status'] === 'dispensed' ? 'success' : ($prescription['status'] === 'cancelled' ? 'danger' : 'warning'); ?>">
                        <?php echo ucfirst($prescription['status'] ?? ''); ?>
                    </span>
                </div>
                <?php if ($prescription['notes']): ?>
                <div><strong>Notes:</strong> <?php echo htmlspecialchars($prescription['notes'] ?? ''); ?></div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3>Medications</h3>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Medication</th>
                                <th>Dosage</th>
                                <th>Frequency</th>
                                <th>Duration</th>
                                <th>Instructions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($prescription_items as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['medication_name'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($item['dosage'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($item['frequency'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($item['duration'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($item['instructions'] ?? 'N/A'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="alert alert-info">
            <strong>Important:</strong> Please follow the medication instructions provided by your doctor. If you have any questions or concerns about your prescription, contact your healthcare provider.
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
