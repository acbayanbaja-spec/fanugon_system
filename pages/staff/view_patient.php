<?php
/**
 * MedCare System - Staff: View Patient Details
 */
require_once __DIR__ . '/../../includes/auth.php';
requireRole('staff');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/header.php';

$database = new Database();
$conn = $database->getConnection();

$patient_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$patient_id) {
    echo '<div class="alert alert-danger">Invalid patient ID.</div>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit();
}

// Get patient information
$query = "SELECT * FROM patients WHERE patient_id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$patient_id]);
$patient = $stmt->fetch();

if (!$patient) {
    echo '<div class="alert alert-danger">Patient not found.</div>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit();
}

// Get patient priority
$query = "SELECT * FROM patient_priority WHERE patient_id = ? ORDER BY assigned_at DESC, priority_id DESC LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->execute([$patient_id]);
$priority = $stmt->fetch();

// Get medical history
$query = "SELECT * FROM patient_medical_history WHERE patient_id = ? ORDER BY diagnosis_date DESC";
$stmt = $conn->prepare($query);
$stmt->execute([$patient_id]);
$medical_history = $stmt->fetchAll();

// Get recent consultations
$query = "
    SELECT c.*, u.full_name as doctor_name 
    FROM consultations c 
    JOIN users u ON c.doctor_id = u.user_id 
    WHERE c.patient_id = ? 
    ORDER BY c.consultation_date DESC 
    LIMIT 5
";
$stmt = $conn->prepare($query);
$stmt->execute([$patient_id]);
$consultations = $stmt->fetchAll();
?>

<div class="page-container">
    <!-- Page Header -->
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title-group">
                <div class="page-title-icon staff">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <div>
                    <h1 class="page-title"><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></h1>
                    <p class="page-subtitle">Patient ID: #<?php echo str_pad($patient['patient_id'], 4, '0', STR_PAD_LEFT); ?> • Contact: <?php echo htmlspecialchars($patient['contact_number'] ?? ''); ?></p>
                </div>
            </div>
            <div class="page-header-actions">
                <a href="<?php echo BASE_URL; ?>/pages/staff/edit_patient.php?id=<?php echo $patient_id; ?>" class="btn btn-primary btn-sm">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:6px;vertical-align:-3px"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                    Edit Patient
                </a>
                <a href="<?php echo BASE_URL; ?>/pages/staff/patients.php" class="btn btn-outline btn-sm">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:6px;vertical-align:-3px"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                    Back to Patients
                </a>
            </div>
        </div>
    </div>

    <!-- Personal & Contact Information Card -->
    <div class="card">
        <div class="card-header">
            <h3>Personal & Contact Profile</h3>
            <?php if ($priority): ?>
                <div>
                    <span style="font-size:0.825rem; font-weight:600; color:var(--slate-500); margin-right:4px;">Care Priority:</span>
                    <span class="badge priority-<?php echo $priority['priority_level']; ?>">
                        <?php echo ucfirst($priority['priority_level'] ?? ''); ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:1.25rem;">
                <div>
                    <div style="font-size:0.75rem; text-transform:uppercase; font-weight:700; color:var(--slate-400);">Full Name</div>
                    <div style="font-weight:600; font-size:1rem; margin-top:2px;"><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></div>
                </div>
                <div>
                    <div style="font-size:0.75rem; text-transform:uppercase; font-weight:700; color:var(--slate-400);">Date of Birth & Age</div>
                    <div style="font-weight:600; margin-top:2px;">
                        <?php echo $patient['date_of_birth'] ? date('M d, Y', strtotime($patient['date_of_birth'])) : 'N/A'; ?>
                        <?php if ($patient['date_of_birth']): ?>
                            <span style="color:var(--slate-500); font-size:0.825rem;">(<?php echo date_diff(date_create($patient['date_of_birth']), date_create('today'))->y; ?> yrs)</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div>
                    <div style="font-size:0.75rem; text-transform:uppercase; font-weight:700; color:var(--slate-400);">Gender & Blood Type</div>
                    <div style="font-weight:600; margin-top:2px;"><?php echo ucfirst($patient['gender'] ?? ''); ?> • Blood: <span class="badge badge-info"><?php echo htmlspecialchars($patient['blood_type'] ?? 'N/A'); ?></span></div>
                </div>
                <div>
                    <div style="font-size:0.75rem; text-transform:uppercase; font-weight:700; color:var(--slate-400);">Contact Phone</div>
                    <div style="font-weight:600; margin-top:2px;"><?php echo htmlspecialchars($patient['contact_number'] ?? ''); ?></div>
                </div>
                <div>
                    <div style="font-size:0.75rem; text-transform:uppercase; font-weight:700; color:var(--slate-400);">Residential Address</div>
                    <div style="font-weight:500; margin-top:2px;"><?php echo htmlspecialchars($patient['address'] ?? 'Not provided'); ?></div>
                </div>
                <div>
                    <div style="font-size:0.75rem; text-transform:uppercase; font-weight:700; color:var(--slate-400);">Emergency Contact</div>
                    <div style="font-weight:500; margin-top:2px;">
                        <?php echo htmlspecialchars($patient['emergency_contact_name'] ?? 'N/A'); ?>
                        <?php if (!empty($patient['emergency_contact_number'])): ?>
                            <span style="color:var(--slate-500); font-size:0.825rem;">(<?php echo htmlspecialchars($patient['emergency_contact_number'] ?? ''); ?>)</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div style="grid-column: 1 / -1; background:var(--slate-50); padding:0.85rem 1rem; border-radius:8px; border:1px solid var(--slate-200);">
                    <div style="font-size:0.75rem; text-transform:uppercase; font-weight:700; color:var(--slate-500);">Known Allergies</div>
                    <div style="font-weight:600; color:<?php echo !empty($patient['allergies']) && strtolower($patient['allergies']) !== 'none' ? 'var(--danger)' : 'var(--slate-700)'; ?>; margin-top:2px;">
                        <?php echo htmlspecialchars($patient['allergies'] ?? 'None Reported'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Medical History -->
    <?php if (!empty($medical_history)): ?>
    <div class="card p-0">
        <div class="card-header">
            <h3>Medical History (<?php echo count($medical_history); ?>)</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-container" style="border:none; margin:0; border-radius:0 0 var(--radius-lg) var(--radius-lg);">
                <table>
                    <thead>
                        <tr>
                            <th>Condition</th>
                            <th>Diagnosis Date</th>
                            <th>Status</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($medical_history as $history): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($history['condition_name'] ?? ''); ?></strong></td>
                                <td><?php echo $history['diagnosis_date'] ? date('M d, Y', strtotime($history['diagnosis_date'])) : 'N/A'; ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $history['status'] === 'active' ? 'danger' : ($history['status'] === 'resolved' ? 'success' : 'warning'); ?>">
                                        <?php echo ucfirst($history['status'] ?? ''); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($history['notes'] ?? 'None'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Recent Consultations -->
    <?php if (!empty($consultations)): ?>
    <div class="card p-0">
        <div class="card-header">
            <h3>Recent Consultations</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-container" style="border:none; margin:0; border-radius:0 0 var(--radius-lg) var(--radius-lg);">
                <table>
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Doctor</th>
                            <th>Chief Complaint</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($consultations as $consultation): ?>
                            <tr>
                                <td>
                                    <span style="font-weight:600;"><?php echo date('M d, Y', strtotime($consultation['consultation_date'])); ?></span>
                                    <div style="font-size:0.75rem; color:var(--slate-500);"><?php echo date('h:i A', strtotime($consultation['consultation_date'])); ?></div>
                                </td>
                                <td><?php echo htmlspecialchars($consultation['doctor_name'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars(substr($consultation['chief_complaint'], 0, 60)) . (strlen($consultation['chief_complaint']) > 60 ? '...' : ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
