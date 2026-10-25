<?php
/**
 * MedCare System - Doctor: Appointment Calendar
 * Plots the doctor's patient schedule on a month grid, grouped by appointment date.
 */
require_once __DIR__ . '/../../includes/auth.php';
requireRole('doctor');

require_once __DIR__ . '/../../config/config.php';

$database = new Database();
$conn = $database->getConnection();

$doctor_id = (int)$_SESSION['user_id'];
$today = date('Y-m-d');

/**
 * Build a calendar URL preserving the current filters
 */
function calendar_url(array $overrides = []) {
    global $year, $month, $selected_date, $status_filter, $view;
    $params = array_merge([
        'year'   => $year,
        'month'  => $month,
        'date'   => $selected_date,
        'status' => $status_filter,
        'view'   => $view,
    ], $overrides);
    return BASE_URL . '/pages/doctor/calendar.php?' . http_build_query($params);
}

/**
 * Fetch appointments joined with patient (and latest priority) information.
 * The query is ALWAYS scoped to the logged-in doctor (server-side authorization),
 * so no request parameter can expose another doctor's schedule.
 */
function fetch_appointments($conn, $doctor_id, $start_date, $end_date, $status_filter) {
    $sql = "
        SELECT a.*,
               p.first_name, p.last_name, p.contact_number,
               u.full_name AS doctor_name,
               pp.priority_level
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        JOIN users u ON a.doctor_id = u.user_id
        LEFT JOIN patient_priority pp ON pp.priority_id = (
            SELECT priority_id FROM patient_priority
            WHERE patient_id = p.patient_id
            ORDER BY assigned_at DESC, priority_id DESC
            LIMIT 1
        )
        WHERE a.appointment_date BETWEEN ? AND ?
          AND a.doctor_id = ?";
    $params = [$start_date, $end_date, $doctor_id];

    if ($status_filter !== 'all') {
        $sql .= " AND a.status = ?";
        $params[] = $status_filter;
    }

    $sql .= " ORDER BY a.appointment_date, a.appointment_time, a.appointment_id";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Count helper for the summary cards
 */
function appointment_count($conn, $sql, $params = []) {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

/* ---------------------------------------------------------------- Filters */
$year  = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
if ($month < 1 || $month > 12)    { $month = (int)date('n'); }
if ($year < 2000 || $year > 2100) { $year = (int)date('Y'); }

$selected_date = (isset($_GET['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date']))
    ? $_GET['date'] : $today;

$valid_statuses = ['scheduled', 'confirmed', 'completed', 'cancelled', 'no_show'];
$status_filter  = (isset($_GET['status']) && in_array($_GET['status'], $valid_statuses, true))
    ? $_GET['status'] : 'all';

// Calendar view: month | week | day
$valid_views = ['month', 'week', 'day'];
$view        = (isset($_GET['view']) && in_array($_GET['view'], $valid_views, true))
    ? $_GET['view'] : 'month';

/* --------------------------------------------- Status updates (POST/PRG) */
// Allowed status transitions keep the schedule consistent
$allowed_transitions = [
    'scheduled' => ['scheduled', 'confirmed', 'completed', 'cancelled', 'no_show'],
    'confirmed' => ['confirmed', 'completed', 'cancelled', 'no_show'],
    'completed' => ['completed'],
    'cancelled' => ['cancelled', 'scheduled'],
    'no_show'   => ['no_show', 'completed', 'scheduled'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action         = $_POST['action'] ?? '';
    $appointment_id = (int)($_POST['appointment_id'] ?? 0);
    $redirect_date  = (isset($_POST['redirect_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['redirect_date']))
        ? $_POST['redirect_date'] : $today;

    try {
        // Ownership is verified server-side on every mutation so a doctor can
        // never change or delete an appointment belonging to another doctor.
        if ($action === 'update_status' && $appointment_id > 0) {
            $new_status = $_POST['status'] ?? '';

            $stmt = $conn->prepare("SELECT status FROM appointments WHERE appointment_id = ? AND doctor_id = ?");
            $stmt->execute([$appointment_id, $doctor_id]);
            $current = $stmt->fetch();

            $permitted = $current ? ($allowed_transitions[$current['status']] ?? []) : [];
            if ($current && in_array($new_status, $permitted, true)) {
                $stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE appointment_id = ? AND doctor_id = ?");
                $stmt->execute([$new_status, $appointment_id, $doctor_id]);
            }
        } elseif ($action === 'delete' && $appointment_id > 0) {
            $stmt = $conn->prepare("DELETE FROM appointments WHERE appointment_id = ? AND doctor_id = ?");
            $stmt->execute([$appointment_id, $doctor_id]);
        }
    } catch (PDOException $e) {
        error_log("Appointment calendar action error: " . $e->getMessage());
    }

    header('Location: ' . calendar_url(['date' => $redirect_date]));
    exit();
}

/* -------------------------------------------------------- Calendar geometry */
$first_of_month = sprintf('%04d-%02d-01', $year, $month);
$grid_start = date('Y-m-d', strtotime($first_of_month . ' -' . (int)date('w', strtotime($first_of_month)) . ' days'));
$grid_end   = date('Y-m-d', strtotime($grid_start . ' +41 days'));

// Week containing the selected date (Sunday-based, matching the month grid)
$week_start = date('Y-m-d', strtotime($selected_date . ' -' . (int)date('w', strtotime($selected_date)) . ' days'));
$week_end   = date('Y-m-d', strtotime($week_start . ' +6 days'));

// Range actually plotted for the active view
if ($view === 'week') {
    $range_start = $week_start;
    $range_end   = $week_end;
    $cell_count  = 7;
    $cell_origin = $week_start;
} elseif ($view === 'day') {
    $range_start = $selected_date;
    $range_end   = $selected_date;
    $cell_count  = 1;
    $cell_origin = $selected_date;
} else {
    $range_start = $grid_start;
    $range_end   = $grid_end;
    $cell_count  = 42;
    $cell_origin = $grid_start;
}

$prev_month = ($month === 1) ? 12 : $month - 1;
$prev_year  = ($month === 1) ? $year - 1 : $year;
$next_month = ($month === 12) ? 1 : $month + 1;
$next_year  = ($month === 12) ? $year + 1 : $year;

// Navigation targets depend on the active view
if ($view === 'week') {
    $nav_prev = ['date' => date('Y-m-d', strtotime($week_start . ' -7 days')), 'view' => 'week'];
    $nav_next = ['date' => date('Y-m-d', strtotime($week_start . ' +7 days')), 'view' => 'week'];
    $period_label = date('M j', strtotime($week_start)) . ' - ' . date('M j, Y', strtotime($week_end));
} elseif ($view === 'day') {
    $nav_prev = ['date' => date('Y-m-d', strtotime($selected_date . ' -1 day')), 'view' => 'day'];
    $nav_next = ['date' => date('Y-m-d', strtotime($selected_date . ' +1 day')), 'view' => 'day'];
    $period_label = date('l, F j, Y', strtotime($selected_date));
} else {
    $nav_prev = ['year' => $prev_year, 'month' => $prev_month];
    $nav_next = ['year' => $next_year, 'month' => $next_month];
    $period_label = date('F Y', mktime(0, 0, 0, $month, 1, $year));
}

/* ------------------------------------------------------------- Fetch data */
try {
    $month_appointments = fetch_appointments($conn, $doctor_id, $range_start, $range_end, $status_filter);
} catch (PDOException $e) {
    error_log("Appointment calendar fetch error: " . $e->getMessage());
    $month_appointments = [];
}

$appointments_by_date = [];
foreach ($month_appointments as $appointment) {
    $appointments_by_date[$appointment['appointment_date']][] = $appointment;
}

// Agenda for the selected day: fetched independently of the plotted range so
// navigating to a date outside the visible grid still shows its appointments.
// (The status filter is intentionally not applied here so nothing is hidden.)
try {
    $day_appointments = fetch_appointments($conn, $doctor_id, $selected_date, $selected_date, 'all');
} catch (PDOException $e) {
    error_log("Appointment calendar day agenda error: " . $e->getMessage());
    $day_appointments = [];
}

/* -------------------------------------------------------------- Stat cards */
$today_count = appointment_count(
    $conn,
    "SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND status <> 'cancelled'",
    [$doctor_id, $today]
);

$upcoming_count = appointment_count(
    $conn,
    "SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND appointment_date > ? AND status IN ('scheduled','confirmed')",
    [$doctor_id, $today]
);

$month_completed = appointment_count(
    $conn,
    "SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND status = 'completed' AND MONTH(appointment_date) = ? AND YEAR(appointment_date) = ?",
    [$doctor_id, $month, $year]
);

$weekday_labels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
$max_events_per_day = 3;

// The layout is rendered only after every redirect has been issued,
// so the POST/redirect/GET cycle never sends headers twice.
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-container">

    <?php if (isset($_GET['saved']) && $_GET['saved'] === '1'): ?>
        <div class="alert alert-success">
            <svg class="alert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"></path></svg>
            <div><?php echo isset($_GET['updated']) ? 'Appointment updated successfully.' : 'Appointment scheduled successfully.'; ?></div>
        </div>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title-group">
                <div class="page-title-icon doctor">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                </div>
                <div>
                    <h1 class="page-title">Appointment Calendar</h1>
                    <p class="page-subtitle">Your patient schedule plotted by appointment date — <?php echo date('l, F j, Y'); ?></p>
                </div>
            </div>
            <div class="page-header-actions">
                <a href="<?php echo calendar_url(['year' => (int)date('Y'), 'month' => (int)date('n'), 'date' => $today]); ?>" class="btn btn-outline btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="margin-right:6px;vertical-align:-3px"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                    Today
                </a>
                <a href="<?php echo BASE_URL; ?>/pages/doctor/new_appointment.php?date=<?php echo $today; ?>" class="btn btn-primary btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="margin-right:6px;vertical-align:-3px"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path></svg>
                    New Appointment
                </a>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="stats-grid">
        <div class="stat-card primary">
            <div class="stat-icon">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $today_count; ?></div>
                <div class="stat-label">Appointments Today</div>
            </div>
        </div>
        <div class="stat-card info">
            <div class="stat-icon">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $upcoming_count; ?></div>
                <div class="stat-label">Upcoming</div>
            </div>
        </div>
        <div class="stat-card success">
            <div class="stat-icon">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"></path></svg>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $month_completed; ?></div>
                <div class="stat-label">Completed in <?php echo htmlspecialchars(date('F', mktime(0, 0, 0, $month, 1, $year))); ?></div>
            </div>
        </div>
        <div class="stat-card teal">
            <div class="stat-icon">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo count($day_appointments); ?></div>
                <div class="stat-label">On <?php echo htmlspecialchars(date('M j', strtotime($selected_date))); ?></div>
            </div>
        </div>
    </div>

<!-- Calendar Toolbar -->
    <div class="card calendar-card">
        <div class="calendar-toolbar">
            <div class="calendar-nav">
                <a class="btn btn-outline btn-sm"
                   href="<?php echo calendar_url($nav_prev); ?>"
                   title="Previous period" aria-label="Previous period">&lsaquo;</a>
                <h2 class="calendar-month-label"><?php echo htmlspecialchars($period_label); ?></h2>
                <a class="btn btn-outline btn-sm"
                   href="<?php echo calendar_url($nav_next); ?>"
                   title="Next period" aria-label="Next period">&rsaquo;</a>
            </div>

            <div class="calendar-filters">
                <!-- View switcher: month / week / day -->
                <div class="filter-group">
                    <span class="filter-label">View</span>
                    <?php foreach (['month' => 'Month', 'week' => 'Week', 'day' => 'Day'] as $view_key => $view_label): ?>
                        <a class="filter-pill <?php echo $view === $view_key ? 'active' : ''; ?>"
                           href="<?php echo calendar_url(['view' => $view_key]); ?>">
                            <?php echo $view_label; ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- Status filter -->
                <div class="filter-group">
                    <span class="filter-label">Status</span>
                    <a class="filter-pill <?php echo $status_filter === 'all' ? 'active' : ''; ?>"
                       href="<?php echo calendar_url(['status' => 'all']); ?>">All</a>
                    <?php foreach ($valid_statuses as $status_option): ?>
                        <a class="filter-pill <?php echo $status_filter === $status_option ? 'active' : ''; ?>"
                           href="<?php echo calendar_url(['status' => $status_option]); ?>">
                            <?php echo ucwords(str_replace('_', ' ', $status_option)); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Weekday labels (day view shows the agenda card only) -->
        <?php if ($view !== 'day'): ?>
            <div class="calendar-grid calendar-weekdays">
                <?php foreach ($weekday_labels as $weekday): ?>
                    <div class="calendar-weekday"><?php echo $weekday; ?></div>
                <?php endforeach; ?>
            </div>

            <!-- Month / week grid -->
            <div class="calendar-grid calendar-days <?php echo $view === 'week' ? 'calendar-week' : ''; ?>">
            <?php
            $visible_events = ($view === 'week') ? PHP_INT_MAX : $max_events_per_day + 1;
            for ($cell = 0; $cell < $cell_count; $cell++) {
                $cell_date = date('Y-m-d', strtotime($cell_origin . " +{$cell} days"));
                $is_current_month = ($view === 'month') && (int)date('n', strtotime($cell_date)) === $month;
                $is_today   = ($cell_date === $today);
                $is_selected = ($cell_date === $selected_date);
                $cell_events = isset($appointments_by_date[$cell_date]) ? $appointments_by_date[$cell_date] : [];

                $cell_classes = 'calendar-day';
                if (!$is_current_month && $view === 'month') { $cell_classes .= ' outside-month'; }
                if ($is_today)          { $cell_classes .= ' today'; }
                if ($is_selected)       { $cell_classes .= ' selected'; }
            ?>
                <div class="<?php echo $cell_classes; ?>">
                    <a class="calendar-day-number" href="<?php echo calendar_url(['date' => $cell_date]); ?>"
                       title="View appointments for <?php echo htmlspecialchars(date('F j, Y', strtotime($cell_date))); ?>">
                        <?php if ($view === 'week') { echo htmlspecialchars(date('D', strtotime($cell_date))) . ' '; } ?>
                        <?php echo (int)date('j', strtotime($cell_date)); ?>
                        <?php if ($cell_events): ?>
                            <span class="calendar-day-count"><?php echo count($cell_events); ?></span>
                        <?php endif; ?>
                    </a>

                    <div class="calendar-events">
                        <?php foreach (array_slice($cell_events, 0, $visible_events) as $event): ?>
                            <a class="calendar-event status-<?php echo htmlspecialchars($event['status'] ?? ''); ?>"
                               href="<?php echo calendar_url(['date' => $cell_date, 'view' => 'day']); ?>"
                               title="<?php echo htmlspecialchars(
                                   date('g:i A', strtotime($event['appointment_time'])) . ' - ' .
                                   $event['last_name'] . ', ' . $event['first_name'] . ' (' . str_replace('_', ' ', $event['status'] ?? '') . ')'
                               ); ?>">
                                <span class="calendar-event-time"><?php echo date('g:i A', strtotime($event['appointment_time'])); ?></span>
                                <span class="calendar-event-name"><?php echo htmlspecialchars($event['last_name'] . ', ' . $event['first_name']); ?></span>
                            </a>
                        <?php endforeach; ?>

                        <?php if (count($cell_events) > $visible_events): ?>
                            <a class="calendar-more" href="<?php echo calendar_url(['date' => $cell_date]); ?>">
                                +<?php echo count($cell_events) - $visible_events; ?> more
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php } ?>
            </div>
        <?php else: ?>
            <div class="empty-state small">
                <p>Daily schedule for <?php echo htmlspecialchars(date('l, F j, Y', strtotime($selected_date))); ?>.</p>
            </div>
        <?php endif; ?>

        <!-- Legend -->
        <div class="calendar-legend">
            <span class="legend-title">Status</span>
            <span class="legend-item"><span class="legend-dot scheduled"></span>Scheduled</span>
            <span class="legend-item"><span class="legend-dot confirmed"></span>Confirmed</span>
            <span class="legend-item"><span class="legend-dot completed"></span>Completed</span>
            <span class="legend-item"><span class="legend-dot cancelled"></span>Cancelled</span>
            <span class="legend-item"><span class="legend-dot no_show"></span>No Show</span>
        </div>
    </div>

<!-- Selected Day Agenda -->
    <div class="card">
        <div class="card-header">
            <h3>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="var(--primary)" stroke-width="2" style="margin-right:6px;vertical-align:-2px"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                <?php echo htmlspecialchars(date('l, F j, Y', strtotime($selected_date))); ?>
                <?php if ($selected_date === $today): ?>
                    <span class="badge badge-primary" style="margin-left:0.5rem;">Today</span>
                <?php endif; ?>
            </h3>
            <a href="<?php echo BASE_URL; ?>/pages/doctor/new_appointment.php?date=<?php echo $selected_date; ?>" class="card-header-link">+ Add appointment</a>
        </div>
        <div class="card-body p-0">
            <?php if (empty($day_appointments)): ?>
                <div class="empty-state small">
                    <p>No appointments scheduled for this day.</p>
                    <a href="<?php echo BASE_URL; ?>/pages/doctor/new_appointment.php?date=<?php echo $selected_date; ?>" class="btn btn-primary btn-sm">Schedule Appointment</a>
                </div>
            <?php elseif ($view === 'day'): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Patient</th>
                                <th>Type</th>
                                <th>Reason</th>
                                <th>Status</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($day_appointments as $appt): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo date('g:i A', strtotime($appt['appointment_time'])); ?></strong>
                                        <div style="font-size:0.75rem;color:var(--slate-500);">
                                            <?php echo (int)$appt['duration_minutes']; ?> min
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($appt['last_name'] . ', ' . $appt['first_name']); ?></td>
                                    <td><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $appt['appointment_type'] ?? ''))); ?></td>
                                    <td><?php echo htmlspecialchars($appt['reason'] ?: '—'); ?></td>
                                    <td>
                                        <span class="badge appt-status-<?php echo htmlspecialchars($appt['status'] ?? ''); ?>">
                                            <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $appt['status'] ?? ''))); ?>
                                        </span>
                                    </td>
                                    <td style="text-align:right;">
                                        <div class="appt-actions">
                                            <?php if (in_array($appt['status'], ['scheduled', 'cancelled', 'no_show'], true)): ?>
                                                <form method="POST" action="">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="appointment_id" value="<?php echo (int)$appt['appointment_id']; ?>">
                                                    <input type="hidden" name="status" value="confirmed">
                                                    <input type="hidden" name="redirect_date" value="<?php echo $selected_date; ?>">
                                                    <button type="submit" class="btn btn-outline btn-sm">Confirm</button>
                                                </form>
                                            <?php endif; ?>
                                            <?php if (in_array($appt['status'], ['scheduled', 'confirmed'], true)): ?>
                                                <form method="POST" action="">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="appointment_id" value="<?php echo (int)$appt['appointment_id']; ?>">
                                                    <input type="hidden" name="status" value="completed">
                                                    <input type="hidden" name="redirect_date" value="<?php echo $selected_date; ?>">
                                                    <button type="submit" class="btn btn-primary btn-sm">Complete</button>
                                                </form>
                                                <form method="POST" action="">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="appointment_id" value="<?php echo (int)$appt['appointment_id']; ?>">
                                                    <input type="hidden" name="status" value="no_show">
                                                    <input type="hidden" name="redirect_date" value="<?php echo $selected_date; ?>">
                                                    <button type="submit" class="btn btn-secondary btn-sm">No Show</button>
                                                </form>
                                            <?php endif; ?>
                                            <a href="<?php echo BASE_URL; ?>/pages/doctor/new_appointment.php?id=<?php echo (int)$appt['appointment_id']; ?>" class="btn btn-outline btn-sm">Edit</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Patient</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($day_appointments as $appt): ?>
                                <?php
                                $initials = strtoupper(substr($appt['first_name'], 0, 1) . substr($appt['last_name'], 0, 1));
                                $end_time = date('g:i A', strtotime($appt['appointment_time']) + ($appt['duration_minutes'] * 60));
                                $appt_id = (int)$appt['appointment_id'];
                                $can_confirm  = in_array($appt['status'], ['scheduled', 'cancelled', 'no_show'], true);
                                $can_progress = in_array($appt['status'], ['scheduled', 'confirmed'], true);
                                ?>
                                <tr>
                                    <td style="white-space:nowrap;">
                                        <strong><?php echo date('g:i A', strtotime($appt['appointment_time'])); ?></strong>
                                        <div style="font-size:0.75rem; color:var(--slate-400);">to <?php echo $end_time; ?> &bull; <?php echo (int)$appt['duration_minutes']; ?> min</div>
                                    </td>
                                    <td>
                                        <div style="display:flex; align-items:center; gap:0.75rem;">
                                            <div style="width:36px; height:36px; border-radius:50%; background:var(--primary-light); color:var(--primary); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.875rem; flex-shrink:0;">
                                                <?php echo $initials; ?>
                                            </div>
                                            <div>
                                                <strong><?php echo htmlspecialchars($appt['last_name'] . ', ' . $appt['first_name']); ?></strong>
                                                <div style="font-size:0.75rem; color:var(--slate-400);">
                                                    ID: #<?php echo str_pad($appt['patient_id'], 4, '0', STR_PAD_LEFT); ?>
                                                    <?php if (!empty($appt['priority_level'])): ?>
                                                        &bull; <span class="badge priority-<?php echo htmlspecialchars($appt['priority_level'] ?? ''); ?>"><?php echo ucfirst($appt['priority_level'] ?? ''); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if (!empty($appt['reason'])): ?>
                                                    <div style="font-size:0.75rem; color:var(--slate-500);"><?php echo htmlspecialchars($appt['reason'] ?? ''); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo ucwords(str_replace('_', ' ', $appt['appointment_type'] ?? '')); ?></td>
                                    <td>
                                        <span class="badge appt-status-<?php echo htmlspecialchars($appt['status'] ?? ''); ?>">
                                            <?php echo ucwords(str_replace('_', ' ', $appt['status'] ?? '')); ?>
                                        </span>
                                    </td>
                                    <td style="text-align:right;" class="appt-actions">
                                        <?php if ($can_confirm): ?>
                                            <form method="POST" action="" style="display:inline;">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="appointment_id" value="<?php echo $appt_id; ?>">
                                                <input type="hidden" name="status" value="confirmed">
                                                <input type="hidden" name="redirect_date" value="<?php echo $selected_date; ?>">
                                                <button type="submit" class="btn btn-outline btn-sm">Confirm</button>
                                            </form>
                                        <?php endif; ?>
                                        <?php if ($can_progress): ?>
                                            <form method="POST" action="" style="display:inline;">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="appointment_id" value="<?php echo $appt_id; ?>">
                                                <input type="hidden" name="status" value="completed">
                                                <input type="hidden" name="redirect_date" value="<?php echo $selected_date; ?>">
                                                <button type="submit" class="btn btn-primary btn-sm">Complete</button>
                                            </form>
                                            <form method="POST" action="" style="display:inline;">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="appointment_id" value="<?php echo $appt_id; ?>">
                                                <input type="hidden" name="status" value="no_show">
                                                <input type="hidden" name="redirect_date" value="<?php echo $selected_date; ?>">
                                                <button type="submit" class="btn btn-secondary btn-sm">No Show</button>
                                            </form>
                                            <form method="POST" action="" style="display:inline;">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="appointment_id" value="<?php echo $appt_id; ?>">
                                                <input type="hidden" name="status" value="cancelled">
                                                <input type="hidden" name="redirect_date" value="<?php echo $selected_date; ?>">
                                                <button type="submit" class="btn btn-outline btn-sm">Cancel</button>
                                            </form>
                                        <?php endif; ?>
                                        <a href="<?php echo BASE_URL; ?>/pages/doctor/new_appointment.php?id=<?php echo $appt_id; ?>" class="btn btn-outline btn-sm">Edit</a>
                                        <a href="<?php echo BASE_URL; ?>/pages/doctor/view_patient.php?id=<?php echo (int)$appt['patient_id']; ?>" class="btn btn-outline btn-sm">Record</a>
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