<?php
/**
 * MedCare System - Staff: Treatment Progress Tracker
 */
require_once __DIR__ . '/../../includes/auth.php';
requireRole('staff');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/header.php';

$database = new Database();
$conn = $database->getConnection();

$patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
$treatment_id = isset($_GET['treatment_id']) ? (int)$_GET['treatment_id'] : 0;

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

// Get patient treatments
$query = "
    SELECT t.*, d.diagnosis_name 
    FROM treatments t 
    LEFT JOIN diagnoses d ON t.diagnosis_id = d.diagnosis_id 
    WHERE t.patient_id = ? 
    ORDER BY t.start_date DESC
";
$stmt = $conn->prepare($query);
$stmt->execute([$patient_id]);
$treatments = $stmt->fetchAll();

// Get treatment progress
if ($treatment_id) {
    $query = "
        SELECT tp.*, t.treatment_name, u.full_name as recorded_by_name 
        FROM treatment_progress tp 
        JOIN treatments t ON tp.treatment_id = t.treatment_id 
        JOIN users u ON tp.recorded_by = u.user_id 
        WHERE tp.treatment_id = ? 
        ORDER BY tp.progress_date DESC
    ";
    $stmt = $conn->prepare($query);
    $stmt->execute([$treatment_id]);
    $progress_records = $stmt->fetchAll();
    
    // Get treatment details
    $query = "SELECT * FROM treatments WHERE treatment_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$treatment_id]);
    $current_treatment = $stmt->fetch();
    
    // The treatment must belong to this patient
    if (!$current_treatment || (int)$current_treatment['patient_id'] !== $patient_id) {
        $treatment_id = 0;
        $progress_records = [];
        $current_treatment = null;
    }
} else {
    $progress_records = [];
    $current_treatment = null;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $treatment_id) {
    $progress_notes = sanitizeInput($_POST['progress_notes'] ?? '');
    $status = $_POST['status'] ?? '';
    
    if (empty($progress_notes) || !in_array($status, ['improving', 'stable', 'worsening', 'recovered', 'complications'], true)) {
        $error = 'Progress notes and status are required.';
    } else {
        try {
            $query = "
                INSERT INTO treatment_progress (patient_id, treatment_id, progress_notes, status, recorded_by)
                VALUES (?, ?, ?, ?, ?)
            ";
            $stmt = $conn->prepare($query);
            $stmt->execute([$patient_id, $treatment_id, $progress_notes, $status, $_SESSION['user_id']]);
            
            $success = 'Progress record added successfully!';
            
            // Refresh progress records
            $query = "
                SELECT tp.*, t.treatment_name, u.full_name as recorded_by_name 
                FROM treatment_progress tp 
                JOIN treatments t ON tp.treatment_id = t.treatment_id 
                JOIN users u ON tp.recorded_by = u.user_id 
                WHERE tp.treatment_id = ? 
                ORDER BY tp.progress_date DESC
            ";
            $stmt = $conn->prepare($query);
            $stmt->execute([$treatment_id]);
            $progress_records = $stmt->fetchAll();
            
            $_POST = array();
        } catch (PDOException $e) {
            $error = 'Error adding progress record. Please try again.';
            error_log("Progress record error: " . $e->getMessage());
        }
    }
}
?>

<div class="card">
    <div class="card-header">
        <h2>Treatment Progress Tracker</h2>
    </div>
    <div class="card-body">
        <div class="btn-group" style="margin-bottom: 1rem;">
            <a href="<?php echo BASE_URL; ?>/pages/staff/view_patient.php?id=<?php echo $patient_id; ?>" class="btn btn-secondary">Back to Patient</a>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3>Patient Information</h3>
            </div>
            <div class="card-body">
                <p><strong>Name:</strong> <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></p>
                <p><strong>Contact:</strong> <?php echo htmlspecialchars($patient['contact_number'] ?? ''); ?></p>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3>Select Treatment</h3>
            </div>
            <div class="card-body">
                <?php if (empty($treatments)): ?>
                    <div class="empty-state">
                        <p>No treatments found for this patient.</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Treatment</th>
                                    <th>Diagnosis</th>
                                    <th>Status</th>
                                    <th>Start Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($treatments as $treatment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($treatment['treatment_name'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($treatment['diagnosis_name'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $treatment['status'] === 'completed' ? 'success' : ($treatment['status'] === 'ongoing' ? 'info' : 'warning'); ?>">
                                                <?php echo ucfirst($treatment['status'] ?? ''); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $treatment['start_date'] ? date('M d, Y', strtotime($treatment['start_date'])) : 'N/A'; ?></td>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>/pages/staff/treatment_progress.php?patient_id=<?php echo $patient_id; ?>&treatment_id=<?php echo $treatment['treatment_id']; ?>" class="action-link">View Progress</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($current_treatment): ?>
        <div class="card">
            <div class="card-header">
                <h3>Current Treatment: <?php echo htmlspecialchars($current_treatment['treatment_name'] ?? ''); ?></h3>
            </div>
            <div class="card-body">
                <p><strong>Diagnosis:</strong> <?php echo htmlspecialchars($current_treatment['diagnosis_name'] ?? 'N/A'); ?></p>
                <p><strong>Description:</strong> <?php echo htmlspecialchars($current_treatment['description'] ?? 'N/A'); ?></p>
                <p><strong>Instructions:</strong> <?php echo htmlspecialchars($current_treatment['instructions'] ?? 'N/A'); ?></p>
                <div class="form-row">
                    <div><strong>Start Date:</strong> <?php echo $current_treatment['start_date'] ? date('M d, Y', strtotime($current_treatment['start_date'])) : 'N/A'; ?></div>
                    <div><strong>End Date:</strong> <?php echo $current_treatment['end_date'] ? date('M d, Y', strtotime($current_treatment['end_date'])) : 'N/A'; ?></div>
                </div>
                <p><strong>Status:</strong> 
                    <span class="badge badge-<?php echo $current_treatment['status'] === 'completed' ? 'success' : ($current_treatment['status'] === 'ongoing' ? 'info' : 'warning'); ?>">
                        <?php echo ucfirst($current_treatment['status'] ?? ''); ?>
                    </span>
                </p>
            </div>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h3>Add Progress Record</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="progress_notes">Progress Notes *</label>
                        <textarea id="progress_notes" name="progress_notes" required placeholder="Describe the patient's progress, response to treatment, any changes in condition..."><?php echo htmlspecialchars($_POST['progress_notes'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Current Status *</label>
                        <select id="status" name="status" required>
                            <option value="">Select Status</option>
                            <option value="improving" <?php echo (($_POST['status'] ?? '') === 'improving' ? 'selected' : ''); ?>>Improving</option>
                            <option value="stable" <?php echo (($_POST['status'] ?? '') === 'stable' ? 'selected' : ''); ?>>Stable</option>
                            <option value="worsening" <?php echo (($_POST['status'] ?? '') === 'worsening' ? 'selected' : ''); ?>>Worsening</option>
                            <option value="recovered" <?php echo (($_POST['status'] ?? '') === 'recovered' ? 'selected' : ''); ?>>Recovered</option>
                            <option value="complications" <?php echo (($_POST['status'] ?? '') === 'complications' ? 'selected' : ''); ?>>Complications</option>
                        </select>
                    </div>
                    
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">Add Progress Record</button>
                    </div>
                </form>
            </div>
        </div>
        
        <?php if (!empty($progress_records)): ?>
        <div class="card">
            <div class="card-header">
                <h3>Progress History</h3>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Notes</th>
                                <th>Recorded By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($progress_records as $record): ?>
                                <tr>
                                    <td><?php echo date('M d, Y h:i A', strtotime($record['progress_date'])); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $record['status'] === 'recovered' ? 'success' : ($record['status'] === 'worsening' || $record['status'] === 'complications' ? 'danger' : 'info'); ?>">
                                            <?php echo ucfirst($record['status'] ?? ''); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($record['progress_notes'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($record['recorded_by_name'] ?? ''); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
