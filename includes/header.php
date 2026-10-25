<?php
/**
 * MedCare System - Bootstrap 5 Sidebar Header
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/auth.php';

checkSessionTimeout();

$current_script = basename($_SERVER['PHP_SELF'] ?? '');
$user_role = $_SESSION['role'] ?? '';
$user_name = $_SESSION['full_name'] ?? 'User';
$user_initials = strtoupper(substr($user_name, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedCare - Healthcare &amp; Patient Records</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body>

<?php if (isLoggedIn()): ?>
<!-- Sidebar Wrapper -->
<div class="d-flex" id="wrapper">

    <!-- Sidebar -->
    <nav id="sidebar" class="sidebar d-flex flex-column flex-shrink-0 p-0">
        <!-- Brand -->
        <a href="<?php echo BASE_URL; ?>" class="sidebar-brand d-flex align-items-center px-3 py-3 text-decoration-none">
            <div class="sidebar-brand-icon me-2">
                <i class="bi bi-heart-pulse-fill"></i>
            </div>
            <div class="sidebar-brand-text">
                <span class="fw-bold">MedCare</span>
                <small class="d-block text-truncate">Health Portal</small>
            </div>
        </a>

        <hr class="sidebar-divider m-0">

        <!-- Role Badge -->
        <div class="px-3 py-2">
            <div class="sidebar-role-label">
                <?php if ($user_role === 'doctor'): ?>
                    <i class="bi bi-person-badge-fill text-primary"></i> <span class="text-uppercase small fw-semibold">Physician</span>
                <?php elseif ($user_role === 'staff'): ?>
                    <i class="bi bi-person-workspace text-success"></i> <span class="text-uppercase small fw-semibold">Staff</span>
                <?php else: ?>
                    <i class="bi bi-person-circle text-info"></i> <span class="text-uppercase small fw-semibold">Patient</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Navigation -->
        <ul class="nav flex-column px-2 flex-grow-1">

            <?php if ($user_role === 'doctor'): ?>
                <li class="nav-item">
                    <a class="nav-link sidebar-link <?php echo in_array($current_script, ['dashboard.php']) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/pages/doctor/dashboard.php">
                        <i class="bi bi-house-door-fill"></i> <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link sidebar-link <?php echo in_array($current_script, ['calendar.php','new_appointment.php']) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/pages/doctor/calendar.php">
                        <i class="bi bi-calendar-fill"></i> <span>Calendar</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link sidebar-link <?php echo in_array($current_script, ['patients.php','new_patient.php','view_patient.php','set_priority.php']) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/pages/doctor/patients.php">
                        <i class="bi bi-people-fill"></i> <span>Patients</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link sidebar-link <?php echo in_array($current_script, ['new_consultation.php','consultation_diagnosis.php','view_consultation.php']) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/pages/doctor/new_consultation.php">
                        <i class="bi bi-file-earmark-medical-fill"></i> <span>Consultations</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link sidebar-link <?php echo in_array($current_script, ['prescriptions.php','new_prescription.php','view_prescription.php']) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/pages/doctor/prescriptions.php">
                        <i class="bi bi-capsule-pill"></i> <span>Prescriptions</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link sidebar-link <?php echo in_array($current_script, ['treatment_progress_list.php','treatment_progress.php']) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/pages/doctor/treatment_progress_list.php">
                        <i class="bi bi-activity"></i> <span>Treatment Progress</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link sidebar-link <?php echo in_array($current_script, ['communication.php']) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/pages/doctor/communication.php">
                        <i class="bi bi-chat-dots-fill"></i> <span>Team Messages</span>
                    </a>
                </li>

            <?php elseif ($user_role === 'staff'): ?>
                <li class="nav-item">
                    <a class="nav-link sidebar-link <?php echo in_array($current_script, ['dashboard.php']) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/pages/staff/dashboard.php">
                        <i class="bi bi-house-door-fill"></i> <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link sidebar-link <?php echo in_array($current_script, ['patients.php','new_patient.php','view_patient.php','edit_patient.php']) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/pages/staff/patients.php">
                        <i class="bi bi-people-fill"></i> <span>Patients</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link sidebar-link <?php echo in_array($current_script, ['inventory.php','add_inventory.php','edit_inventory.php','view_inventory.php','add_stock.php','inventory_transactions.php']) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/pages/staff/inventory.php">
                        <i class="bi bi-box-seam-fill"></i> <span>Inventory</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link sidebar-link <?php echo in_array($current_script, ['stock_alerts.php']) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/pages/staff/stock_alerts.php">
                        <i class="bi bi-exclamation-triangle-fill"></i> <span>Stock Alerts</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link sidebar-link <?php echo in_array($current_script, ['prescriptions.php','dispense_prescription.php','view_prescription.php']) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/pages/staff/prescriptions.php">
                        <i class="bi bi-capsule-pill"></i> <span>Dispense Rx</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link sidebar-link <?php echo in_array($current_script, ['treatment_progress_list.php','treatment_progress.php']) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/pages/staff/treatment_progress_list.php">
                        <i class="bi bi-activity"></i> <span>Treatment Progress</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link sidebar-link <?php echo in_array($current_script, ['health_education.php','edit_health_education.php','view_health_education.php']) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/pages/staff/health_education.php">
                        <i class="bi bi-book-fill"></i> <span>Health Education</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link sidebar-link <?php echo in_array($current_script, ['communication.php']) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/pages/staff/communication.php">
                        <i class="bi bi-chat-dots-fill"></i> <span>Team Messages</span>
                    </a>
                </li>

            <?php elseif ($user_role === 'patient'): ?>
                <li class="nav-item">
                    <a class="nav-link sidebar-link <?php echo in_array($current_script, ['dashboard.php']) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/pages/patient/dashboard.php">
                        <i class="bi bi-house-door-fill"></i> <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link sidebar-link <?php echo in_array($current_script, ['my_info.php']) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/pages/patient/my_info.php">
                        <i class="bi bi-person-vcard-fill"></i> <span>My Health Record</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link sidebar-link <?php echo in_array($current_script, ['appointments.php']) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/pages/patient/appointments.php">
                        <i class="bi bi-calendar-fill"></i> <span>My Appointments</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link sidebar-link <?php echo in_array($current_script, ['prescriptions.php','view_prescription.php']) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/pages/patient/prescriptions.php">
                        <i class="bi bi-capsule-pill"></i> <span>Prescriptions</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link sidebar-link <?php echo in_array($current_script, ['my_treatments.php']) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/pages/patient/my_treatments.php">
                        <i class="bi bi-activity"></i> <span>Treatments &amp; Progress</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link sidebar-link <?php echo in_array($current_script, ['health_education.php','view_health_education.php']) ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/pages/patient/health_education.php">
                        <i class="bi bi-book-fill"></i> <span>Health Library</span>
                    </a>
                </li>
            <?php endif; ?>

        </ul>

        <hr class="sidebar-divider m-0">

        <!-- User Profile + Logout -->
        <div class="sidebar-footer px-3 py-3">
            <div class="d-flex align-items-center gap-2 mb-2">
                <div class="sidebar-avatar"><?php echo $user_initials; ?></div>
                <div class="overflow-hidden">
                    <div class="fw-semibold text-truncate small"><?php echo htmlspecialchars($user_name); ?></div>
                    <div class="text-xs text-muted"><?php echo ucfirst($user_role); ?></div>
                </div>
            </div>
            <a href="<?php echo BASE_URL; ?>/pages/auth/logout.php" class="btn btn-outline-danger btn-sm w-100">
                <i class="bi bi-box-arrow-right me-1"></i> Sign Out
            </a>
        </div>
    </nav>
    <!-- End Sidebar -->

    <!-- Page Content Wrapper -->
    <div id="page-content-wrapper" class="flex-grow-1 d-flex flex-column min-vh-100">

        <!-- Top Navbar (mobile toggle + breadcrumb) -->
        <nav class="top-navbar navbar navbar-expand-lg border-bottom px-3 py-2">
            <button class="btn btn-sm btn-outline-secondary me-3 d-lg-none" id="sidebarToggle" type="button" aria-label="Open menu">
                <i class="bi bi-list"></i> Menu
            </button>
            <span class="navbar-text text-muted small">
                <i class="bi bi-heart-pulse-fill text-danger me-1"></i>
                MedCare Patient Record &amp; Care Coordination System
            </span>
            <div class="ms-auto d-flex align-items-center gap-2">
                <span class="badge bg-light text-dark border"><?php echo date('M d, Y'); ?></span>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content flex-grow-1 p-3 p-md-4">

<?php else: ?>
<!-- Guest layout (auth pages) -->
<div id="auth-wrapper">
<main class="main-content">
<?php endif; ?>
