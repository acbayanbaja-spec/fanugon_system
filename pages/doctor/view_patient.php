<?php
/**
 * MedCare System - Doctor: View Patient Details
 */
require_once __DIR__ . '/../../includes/auth.php';
requireRole('doctor');

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
                <div class="page-title-icon doctor">
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
                <a href="<?php echo BASE_URL; ?>/pages/doctor/new_consultation.php?patient_id=<?php echo $patient_id; ?>" class="btn btn-primary btn-sm">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:6px;vertical-align:-3px"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                    New Consultation
                </a>
                <a href="<?php echo BASE_URL; ?>/pages/doctor/set_priority.php?patient_id=<?php echo $patient_id; ?>" class="btn btn-warning btn-sm">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:6px;vertical-align:-3px"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg>
                    Set Priority
                </a>
                <a href="<?php echo BASE_URL; ?>/pages/doctor/patients.php" class="btn btn-outline btn-sm">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:6px;vertical-align:-3px"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                    Back to Patients
                </a>
            </div>
        </div>
    </div>

    <!-- Personal & Clinical Profile Card -->
    <div class="card">
        <div class="card-header">
            <h3>Personal & Clinical Profile</h3>
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
                    <div style="font-size:0.75rem; text-transform:uppercase; font-weight:700; color:var(--slate-400);">Phone / Contact</div>
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
    <div class="card p-0">
        <div class="card-header">
            <h3>Recent Consultations</h3>
            <a href="<?php echo BASE_URL; ?>/pages/doctor/new_consultation.php?patient_id=<?php echo $patient_id; ?>" class="btn btn-primary btn-sm">+ Add Consultation</a>
        </div>
        <div class="card-body p-0">
            <?php if (empty($consultations)): ?>
                <div class="empty-state small">No consultations recorded yet for this patient.</div>
            <?php else: ?>
                <div class="table-container" style="border:none; margin:0; border-radius:0 0 var(--radius-lg) var(--radius-lg);">
                    <table>
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Doctor</th>
                                <th>Chief Complaint</th>
                                <th style="text-align:right;">Action</th>
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
                                    <td style="text-align:right;">
                                        <a href="<?php echo BASE_URL; ?>/pages/doctor/view_consultation.php?id=<?php echo $consultation['consultation_id']; ?>" class="btn btn-outline btn-sm">View Full</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
