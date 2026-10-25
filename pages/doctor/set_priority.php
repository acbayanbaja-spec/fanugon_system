<?php
/**
 * MedCare System - Doctor: Set Patient Priority
 */
require_once __DIR__ . '/../../includes/auth.php';
requireRole('doctor');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/header.php';

$database = new Database();
$conn = $database->getConnection();

$patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;

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

// Get current priority
$query = "SELECT * FROM patient_priority WHERE patient_id = ? ORDER BY assigned_at DESC, priority_id DESC LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->execute([$patient_id]);
$current_priority = $stmt->fetch();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $priority_level = $_POST['priority_level'] ?? '';
    $urgency_reason = sanitizeInput($_POST['urgency_reason'] ?? '');
    
    if (!in_array($priority_level, ['low', 'medium', 'high', 'critical'], true)) {
        $error = 'Please select a priority level.';
    } else {
        try {
            $query = "
                INSERT INTO patient_priority (patient_id, priority_level, urgency_reason, assigned_by)
                VALUES (?, ?, ?, ?)
            ";
            $stmt = $conn->prepare($query);
            $stmt->execute([$patient_id, $priority_level, $urgency_reason, $_SESSION['user_id']]);
            
            $success = 'Patient priority updated successfully!';
            
            // Refresh current priority
            $query = "SELECT * FROM patient_priority WHERE patient_id = ? ORDER BY assigned_at DESC, priority_id DESC LIMIT 1";
            $stmt = $conn->prepare($query);
            $stmt->execute([$patient_id]);
            $current_priority = $stmt->fetch();
        } catch (PDOException $e) {
            $error = 'Error updating patient priority. Please try again.';
            error_log("Priority update error: " . $e->getMessage());
        }
    }
}
?>

<div class="card">
    <div class="card-header">
        <h2>Set Patient Priority</h2>
    </div>
    <div class="card-body">
        <div class="btn-group" style="margin-bottom: 1rem;">
            <a href="<?php echo BASE_URL; ?>/pages/doctor/view_patient.php?id=<?php echo $patient_id; ?>" class="btn btn-secondary">Back to Patient</a>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3>Patient Information</h3>
            </div>
            <div class="card-body">
                <p><strong>Name:</strong> <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></p>
                <p><strong>Contact:</strong> <?php echo htmlspecialchars($patient['contact_number'] ?? ''); ?></p>
                
                <?php if ($current_priority): ?>
                <p>
                    <strong>Current Priority:</strong> 
                    <span class="badge priority-<?php echo $current_priority['priority_level']; ?>">
                        <?php echo ucfirst($current_priority['priority_level'] ?? ''); ?>
                    </span>
                    <small>(Assigned on <?php echo date('M d, Y', strtotime($current_priority['assigned_at'])); ?>)</small>
                </p>
                <?php endif; ?>
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
                <h3>Update Priority Classification</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="priority_level">Priority Level *</label>
                        <select id="priority_level" name="priority_level" required>
                            <option value="">Select Priority Level</option>
                            <option value="low">Low - Routine care, non-urgent</option>
                            <option value="medium">Medium - Requires attention within 24-48 hours</option>
                            <option value="high">High - Requires attention within 4-6 hours</option>
                            <option value="critical">Critical - Immediate attention required</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="urgency_reason">Reason for Priority Classification</label>
                        <textarea id="urgency_reason" name="urgency_reason" placeholder="Explain why this priority level is appropriate..."><?php echo htmlspecialchars($_POST['urgency_reason'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">Set Priority</button>
                        <a href="<?php echo BASE_URL; ?>/pages/doctor/view_patient.php?id=<?php echo $patient_id; ?>" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3>Priority Classification Guidelines</h3>
            </div>
            <div class="card-body">
                <div class="priority-level" style="margin-bottom: 1rem;">
                    <strong>Low Priority:</strong> Routine care, follow-up appointments, non-urgent conditions
                </div>
                <div class="priority-level" style="margin-bottom: 1rem;">
                    <strong>Medium Priority:</strong> Conditions requiring attention within 24-48 hours, moderate symptoms
                </div>
                <div class="priority-level" style="margin-bottom: 1rem;">
                    <strong>High Priority:</strong> Conditions requiring attention within 4-6 hours, significant symptoms
                </div>
                <div class="priority-level">
                    <strong>Critical Priority:</strong> Life-threatening conditions, severe symptoms requiring immediate attention
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
