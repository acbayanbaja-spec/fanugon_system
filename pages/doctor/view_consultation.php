<?php
/**
 * MedCare System - Doctor: View Consultation Details
 */
require_once __DIR__ . '/../../includes/auth.php';
requireRole('doctor');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/header.php';

$database = new Database();
$conn = $database->getConnection();

$consultation_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$consultation_id) {
    echo '<div class="alert alert-danger">Invalid consultation ID.</div>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit();
}

// Get consultation information
$query = "
    SELECT c.*, p.first_name, p.last_name, u.full_name as doctor_name 
    FROM consultations c 
    JOIN patients p ON c.patient_id = p.patient_id 
    JOIN users u ON c.doctor_id = u.user_id 
    WHERE c.consultation_id = ?
";
$stmt = $conn->prepare($query);
$stmt->execute([$consultation_id]);
$consultation = $stmt->fetch();

if (!$consultation) {
    echo '<div class="alert alert-danger">Consultation not found.</div>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit();
}

// Get diagnoses
$query = "
    SELECT d.*, u.full_name as diagnosed_by_name 
    FROM diagnoses d 
    JOIN users u ON d.diagnosed_by = u.user_id 
    WHERE d.consultation_id = ?
";
$stmt = $conn->prepare($query);
$stmt->execute([$consultation_id]);
$diagnoses = $stmt->fetchAll();

// Get treatments
$query = "
    SELECT t.*, d.diagnosis_name 
    FROM treatments t 
    LEFT JOIN diagnoses d ON t.diagnosis_id = d.diagnosis_id 
    WHERE t.consultation_id = ?
";
$stmt = $conn->prepare($query);
$stmt->execute([$consultation_id]);
$treatments = $stmt->fetchAll();

// Get prescriptions
$query = "
    SELECT p.*, u.full_name as doctor_name 
    FROM prescriptions p 
    JOIN users u ON p.doctor_id = u.user_id 
    WHERE p.consultation_id = ?
";
$stmt = $conn->prepare($query);
$stmt->execute([$consultation_id]);
$prescriptions = $stmt->fetchAll();
?>

<div class="card">
    <div class="card-header">
        <h2>Consultation Details</h2>
    </div>
    <div class="card-body">
        <div class="btn-group" style="margin-bottom: 1rem;">
            <a href="<?php echo BASE_URL; ?>/pages/doctor/view_patient.php?id=<?php echo $consultation['patient_id']; ?>" class="btn btn-secondary">Back to Patient</a>
            <a href="<?php echo BASE_URL; ?>/pages/doctor/consultation_diagnosis.php?consultation_id=<?php echo $consultation_id; ?>" class="btn btn-primary">Add Diagnosis/Treatment</a>
            <a href="<?php echo BASE_URL; ?>/pages/doctor/new_prescription.php?consultation_id=<?php echo $consultation_id; ?>&patient_id=<?php echo $consultation['patient_id']; ?>" class="btn btn-success">Create Prescription</a>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3>Consultation Information</h3>
            </div>
            <div class="card-body">
                <div class="form-row">
                    <div><strong>Patient:</strong> <?php echo htmlspecialchars($consultation['first_name'] . ' ' . $consultation['last_name']); ?></div>
                    <div><strong>Doctor:</strong> <?php echo htmlspecialchars($consultation['doctor_name'] ?? ''); ?></div>
                </div>
                <div class="form-row">
                    <div><strong>Date:</strong> <?php echo date('M d, Y h:i A', strtotime($consultation['consultation_date'])); ?></div>
                </div>
                <div><strong>Chief Complaint:</strong> <?php echo htmlspecialchars($consultation['chief_complaint'] ?? ''); ?></div>
                <?php if ($consultation['vital_signs']): ?>
                <div><strong>Vital Signs:</strong> <?php echo htmlspecialchars($consultation['vital_signs'] ?? ''); ?></div>
                <?php endif; ?>
                <?php if ($consultation['notes']): ?>
                <div><strong>Notes:</strong> <?php echo htmlspecialchars($consultation['notes'] ?? ''); ?></div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if (!empty($diagnoses)): ?>
        <div class="card">
            <div class="card-header">
                <h3>Diagnoses</h3>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Diagnosis</th>
                                <th>Severity</th>
                                <th>Diagnosed By</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($diagnoses as $diagnosis): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($diagnosis['diagnosis_code'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($diagnosis['diagnosis_name'] ?? ''); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $diagnosis['severity'] === 'severe' ? 'danger' : ($diagnosis['severity'] === 'moderate' ? 'warning' : 'success'); ?>">
                                            <?php echo ucfirst($diagnosis['severity'] ?? ''); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($diagnosis['diagnosed_by_name'] ?? ''); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($diagnosis['diagnosis_date'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($treatments)): ?>
        <div class="card">
            <div class="card-header">
                <h3>Treatments</h3>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Treatment</th>
                                <th>Related Diagnosis</th>
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
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($prescriptions)): ?>
        <div class="card">
            <div class="card-header">
                <h3>Prescriptions</h3>
            </div>
            <div class="card-body">
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
                                        <span class="badge badge-<?php echo $prescription['status'] === 'dispensed' ? 'success' : 'warning'; ?>">
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
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
