<?php
/**
 * MedCare System - Doctor Dashboard
 */
require_once __DIR__ . '/../../includes/auth.php';
requireRole('doctor');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/header.php';

$database = new Database();
$conn = $database->getConnection();

$doctor_id = $_SESSION['user_id'];

// Get statistics
$total_patients = $conn->query("SELECT COUNT(*) as count FROM patients")->fetch()['count'];
$today_consultations = $conn->query("SELECT COUNT(*) as count FROM consultations WHERE DATE(consultation_date) = CURDATE()")->fetch()['count'];
$pending_prescriptions = $conn->query("SELECT COUNT(*) as count FROM prescriptions WHERE status = 'pending'")->fetch()['count'];
$high_priority_patients = $conn->query("
    SELECT COUNT(*) as count FROM patient_priority pp
    WHERE pp.priority_level IN ('high', 'critical')
      AND pp.priority_id = (SELECT pp2.priority_id FROM patient_priority pp2 WHERE pp2.patient_id = pp.patient_id ORDER BY pp2.assigned_at DESC, pp2.priority_id DESC LIMIT 1)
")->fetch()['count'];

// Get recent consultations
$recent_consultations = $conn->query("
    SELECT c.*, p.first_name, p.last_name, pp.priority_level
    FROM consultations c 
    JOIN patients p ON c.patient_id = p.patient_id 
    LEFT JOIN patient_priority pp ON pp.priority_id = (
        SELECT pp2.priority_id FROM patient_priority pp2
        WHERE pp2.patient_id = p.patient_id
        ORDER BY pp2.assigned_at DESC, pp2.priority_id DESC LIMIT 1
    )
    ORDER BY c.consultation_date DESC 
    LIMIT 6
")->fetchAll();

// Get critical/high priority patients needing attention
$urgent_patients = $conn->query("
    SELECT p.*, pp.priority_level, pp.urgency_reason as priority_reason
    FROM patients p
    LEFT JOIN patient_priority pp ON pp.priority_id = (
        SELECT pp2.priority_id FROM patient_priority pp2
        WHERE pp2.patient_id = p.patient_id
        ORDER BY pp2.assigned_at DESC, pp2.priority_id DESC LIMIT 1
    )
    WHERE pp.priority_level IN ('critical', 'high')
    ORDER BY CASE pp.priority_level WHEN 'critical' THEN 1 WHEN 'high' THEN 2 ELSE 3 END, p.created_at DESC
    LIMIT 5
")->fetchAll();

// Doctor's own appointments for the dashboard calendar section.
// Always filtered by the logged-in doctor (server-side authorization).
$doctor_id = (int)$doctor_id;
$today = date('Y-m-d');

try {
    // Month grid range (6 weeks, Sunday-based)
    $cal_month = (int)date('n');
    $cal_year  = (int)date('Y');
    $cal_first = sprintf('%04d-%02d-01', $cal_year, $cal_month);
    $cal_start = date('Y-m-d', strtotime($cal_first . ' -' . (int)date('w', strtotime($cal_first)) . ' days'));
    $cal_end   = date('Y-m-d', strtotime($cal_start . ' +41 days'));

    $stmt = $conn->prepare("
        SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.status,
               p.first_name, p.last_name
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        WHERE a.doctor_id = ? AND a.appointment_date BETWEEN ? AND ?
        ORDER BY a.appointment_date, a.appointment_time
    ");
    $stmt->execute([$doctor_id, $cal_start, $cal_end]);
    $calendar_events = $stmt->fetchAll();

    $events_by_date = [];
    foreach ($calendar_events as $event) {
        $events_by_date[$event['appointment_date']][] = $event;
    }

    // Today's agenda
    $stmt = $conn->prepare("
        SELECT a.appointment_id, a.appointment_time, a.status, a.appointment_type, a.reason,
               p.first_name, p.last_name
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        WHERE a.doctor_id = ? AND a.appointment_date = ?
        ORDER BY a.appointment_time
    ");
    $stmt->execute([$doctor_id, $today]);
    $today_appointments = $stmt->fetchAll();

    // Upcoming (next 7 days, excluding today)
    $stmt = $conn->prepare("
        SELECT COUNT(*) FROM appointments
        WHERE doctor_id = ? AND appointment_date > ?
          AND appointment_date <= DATE_ADD(?, INTERVAL 7 DAY)
          AND status IN ('scheduled', 'confirmed')
    ");
    $stmt->execute([$doctor_id, $today, $today]);
    $upcoming_appointments = (int)$stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Dashboard appointment calendar error: " . $e->getMessage());
    $events_by_date = [];
    $today_appointments = [];
    $upcoming_appointments = 0;
}
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
                    <h1 class="page-title">Doctor Dashboard</h1>
                    <p class="page-subtitle">Welcome back, <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Doctor'); ?> — <?php echo date('l, F j, Y'); ?></p>
                </div>
            </div>
            <div class="page-header-actions">
                <a href="<?php echo BASE_URL; ?>/pages/doctor/new_consultation.php" class="btn btn-primary btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="margin-right:6px;vertical-align:-3px"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    New Consultation
                </a>
                <a href="<?php echo BASE_URL; ?>/pages/doctor/new_patient.php" class="btn btn-outline btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="margin-right:6px;vertical-align:-3px"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                    Register Patient
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card primary">
            <div class="stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $total_patients; ?></div>
                <div class="stat-label">Total Patients</div>
            </div>
        </div>
        <div class="stat-card success">
            <div class="stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $today_consultations; ?></div>
                <div class="stat-label">Consultations Today</div>
            </div>
        </div>
        <div class="stat-card warning">
            <div class="stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $pending_prescriptions; ?></div>
                <div class="stat-label">Active Prescriptions</div>
            </div>
        </div>
        <div class="stat-card <?php echo $high_priority_patients > 0 ? 'danger' : 'teal'; ?>">
            <div class="stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $high_priority_patients; ?></div>
                <div class="stat-label">Priority Patients</div>
            </div>
        </div>
        <div class="stat-card info">
            <div class="stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo count($today_appointments); ?></div>
                <div class="stat-label">Appointments Today</div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="card">
        <div class="card-header">
            <h3>Quick Actions</h3>
        </div>
        <div class="card-body">
            <div class="quick-actions-grid">
                <a href="<?php echo BASE_URL; ?>/pages/doctor/patients.php" class="quick-action-item primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Patient Records
                </a>
                <a href="<?php echo BASE_URL; ?>/pages/doctor/new_consultation.php" class="quick-action-item success">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    New Consultation
                </a>
                <a href="<?php echo BASE_URL; ?>/pages/doctor/prescriptions.php" class="quick-action-item warning">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Prescriptions
                </a>
                <a href="<?php echo BASE_URL; ?>/pages/doctor/treatment_progress_list.php" class="quick-action-item teal">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    Treatment Progress
                </a>
                <a href="<?php echo BASE_URL; ?>/pages/doctor/communication.php" class="quick-action-item info">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    Communication
                </a>
                <a href="<?php echo BASE_URL; ?>/pages/doctor/new_patient.php" class="quick-action-item danger">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                    Add Patient
                </a>
            </div>
        </div>
    </div>

    <!-- Two-column section: Calendar + Today's Appointments -->
    <div class="dashboard-two-col">

        <!-- Doctor Appointment Calendar -->
        <div class="card">
            <div class="card-header">
                <h3>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="var(--primary)" stroke-width="2" style="margin-right:6px;vertical-align:-2px"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    My Appointment Calendar
                </h3>
                <a href="<?php echo BASE_URL; ?>/pages/doctor/calendar.php" class="card-header-link">Open full calendar →</a>
            </div>
            <div class="card-body">
                <div class="calendar-toolbar" style="margin-bottom:0.75rem;">
                    <div class="calendar-nav">
                        <h2 class="calendar-month-label" style="font-size:1rem;"><?php echo date('F Y'); ?></h2>
                    </div>
                    <div class="calendar-filters">
                        <span class="legend-item">
                            <i class="bi bi-calendar-event"></i>
                            <?php echo count($today_appointments); ?> today &middot; <?php echo $upcoming_appointments; ?> upcoming
                        </span>
                    </div>
                </div>

                <div class="calendar-grid calendar-weekdays">
                    <?php foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $wd): ?>
                        <div class="calendar-weekday"><?php echo $wd; ?></div>
                    <?php endforeach; ?>
                </div>

                <div class="calendar-grid calendar-days calendar-mini">
                    <?php for ($cell = 0; $cell < 42; $cell++):
                        $cell_date = date('Y-m-d', strtotime($cal_start . " +{$cell} days"));
                        $cell_events = isset($events_by_date[$cell_date]) ? $events_by_date[$cell_date] : [];
                        $classes = 'calendar-day';
                        if ((int)date('n', strtotime($cell_date)) !== $cal_month) { $classes .= ' outside-month'; }
                        if ($cell_date === $today) { $classes .= ' today'; }
                    ?>
                        <div class="<?php echo $classes; ?>">
                            <a class="calendar-day-number"
                               href="<?php echo BASE_URL; ?>/pages/doctor/calendar.php?view=day&amp;date=<?php echo $cell_date; ?>"
                               title="<?php echo htmlspecialchars(date('F j, Y', strtotime($cell_date))); ?>">
                                <?php echo (int)date('j', strtotime($cell_date)); ?>
                                <?php if ($cell_events): ?>
                                    <span class="calendar-day-count"><?php echo count($cell_events); ?></span>
                                <?php endif; ?>
                            </a>
                        </div>
                    <?php endfor; ?>
                </div>

                <div class="calendar-legend">
                    <span class="legend-title">Legend</span>
                    <span class="legend-item"><span class="legend-dot scheduled"></span>Scheduled</span>
                    <span class="legend-item"><span class="legend-dot confirmed"></span>Confirmed</span>
                    <span class="legend-item"><span class="legend-dot completed"></span>Completed</span>
                    <span class="legend-item"><span class="legend-dot cancelled"></span>Cancelled</span>
                    <span class="legend-item"><span class="legend-dot no_show"></span>No Show</span>
                </div>
            </div>
        </div>

        <!-- Today's Appointments -->
        <div class="card">
            <div class="card-header">
                <h3>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="var(--teal)" stroke-width="2" style="margin-right:6px;vertical-align:-2px"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    Today's Appointments
                </h3>
                <a href="<?php echo BASE_URL; ?>/pages/doctor/calendar.php?view=day&amp;date=<?php echo $today; ?>" class="card-header-link">Open day view →</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($today_appointments)): ?>
                    <div class="empty-state small">No appointments scheduled.</div>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Patient</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th style="text-align:right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($today_appointments as $appt): ?>
                                    <tr>
                                        <td><strong><?php echo date('g:i A', strtotime($appt['appointment_time'])); ?></strong></td>
                                        <td><?php echo htmlspecialchars($appt['last_name'] . ', ' . $appt['first_name']); ?></td>
                                        <td><?php echo htmlspecialchars($appt['reason'] ?: '—'); ?></td>
                                        <td>
                                            <span class="badge appt-status-<?php echo htmlspecialchars($appt['status'] ?? ''); ?>">
                                                <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $appt['status'] ?? ''))); ?>
                                            </span>
                                        </td>
                                        <td style="text-align:right;">
                                            <a href="<?php echo BASE_URL; ?>/pages/doctor/calendar.php?view=day&amp;date=<?php echo $today; ?>" class="btn btn-outline btn-sm">Details</a>
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

    <!-- Two-column section -->
    <div class="dashboard-two-col">

        <!-- Recent Consultations -->
        <div class="card">
            <div class="card-header">
                <h3>Recent Consultations</h3>
                <a href="<?php echo BASE_URL; ?>/pages/doctor/patients.php" class="card-header-link">View all patients →</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recent_consultations)): ?>
                    <div class="empty-state small">No recent consultations recorded.</div>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Patient</th>
                                    <th>Chief Complaint</th>
                                    <th>Priority</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_consultations as $consultation): ?>
                                    <tr>
                                        <td>
                                            <span style="font-weight:600;"><?php echo date('M d, Y', strtotime($consultation['consultation_date'])); ?></span>
                                            <div style="font-size:0.75rem;color:var(--slate-500);"><?php echo date('h:i A', strtotime($consultation['consultation_date'])); ?></div>
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($consultation['first_name'] . ' ' . $consultation['last_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars(substr($consultation['chief_complaint'], 0, 45)) . (strlen($consultation['chief_complaint']) > 45 ? '...' : ''); ?></td>
                                        <td>
                                            <?php $pl = $consultation['priority_level'] ?? 'medium'; ?>
                                            <span class="badge priority-<?php echo $pl; ?>"><?php echo ucfirst($pl); ?></span>
                                        </td>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>/pages/doctor/view_consultation.php?id=<?php echo $consultation['consultation_id']; ?>" class="btn btn-outline btn-sm">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- High Priority / Attention Needed -->
        <div class="card">
            <div class="card-header">
                <h3>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="var(--danger)" stroke-width="2" style="margin-right:6px;vertical-align:-2px"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    Priority Attention
                </h3>
                <a href="<?php echo BASE_URL; ?>/pages/doctor/patients.php?priority=high" class="card-header-link">Filter list →</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($urgent_patients)): ?>
                    <div class="empty-state small">No critical or high priority patients at this time.</div>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Priority</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($urgent_patients as $p): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name']); ?></strong>
                                            <?php if (!empty($p['priority_reason'])): ?>
                                                <div style="font-size:0.75rem;color:var(--slate-500);"><?php echo htmlspecialchars(substr($p['priority_reason'], 0, 35)); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge priority-<?php echo $p['priority_level']; ?>"><?php echo ucfirst($p['priority_level'] ?? ''); ?></span>
                                        </td>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>/pages/doctor/view_patient.php?id=<?php echo $p['patient_id']; ?>" class="action-link">Open</a>
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

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
