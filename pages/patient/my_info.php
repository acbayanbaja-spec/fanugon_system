<?php
/**
 * MedCare System - Patient: My Information
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

// Get patient information
$query = "SELECT p.*, u.email, u.username FROM patients p LEFT JOIN users u ON p.user_id = u.user_id WHERE p.patient_id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$patient_id]);
$patient_info = $stmt->fetch();
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
                    <h1 class="page-title">My Profile & Clinical Information</h1>
                    <p class="page-subtitle">Your personal health record details and online account credentials</p>
                </div>
            </div>
            <div class="page-header-actions">
                <a href="<?php echo BASE_URL; ?>/pages/patient/dashboard.php" class="btn btn-outline btn-sm">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:6px;vertical-align:-3px"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                    Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Personal Health Profile Card -->
    <div class="card">
        <div class="card-header">
            <h3>Personal & Contact Profile</h3>
        </div>
        <div class="card-body">
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:1.25rem;">
                <div>
                    <div style="font-size:0.75rem; text-transform:uppercase; font-weight:700; color:var(--slate-400);">Full Name</div>
                    <div style="font-weight:600; font-size:1rem; margin-top:2px;"><?php echo htmlspecialchars($patient_info['first_name'] . ' ' . $patient_info['last_name']); ?></div>
                </div>
                <div>
                    <div style="font-size:0.75rem; text-transform:uppercase; font-weight:700; color:var(--slate-400);">Date of Birth</div>
                    <div style="font-weight:600; margin-top:2px;">
                        <?php echo $patient_info['date_of_birth'] ? date('M d, Y', strtotime($patient_info['date_of_birth'])) : 'N/A'; ?>
                    </div>
                </div>
                <div>
                    <div style="font-size:0.75rem; text-transform:uppercase; font-weight:700; color:var(--slate-400);">Gender & Blood Type</div>
                    <div style="font-weight:600; margin-top:2px;"><?php echo ucfirst($patient_info['gender'] ?? ''); ?> • Blood: <span class="badge badge-info"><?php echo htmlspecialchars($patient_info['blood_type'] ?? 'N/A'); ?></span></div>
                </div>
                <div>
                    <div style="font-size:0.75rem; text-transform:uppercase; font-weight:700; color:var(--slate-400);">Contact Phone</div>
                    <div style="font-weight:600; margin-top:2px;"><?php echo htmlspecialchars($patient_info['contact_number'] ?? ''); ?></div>
                </div>
                <div>
                    <div style="font-size:0.75rem; text-transform:uppercase; font-weight:700; color:var(--slate-400);">Residential Address</div>
                    <div style="font-weight:500; margin-top:2px;"><?php echo htmlspecialchars($patient_info['address'] ?? 'Not provided'); ?></div>
                </div>
                <div>
                    <div style="font-size:0.75rem; text-transform:uppercase; font-weight:700; color:var(--slate-400);">Emergency Contact</div>
                    <div style="font-weight:500; margin-top:2px;">
                        <?php echo htmlspecialchars($patient_info['emergency_contact_name'] ?? 'N/A'); ?>
                        <?php if (!empty($patient_info['emergency_contact_number'])): ?>
                            <span style="color:var(--slate-500); font-size:0.825rem;">(<?php echo htmlspecialchars($patient_info['emergency_contact_number'] ?? ''); ?>)</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div style="grid-column: 1 / -1; background:var(--slate-50); padding:0.85rem 1rem; border-radius:8px; border:1px solid var(--slate-200);">
                    <div style="font-size:0.75rem; text-transform:uppercase; font-weight:700; color:var(--slate-500);">Known Allergies</div>
                    <div style="font-weight:600; color:<?php echo !empty($patient_info['allergies']) && strtolower($patient_info['allergies']) !== 'none' ? 'var(--danger)' : 'var(--slate-700)'; ?>; margin-top:2px;">
                        <?php echo htmlspecialchars($patient_info['allergies'] ?? 'None Reported'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Portal Account Card -->
    <div class="card">
        <div class="card-header">
            <h3>Online Portal Account</h3>
        </div>
        <div class="card-body">
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:1.25rem;">
                <div>
                    <div style="font-size:0.75rem; text-transform:uppercase; font-weight:700; color:var(--slate-400);">Portal Username</div>
                    <div style="font-weight:600; font-size:1rem; margin-top:2px; color:var(--primary);"><?php echo htmlspecialchars($_SESSION['username'] ?? ($patient_info['username'] ?? 'N/A')); ?></div>
                </div>
                <div>
                    <div style="font-size:0.75rem; text-transform:uppercase; font-weight:700; color:var(--slate-400);">Registered Email</div>
                    <div style="font-weight:500; margin-top:2px;"><?php echo htmlspecialchars($patient_info['email'] ?? $_SESSION['email'] ?? 'Not provided'); ?></div>
                </div>
                <div>
                    <div style="font-size:0.75rem; text-transform:uppercase; font-weight:700; color:var(--slate-400);">Account Status</div>
                    <div style="margin-top:4px;"><span class="badge badge-success">Active & Verified</span></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
