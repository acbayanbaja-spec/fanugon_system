<?php
/**
 * MedCare System - Doctor: Treatment Progress List (Select Patient)
 */
require_once __DIR__ . '/../../includes/auth.php';
requireRole('doctor');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/header.php';

$database = new Database();
$conn = $database->getConnection();

// Handle search
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// Get patients with active treatments
if ($search) {
    $query = "
        SELECT DISTINCT p.*, COUNT(t.treatment_id) as treatment_count 
        FROM patients p 
        JOIN treatments t ON p.patient_id = t.patient_id 
        WHERE p.first_name LIKE ? OR p.last_name LIKE ? 
        GROUP BY p.patient_id
        ORDER BY p.last_name, p.first_name
    ";
    $search_param = "%$search%";
    $stmt = $conn->prepare($query);
    $stmt->execute([$search_param, $search_param]);
} else {
    $query = "
        SELECT DISTINCT p.*, COUNT(t.treatment_id) as treatment_count 
        FROM patients p 
        JOIN treatments t ON p.patient_id = t.patient_id 
        GROUP BY p.patient_id
        ORDER BY p.last_name, p.first_name
    ";
    $stmt = $conn->query($query);
}

$patients = $stmt->fetchAll();
?>

<div class="card">
    <div class="card-header">
        <h2>Treatment Progress Tracker</h2>
    </div>
    <div class="card-body">
        <div class="search-box">
            <form method="GET" action="">
                <input type="text" name="search" placeholder="Search patients with treatments..." value="<?php echo htmlspecialchars($search); ?>">
            </form>
        </div>
        
        <div style="margin-bottom: 1rem;">
            <a href="<?php echo BASE_URL; ?>/pages/doctor/dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
        
        <?php if (empty($patients)): ?>
            <div class="empty-state">
                <p>No patients with treatments found.</p>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Contact</th>
                            <th>Active Treatments</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($patients as $patient): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($patient['last_name'] . ', ' . $patient['first_name']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($patient['contact_number'] ?? ''); ?></td>
                                <td><?php echo $patient['treatment_count']; ?></td>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>/pages/doctor/treatment_progress.php?patient_id=<?php echo $patient['patient_id']; ?>" class="action-link">View Progress</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
