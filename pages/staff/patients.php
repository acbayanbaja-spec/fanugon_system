<?php
/**
 * MedCare System - Staff: Patient Management
 */
require_once __DIR__ . '/../../includes/auth.php';
requireRole('staff');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/header.php';

$database = new Database();
$conn = $database->getConnection();

// Handle search and priority filter
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$priority_filter = isset($_GET['priority']) ? sanitizeInput($_GET['priority']) : '';

$query = "
    SELECT p.*, pp.priority_level, pp.assigned_at 
    FROM patients p 
    LEFT JOIN patient_priority pp ON pp.priority_id = (
        SELECT pp2.priority_id FROM patient_priority pp2
        WHERE pp2.patient_id = p.patient_id
        ORDER BY pp2.assigned_at DESC, pp2.priority_id DESC LIMIT 1
    ) 
    WHERE 1=1
";
$params = [];

if ($search) {
    $query .= " AND (p.first_name LIKE ? OR p.last_name LIKE ? OR p.contact_number LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($priority_filter) {
    $query .= " AND pp.priority_level = ?";
    $params[] = $priority_filter;
}

$query .= " ORDER BY p.last_name ASC, p.first_name ASC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$patients = $stmt->fetchAll();

// Priority counts for filter pills
$p_counts = [
    'all' => $conn->query("SELECT COUNT(*) FROM patients")->fetchColumn(),
    'critical' => $conn->query("SELECT COUNT(*) FROM patient_priority pp WHERE pp.priority_level='critical' AND pp.priority_id = (SELECT pp2.priority_id FROM patient_priority pp2 WHERE pp2.patient_id = pp.patient_id ORDER BY pp2.assigned_at DESC, pp2.priority_id DESC LIMIT 1)")->fetchColumn(),
    'high' => $conn->query("SELECT COUNT(*) FROM patient_priority pp WHERE pp.priority_level='high' AND pp.priority_id = (SELECT pp2.priority_id FROM patient_priority pp2 WHERE pp2.patient_id = pp.patient_id ORDER BY pp2.assigned_at DESC, pp2.priority_id DESC LIMIT 1)")->fetchColumn(),
    'medium' => $conn->query("SELECT COUNT(*) FROM patient_priority pp WHERE pp.priority_level='medium' AND pp.priority_id = (SELECT pp2.priority_id FROM patient_priority pp2 WHERE pp2.patient_id = pp.patient_id ORDER BY pp2.assigned_at DESC, pp2.priority_id DESC LIMIT 1)")->fetchColumn(),
    'low' => $conn->query("SELECT COUNT(*) FROM patient_priority pp WHERE pp.priority_level='low' AND pp.priority_id = (SELECT pp2.priority_id FROM patient_priority pp2 WHERE pp2.patient_id = pp.patient_id ORDER BY pp2.assigned_at DESC, pp2.priority_id DESC LIMIT 1)")->fetchColumn(),
];
?>

<div class="page-container">

    <!-- Page Header -->
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title-group">
                <div class="page-title-icon staff">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="page-title">Patient Management</h1>
                    <p class="page-subtitle">Register, manage, and coordinate patient admission records.</p>
                </div>
            </div>
            <div class="page-header-actions">
                <a href="<?php echo BASE_URL; ?>/pages/staff/new_patient.php" class="btn btn-primary btn-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="margin-right:6px;vertical-align:-3px"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    Register New Patient
                </a>
            </div>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="card" style="margin-bottom: 1.5rem;">
        <div class="card-body" style="padding: 1.25rem;">
            <div style="display:flex; flex-wrap:wrap; gap:1rem; align-items:center; justify-content:space-between;">
                <!-- Search input -->
                <form method="GET" action="" style="display:flex; gap:0.5rem; flex:1; max-width:480px;">
                    <?php if ($priority_filter): ?>
                        <input type="hidden" name="priority" value="<?php echo htmlspecialchars($priority_filter); ?>">
                    <?php endif; ?>
                    <input type="text" name="search" placeholder="Search by patient name or phone number..." value="<?php echo htmlspecialchars($search); ?>" style="flex:1;">
                    <button type="submit" class="btn btn-primary btn-sm">Search</button>
                    <?php if ($search): ?>
                        <a href="<?php echo BASE_URL; ?>/pages/staff/patients.php<?php echo $priority_filter ? '?priority='.urlencode($priority_filter) : ''; ?>" class="btn btn-outline btn-sm">Clear</a>
                    <?php endif; ?>
                </form>

                <!-- Priority Filters -->
                <div style="display:flex; gap:0.5rem; flex-wrap:wrap; align-items:center;">
                    <span style="font-size:0.8125rem; font-weight:600; color:var(--slate-500); margin-right:4px;">Filter:</span>
                    <a href="<?php echo BASE_URL; ?>/pages/staff/patients.php<?php echo $search ? '?search='.urlencode($search) : ''; ?>" class="btn <?php echo empty($priority_filter) ? 'btn-primary' : 'btn-outline'; ?> btn-sm">
                        All (<?php echo $p_counts['all']; ?>)
                    </a>
                    <a href="<?php echo BASE_URL; ?>/pages/staff/patients.php?priority=critical<?php echo $search ? '&search='.urlencode($search) : ''; ?>" class="btn <?php echo $priority_filter === 'critical' ? 'btn-danger' : 'btn-outline'; ?> btn-sm">
                        Critical (<?php echo $p_counts['critical']; ?>)
                    </a>
                    <a href="<?php echo BASE_URL; ?>/pages/staff/patients.php?priority=high<?php echo $search ? '&search='.urlencode($search) : ''; ?>" class="btn <?php echo $priority_filter === 'high' ? 'btn-warning' : 'btn-outline'; ?> btn-sm">
                        High (<?php echo $p_counts['high']; ?>)
                    </a>
                    <a href="<?php echo BASE_URL; ?>/pages/staff/patients.php?priority=medium<?php echo $search ? '&search='.urlencode($search) : ''; ?>" class="btn <?php echo $priority_filter === 'medium' ? 'btn-primary' : 'btn-outline'; ?> btn-sm">
                        Medium (<?php echo $p_counts['medium']; ?>)
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Patients Table Card -->
    <div class="card">
        <div class="card-header">
            <h3>Registered Patients (<?php echo count($patients); ?>)</h3>
        </div>
        <div class="card-body p-0">
            <?php if (empty($patients)): ?>
                <div class="empty-state">
                    <p>No patients found matching your search.</p>
                    <a href="<?php echo BASE_URL; ?>/pages/staff/patients.php" class="btn btn-outline btn-sm" style="margin-top:1rem;">Reset Filters</a>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Patient Name</th>
                                <th>DOB & Age</th>
                                <th>Gender</th>
                                <th>Contact Number</th>
                                <th>Priority</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($patients as $patient): 
                                $age = $patient['date_of_birth'] ? (date_diff(date_create($patient['date_of_birth']), date_create('today'))->y . ' yrs') : 'N/A';
                                $initials = strtoupper(substr($patient['first_name'], 0, 1) . substr($patient['last_name'], 0, 1));
                            ?>
                                <tr>
                                    <td>
                                        <div style="display:flex; align-items:center; gap:0.75rem;">
                                            <div style="width:36px; height:36px; border-radius:50%; background:var(--teal-light); color:var(--teal); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.875rem;">
                                                <?php echo $initials; ?>
                                            </div>
                                            <div>
                                                <strong><?php echo htmlspecialchars($patient['last_name'] . ', ' . $patient['first_name']); ?></strong>
                                                <div style="font-size:0.75rem; color:var(--slate-400);">ID: #<?php echo str_pad($patient['patient_id'], 4, '0', STR_PAD_LEFT); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div><?php echo $patient['date_of_birth'] ? date('M d, Y', strtotime($patient['date_of_birth'])) : 'N/A'; ?></div>
                                        <div style="font-size:0.75rem; color:var(--slate-500);"><?php echo $age; ?></div>
                                    </td>
                                    <td><?php echo ucfirst($patient['gender'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($patient['contact_number'] ?? ''); ?></td>
                                    <td>
                                        <?php if ($patient['priority_level']): ?>
                                            <span class="badge priority-<?php echo $patient['priority_level']; ?>">
                                                <?php echo ucfirst($patient['priority_level'] ?? ''); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Not Set</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align:right;">
                                        <a href="<?php echo BASE_URL; ?>/pages/staff/view_patient.php?id=<?php echo $patient['patient_id']; ?>" class="btn btn-outline btn-sm">View</a>
                                        <a href="<?php echo BASE_URL; ?>/pages/staff/edit_patient.php?id=<?php echo $patient['patient_id']; ?>" class="btn btn-primary btn-sm">Edit</a>
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
