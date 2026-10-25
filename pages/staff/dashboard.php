<?php
/**
 * MedCare System - Staff Dashboard
 */
require_once __DIR__ . '/../../includes/auth.php';
requireRole('staff');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/header.php';

$database = new Database();
$conn = $database->getConnection();

// Statistics
$total_patients   = $conn->query("SELECT COUNT(*) as c FROM patients")->fetch()['c'];
$total_inventory  = $conn->query("SELECT COUNT(*) as c FROM inventory")->fetch()['c'];
$low_stock_items  = $conn->query("SELECT COUNT(*) as c FROM inventory WHERE quantity <= minimum_stock_level")->fetch()['c'];
$expired_items    = $conn->query("SELECT COUNT(*) as c FROM inventory WHERE expiration_date < CURDATE()")->fetch()['c'];
$total_consults   = $conn->query("SELECT COUNT(*) as c FROM consultations WHERE DATE(consultation_date) = CURDATE()")->fetch()['c'];
$pending_rx       = $conn->query("SELECT COUNT(*) as c FROM prescriptions WHERE status = 'pending'")->fetch()['c'];

// Low stock alerts
$low_stock_alerts = $conn->query("
    SELECT * FROM inventory 
    WHERE quantity <= minimum_stock_level 
    ORDER BY quantity ASC 
    LIMIT 5
")->fetchAll();

// Expired items
$expired_items_list = $conn->query("
    SELECT * FROM inventory 
    WHERE expiration_date < CURDATE() 
    ORDER BY expiration_date DESC 
    LIMIT 5
")->fetchAll();

// Recent patients
$recent_patients = $conn->query("
    SELECT p.*, pp.priority_level
    FROM patients p 
    LEFT JOIN patient_priority pp ON pp.priority_id = (
        SELECT pp2.priority_id FROM patient_priority pp2
        WHERE pp2.patient_id = p.patient_id
        ORDER BY pp2.assigned_at DESC, pp2.priority_id DESC LIMIT 1
    )
    ORDER BY p.created_at DESC 
    LIMIT 5
")->fetchAll();

/* ------------------------------------------------------------------ Analytics */
require_once __DIR__ . '/../../includes/charts.php';

// 1. Appointments per day: last 7 days, today, next 7 days
$appt_rows = $conn->query("
    SELECT appointment_date AS d, COUNT(*) AS c
    FROM appointments
    WHERE status <> 'cancelled'
      AND appointment_date BETWEEN DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    GROUP BY appointment_date
")->fetchAll();
$appt_by_day = [];
foreach ($appt_rows as $r) {
    $appt_by_day[$r['d']] = (int)$r['c'];
}
$appt_points = [];
$appt_table = [];
$appt_total = 0;
for ($i = -7; $i <= 7; $i++) {
    $day = date('Y-m-d', strtotime("$i days"));
    $count = $appt_by_day[$day] ?? 0;
    $appt_total += $count;
    $appt_points[] = [
        'label' => $i === 0 ? 'Today' : date('D', strtotime($day)),
        'sub' => date('j', strtotime($day)),
        'value' => $count,
        'tip' => date('l, M j', strtotime($day)),
        'highlight' => $i === 0,
    ];
    $appt_table[] = [date('D, M j', strtotime($day)) . ($i === 0 ? ' (today)' : ''), $count];
}

// 2. Inventory stock health - every active item counted once, worst problem first
$expiry_days = (int)EXPIRY_WARNING_DAYS;
$health = $conn->query("
    SELECT
        SUM(CASE WHEN expiration_date IS NOT NULL AND expiration_date < CURDATE() THEN 1 ELSE 0 END) AS expired,
        SUM(CASE WHEN (expiration_date IS NULL OR expiration_date >= CURDATE()) AND quantity = 0 THEN 1 ELSE 0 END) AS out_of_stock,
        SUM(CASE WHEN (expiration_date IS NULL OR expiration_date >= CURDATE()) AND quantity > 0 AND quantity <= minimum_stock_level THEN 1 ELSE 0 END) AS low_stock,
        SUM(CASE WHEN expiration_date >= CURDATE() AND expiration_date <= DATE_ADD(CURDATE(), INTERVAL $expiry_days DAY)
                  AND quantity > minimum_stock_level THEN 1 ELSE 0 END) AS expiring,
        SUM(CASE WHEN (expiration_date IS NULL OR expiration_date > DATE_ADD(CURDATE(), INTERVAL $expiry_days DAY))
                  AND quantity > minimum_stock_level THEN 1 ELSE 0 END) AS healthy
    FROM inventory
    WHERE is_active = 1
")->fetch();
$icon_ok = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>';
$icon_warn = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>';
$icon_clock = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>';
$icon_x = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';
$health_rows = [
    ['label' => 'Healthy stock',       'value' => (int)$health['healthy'],      'color' => 'var(--viz-good)',     'icon' => $icon_ok],
    ['label' => "Expiring ≤ {$expiry_days} days", 'value' => (int)$health['expiring'], 'color' => 'var(--viz-warning)', 'icon' => $icon_clock],
    ['label' => 'Low stock',           'value' => (int)$health['low_stock'],    'color' => 'var(--viz-serious)',  'icon' => $icon_warn],
    ['label' => 'Out of stock',        'value' => (int)$health['out_of_stock'], 'color' => 'var(--viz-critical)', 'icon' => $icon_x],
    ['label' => 'Expired',             'value' => (int)$health['expired'],      'color' => 'var(--viz-critical)', 'icon' => $icon_x],
];
$health_total = array_sum(array_column($health_rows, 'value'));

// 3. Prescriptions by status
$rx = $conn->query("SELECT status, COUNT(*) AS c FROM prescriptions GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
$rx_rows = [];
foreach (['pending' => 'Pending', 'dispensed' => 'Dispensed', 'cancelled' => 'Cancelled'] as $key => $label) {
    $rx_rows[] = ['label' => $label, 'value' => (int)($rx[$key] ?? 0), 'color' => 'var(--viz-series-1)'];
}
$rx_total = array_sum(array_column($rx_rows, 'value'));

// 4. Items closest to (or below) their minimum stock level
$lowest = $conn->query("
    SELECT item_name, quantity, minimum_stock_level, unit
    FROM inventory
    WHERE is_active = 1 AND minimum_stock_level > 0
      AND (expiration_date IS NULL OR expiration_date >= CURDATE())
    ORDER BY quantity / minimum_stock_level ASC, item_name
    LIMIT 6
")->fetchAll();
$lowest_rows = [];
foreach ($lowest as $item) {
    $below = (int)$item['quantity'] <= (int)$item['minimum_stock_level'];
    $lowest_rows[] = [
        'label' => $item['item_name'],
        'value' => (int)$item['quantity'],
        'target' => (int)$item['minimum_stock_level'],
        'color' => $below ? 'var(--viz-critical)' : 'var(--viz-series-1)',
        'icon' => $below ? $icon_warn : '',
        'unit' => $item['unit'],
    ];
}

// 5. Patients by current priority (latest priority record per patient)
$prio = $conn->query("
    SELECT pp.priority_level, COUNT(*) AS c
    FROM patient_priority pp
    WHERE pp.priority_id = (
        SELECT pp2.priority_id FROM patient_priority pp2
        WHERE pp2.patient_id = pp.patient_id
        ORDER BY pp2.assigned_at DESC, pp2.priority_id DESC LIMIT 1
    )
    GROUP BY pp.priority_level
")->fetchAll(PDO::FETCH_KEY_PAIR);
$prio_rows = [];
foreach (['low' => ['Low', 'var(--viz-ord-1)'], 'medium' => ['Medium', 'var(--viz-ord-2)'], 'high' => ['High', 'var(--viz-ord-3)'], 'critical' => ['Critical', 'var(--viz-ord-4)']] as $key => [$label, $color]) {
    $prio_rows[] = ['label' => $label, 'value' => (int)($prio[$key] ?? 0), 'color' => $color];
}

?>

<div class="page-container">

    <!-- Page Header -->
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title-group">
                <div class="page-title-icon staff">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                </div>
                <div>
                    <h1 class="page-title">Staff Dashboard</h1>
                    <p class="page-subtitle">Welcome back, <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Staff'); ?> — <?php echo date('l, F j, Y'); ?></p>
                </div>
            </div>
            <div class="page-header-actions">
                <a href="<?php echo BASE_URL; ?>/pages/staff/new_patient.php" class="btn btn-primary btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="margin-right:6px;vertical-align:-3px"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    Add Patient
                </a>
                <a href="<?php echo BASE_URL; ?>/pages/staff/inventory.php" class="btn btn-outline btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="margin-right:6px;vertical-align:-3px"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7H4a2 2 0 00-2 2v6a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2z"/><path stroke-linecap="round" stroke-linejoin="round" d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/></svg>
                    Inventory
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
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $total_consults; ?></div>
                <div class="stat-label">Consultations Today</div>
            </div>
        </div>
        <div class="stat-card teal">
            <div class="stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7H4a2 2 0 00-2 2v6a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2z"/></svg>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $total_inventory; ?></div>
                <div class="stat-label">Inventory Items</div>
            </div>
        </div>
        <div class="stat-card <?php echo $low_stock_items > 0 ? 'warning' : 'success'; ?>">
            <div class="stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $low_stock_items; ?></div>
                <div class="stat-label">Low Stock Alerts</div>
            </div>
        </div>
        <div class="stat-card <?php echo $expired_items > 0 ? 'danger' : 'success'; ?>">
            <div class="stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $expired_items; ?></div>
                <div class="stat-label">Expired Items</div>
            </div>
        </div>
        <div class="stat-card info">
            <div class="stat-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?php echo $pending_rx; ?></div>
                <div class="stat-label">Active Prescriptions</div>
            </div>
        </div>
    </div>

    <!-- Analytics -->
    <section class="viz-root" aria-labelledby="analyticsTitle">
        <div class="viz-section-head">
            <h2 id="analyticsTitle">Analytics</h2>
            <span class="viz-section-note">Live from the database · updated <?php echo date('g:i A'); ?></span>
        </div>

        <div class="viz-grid">
            <!-- Appointments per day -->
            <article class="viz-card viz-span-2">
                <header class="viz-card-head">
                    <div>
                        <h3>Appointments per day</h3>
                        <p>Past 7 days and next 7 days, all doctors (cancelled excluded)</p>
                    </div>
                    <div class="viz-figure"><strong><?php echo $appt_total; ?></strong><span>in this period</span></div>
                </header>
                <?php echo chart_columns($appt_points, 'appointment', 'appointments', 'Appointments per day over the past and next 7 days'); ?>
                <?php echo chart_table(['Day', 'Appointments'], $appt_table); ?>
            </article>

            <!-- Inventory health -->
            <article class="viz-card">
                <header class="viz-card-head">
                    <div>
                        <h3>Inventory stock health</h3>
                        <p>Active items, each counted once under its most urgent issue</p>
                    </div>
                    <div class="viz-figure"><strong><?php echo $health_total; ?></strong><span>items</span></div>
                </header>
                <?php echo chart_bars($health_rows, 'item', 'items', 'Inventory items by stock health', true); ?>
                <?php echo chart_table(['Status', 'Items'], array_map(fn($r) => [$r['label'], $r['value']], $health_rows)); ?>
                <a class="viz-card-link" href="<?php echo BASE_URL; ?>/pages/staff/stock_alerts.php">Open stock alerts →</a>
            </article>

            <!-- Prescriptions -->
            <article class="viz-card">
                <header class="viz-card-head">
                    <div>
                        <h3>Prescriptions by status</h3>
                        <p>All prescriptions on record</p>
                    </div>
                    <div class="viz-figure"><strong><?php echo $rx_total; ?></strong><span>total</span></div>
                </header>
                <?php echo chart_bars($rx_rows, 'prescription', 'prescriptions', 'Prescriptions by status', true); ?>
                <?php echo chart_table(['Status', 'Prescriptions'], array_map(fn($r) => [$r['label'], $r['value']], $rx_rows)); ?>
                <a class="viz-card-link" href="<?php echo BASE_URL; ?>/pages/staff/prescriptions.php">Go to dispensing →</a>
            </article>

            <!-- Lowest stock -->
            <article class="viz-card">
                <header class="viz-card-head">
                    <div>
                        <h3>Lowest stock vs minimum level</h3>
                        <p>6 items closest to their reorder point · shown as in stock / minimum</p>
                    </div>
                </header>
                <?php if ($lowest_rows): ?>
                    <?php echo chart_bars($lowest_rows, 'unit in stock', 'units in stock', 'Items with the lowest stock compared with their minimum level'); ?>
                    <div class="viz-legend" aria-hidden="true">
                        <span><i class="viz-key" style="background:var(--viz-series-1)"></i>Above minimum</span>
                        <span><i class="viz-key" style="background:var(--viz-critical)"></i>At or below minimum</span>
                        <span><i class="viz-key-target"></i>Minimum level</span>
                    </div>
                    <?php echo chart_table(['Item', 'In stock', 'Minimum'], array_map(fn($r) => [$r['label'], $r['value'] . ' ' . $r['unit'], $r['target']], $lowest_rows)); ?>
                <?php else: ?>
                    <p class="viz-empty">No active items with a minimum stock level.</p>
                <?php endif; ?>
            </article>

            <!-- Patient priority -->
            <article class="viz-card">
                <header class="viz-card-head">
                    <div>
                        <h3>Patients by priority</h3>
                        <p>Current priority assigned by doctors</p>
                    </div>
                    <div class="viz-figure"><strong><?php echo array_sum(array_column($prio_rows, 'value')); ?></strong><span>patients</span></div>
                </header>
                <?php echo chart_bars($prio_rows, 'patient', 'patients', 'Patients by current priority level', true); ?>
                <?php echo chart_table(['Priority', 'Patients'], array_map(fn($r) => [$r['label'], $r['value']], $prio_rows)); ?>
            </article>
        </div>

        <div class="viz-tooltip" id="vizTooltip" role="tooltip" hidden><strong></strong><span></span></div>
    </section>

    <!-- Quick Actions -->
    <div class="card">
        <div class="card-header">
            <h3>Quick Actions</h3>
        </div>
        <div class="card-body">
            <div class="quick-actions-grid">
                <a href="<?php echo BASE_URL; ?>/pages/staff/patients.php" class="quick-action-item primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Manage Patients
                </a>
                <a href="<?php echo BASE_URL; ?>/pages/staff/inventory.php" class="quick-action-item teal">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7H4a2 2 0 00-2 2v6a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2z"/></svg>
                    Inventory
                </a>
                <a href="<?php echo BASE_URL; ?>/pages/staff/prescriptions.php" class="quick-action-item warning">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Prescriptions
                </a>
                <a href="<?php echo BASE_URL; ?>/pages/staff/treatment_progress_list.php" class="quick-action-item success">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    Treatment Progress
                </a>
                <a href="<?php echo BASE_URL; ?>/pages/staff/communication.php" class="quick-action-item info">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    Communication
                </a>
                <a href="<?php echo BASE_URL; ?>/pages/staff/health_education.php" class="quick-action-item secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    Health Education
                </a>
                <a href="<?php echo BASE_URL; ?>/pages/staff/new_patient.php" class="quick-action-item danger">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                    New Patient
                </a>
                <a href="<?php echo BASE_URL; ?>/pages/staff/stock_alerts.php" class="quick-action-item warning">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    Stock Alerts
                </a>
            </div>
        </div>
    </div>

    <!-- Two-column section -->
    <div class="dashboard-two-col">

        <!-- Recent Patients -->
        <div class="card">
            <div class="card-header">
                <h3>Recent Patients</h3>
                <a href="<?php echo BASE_URL; ?>/pages/staff/patients.php" class="card-header-link">View all →</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recent_patients)): ?>
                    <div class="empty-state small">No patients found.</div>
                <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Gender</th>
                                <th>Priority</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_patients as $p): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name']); ?></strong></td>
                                <td><?php echo ucfirst($p['gender'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php $pl = $p['priority_level'] ?? 'medium'; ?>
                                    <span class="badge priority-<?php echo $pl; ?>"><?php echo ucfirst($pl); ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Alerts Column -->
        <div style="display:flex;flex-direction:column;gap:1.25rem;">

            <?php if (!empty($low_stock_alerts)): ?>
            <div class="card border-warning">
                <div class="card-header">
                    <h3>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="var(--warning)" stroke-width="2" style="margin-right:6px;vertical-align:-2px"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        Low Stock Alerts
                    </h3>
                    <a href="<?php echo BASE_URL; ?>/pages/staff/stock_alerts.php" class="card-header-link">Manage →</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-container">
                        <table>
                            <thead><tr><th>Item</th><th>Stock</th><th>Min</th></tr></thead>
                            <tbody>
                                <?php foreach ($low_stock_alerts as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['item_name'] ?? ''); ?></td>
                                    <td><span class="badge badge-danger"><?php echo $item['quantity']; ?> <?php echo htmlspecialchars($item['unit'] ?? ''); ?></span></td>
                                    <td><?php echo $item['minimum_stock_level']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($expired_items_list)): ?>
            <div class="card border-danger">
                <div class="card-header">
                    <h3>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="var(--danger)" stroke-width="2" style="margin-right:6px;vertical-align:-2px"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        Expired Items
                    </h3>
                    <a href="<?php echo BASE_URL; ?>/pages/staff/inventory.php" class="card-header-link">Manage →</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-container">
                        <table>
                            <thead><tr><th>Item</th><th>Expired</th></tr></thead>
                            <tbody>
                                <?php foreach ($expired_items_list as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['item_name'] ?? ''); ?></td>
                                    <td><span class="badge badge-danger"><?php echo date('M d, Y', strtotime($item['expiration_date'])); ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (empty($low_stock_alerts) && empty($expired_items_list)): ?>
            <div class="card">
                <div class="card-body">
                    <div class="alert alert-success">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        All inventory is in good shape! No alerts.
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<script>
// Chart tooltips (hover + keyboard focus). Text is inserted with textContent.
(function () {
    var tip = document.getElementById('vizTooltip');
    if (!tip) return;
    var valueEl = tip.querySelector('strong'), labelEl = tip.querySelector('span');
    function show(el) {
        var parts = (el.getAttribute('data-tip') || '').split('|');
        valueEl.textContent = parts[0] || '';
        labelEl.textContent = parts[1] || '';
        tip.hidden = false;
        var r = el.getBoundingClientRect(), t = tip.getBoundingClientRect();
        var left = Math.min(Math.max(8, r.left + r.width / 2 - t.width / 2), window.innerWidth - t.width - 8);
        var top = r.top - t.height - 8;
        if (top < 8) top = r.bottom + 8;
        tip.style.left = left + 'px';
        tip.style.top = top + 'px';
    }
    function hide() { tip.hidden = true; }
    document.querySelectorAll('.viz-root [data-tip]').forEach(function (el) {
        el.addEventListener('mouseenter', function () { show(el); });
        el.addEventListener('mouseleave', hide);
        el.addEventListener('focus', function () { show(el); });
        el.addEventListener('blur', hide);
    });
    window.addEventListener('scroll', hide, { passive: true });
})();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
