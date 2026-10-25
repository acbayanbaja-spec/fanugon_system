<?php
/**
 * MedCare System - Patient Dashboard
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
    echo '<div class="page-container"><div class="alert alert-danger">Patient record not found. Please contact clinic staff.</div></div>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit();
}

$patient_id = $patient['patient_id'];

// Get patient information
$query = "
    SELECT p.*, pp.priority_level 
    FROM patients p 
    LEFT JOIN patient_priority pp ON pp.priority_id = (
        SELECT pp2.priority_id FROM patient_priority pp2
        WHERE pp2.patient_id = p.patient_id
        ORDER BY pp2.assigned_at DESC, pp2.priority_id DESC LIMIT 1
    ) 
    WHERE p.patient_id = ?
";
$stmt = $conn->prepare($query);
$stmt->execute([$patient_id]);
$patient_info = $stmt->fetch();

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
$recent_consultations = $stmt->fetchAll();

// Get active treatments
$query = "
    SELECT t.*, d.diagnosis_name 
    FROM treatments t 
    LEFT JOIN diagnoses d ON t.diagnosis_id = d.diagnosis_id 
    WHERE t.patient_id = ? AND t.status IN ('pending', 'ongoing')
    ORDER BY t.start_date DESC
";
$stmt = $conn->prepare($query);
$stmt->execute([$patient_id]);
$active_treatments = $stmt->fetchAll();

// Get recent prescriptions
$query = "
    SELECT p.*, u.full_name as doctor_name 
    FROM prescriptions p 
    JOIN users u ON p.doctor_id = u.user_id 
    WHERE p.patient_id = ? 
    ORDER BY p.prescription_date DESC 
    LIMIT 5
";
$stmt = $conn->prepare($query);
$stmt->execute([$patient_id]);
$recent_prescriptions = $stmt->fetchAll();
?>

<div class="page-container">

    <!-- Page Header -->
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title-group">
                <div class="page-title-icon patient">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <div>
                    <h1 class="page-title">Patient Portal</h1>
                    <p class="page-subtitle">Welcome, <?php echo htmlspecialchars($patient_info['first_name'] . ' ' . $patient_info['last_name']); ?> • Patient #<?php echo str_pad($patient_id, 4, '0', STR_PAD_LEFT); ?></p>
                </div>
            </div>
            <div class="page-header-actions">
                <a href="<?php echo BASE_URL; ?>/pages/patient/my_info.php" class="btn btn-outline btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="margin-right:6px;vertical-align:-3px"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    My Profile
                </a>
            </div>
        </div>
    </div>

    <?php if (isset($_GET['registered'])): ?>
    <div class="alert alert-success" style="margin-bottom: 1.5rem;">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div>
            <strong>Registration Successful!</strong> Welcome to MedCare, <?php echo htmlspecialchars($patient_info['first_name'] ?? ''); ?>. Your medical portal profile and patient file have been initialized.
        </div>
    </div>
    <?php endif; ?>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card primary">
            <div class="stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo count($recent_consultations); ?></div>
                <div class="stat-label">Consultations</div>
            </div>
        </div>
        <div class="stat-card success">
            <div class="stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo count($active_treatments); ?></div>
                <div class="stat-label">Active Treatments</div>
            </div>
        </div>
        <div class="stat-card warning">
            <div class="stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo count($recent_prescriptions); ?></div>
                <div class="stat-label">Prescriptions</div>
            </div>
        </div>
        <div class="stat-card teal">
            <div class="stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo htmlspecialchars($patient_info['blood_type'] ?? 'N/A'); ?></div>
                <div class="stat-label">Blood Type</div>
            </div>
        </div>
    </div>

    <!-- Quick Navigation -->
    <div class="card">
        <div class="card-header">
            <h3>Quick Access</h3>
        </div>
        <div class="card-body">
            <div class="quick-actions-grid">
                <a href="<?php echo BASE_URL; ?>/pages/patient/my_info.php" class="quick-action-item primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    My Information
                </a>
                <a href="<?php echo BASE_URL; ?>/pages/patient/my_treatments.php" class="quick-action-item success">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    My Treatments
                </a>
                <a href="<?php echo BASE_URL; ?>/pages/patient/prescriptions.php" class="quick-action-item warning">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    My Prescriptions
                </a>
                <a href="<?php echo BASE_URL; ?>/pages/patient/health_education.php" class="quick-action-item info">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    Health Library
                </a>
            </div>
        </div>
    </div>

    <!-- Active Treatments & Recent Consultations -->
    <div class="dashboard-two-col">

        <!-- Active Treatments -->
        <div class="card">
            <div class="card-header">
                <h3>Ongoing Treatments</h3>
                <a href="<?php echo BASE_URL; ?>/pages/patient/my_treatments.php" class="card-header-link">View all →</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($active_treatments)): ?>
                    <div class="empty-state small">No active treatments at this time.</div>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Treatment</th>
                                    <th>Diagnosis</th>
                                    <th>Status</th>
                                    <th>Started</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($active_treatments as $treatment): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($treatment['treatment_name'] ?? ''); ?></strong></td>
                                        <td><?php echo htmlspecialchars($treatment['diagnosis_name'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $treatment['status'] === 'ongoing' ? 'success' : 'warning'; ?>">
                                                <?php echo ucfirst($treatment['status'] ?? ''); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($treatment['start_date'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Consultations -->
        <div class="card">
            <div class="card-header">
                <h3>Recent Consultations</h3>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recent_consultations)): ?>
                    <div class="empty-state small">No consultations recorded yet.</div>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Doctor</th>
                                    <th>Complaint</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_consultations as $consultation): ?>
                                    <tr>
                                        <td>
                                            <span style="font-weight:600;"><?php echo date('M d, Y', strtotime($consultation['consultation_date'])); ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($consultation['doctor_name'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars(substr($consultation['chief_complaint'], 0, 35)) . (strlen($consultation['chief_complaint']) > 35 ? '...' : ''); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
