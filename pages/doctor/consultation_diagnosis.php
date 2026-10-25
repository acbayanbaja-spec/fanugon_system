<?php
/**
 * MedCare System - Doctor: Consultation Diagnosis and Treatment
 */
require_once __DIR__ . '/../../includes/auth.php';
requireRole('doctor');

require_once __DIR__ . '/../../config/config.php';

$database = new Database();
$conn = $database->getConnection();

$consultation_id = isset($_GET['consultation_id']) ? (int)$_GET['consultation_id'] : 0;

if (!$consultation_id) {
    require_once __DIR__ . '/../../includes/header.php';
    echo '<div class="alert alert-danger">Invalid consultation ID.</div>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit();
}

// Get consultation information
$query = "
    SELECT c.*, p.first_name, p.last_name 
    FROM consultations c 
    JOIN patients p ON c.patient_id = p.patient_id 
    WHERE c.consultation_id = ?
";
$stmt = $conn->prepare($query);
$stmt->execute([$consultation_id]);
$consultation = $stmt->fetch();

if (!$consultation) {
    require_once __DIR__ . '/../../includes/header.php';
    echo '<div class="alert alert-danger">Consultation not found.</div>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $diagnosis_code = sanitizeInput($_POST['diagnosis_code'] ?? '');
    $diagnosis_name = sanitizeInput($_POST['diagnosis_name'] ?? '');
    $diagnosis_description = sanitizeInput($_POST['diagnosis_description'] ?? '');
    $severity = $_POST['severity'] ?? '';
    // Optional ENUM/DATE fields must be NULL (not '') or MySQL strict mode rejects the insert
    $severity = in_array($severity, ['mild', 'moderate', 'severe'], true) ? $severity : null;
    
    $treatment_name = sanitizeInput($_POST['treatment_name'] ?? '');
    $treatment_description = sanitizeInput($_POST['treatment_description'] ?? '');
    $treatment_instructions = sanitizeInput($_POST['treatment_instructions'] ?? '');
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $start_date = $start_date !== '' ? $start_date : null;
    $end_date = $end_date !== '' ? $end_date : null;
    
    if (empty($diagnosis_name)) {
        $error = 'Diagnosis name is required.';
    } elseif ($start_date && $end_date && $end_date < $start_date) {
        $error = 'Treatment end date cannot be before the start date.';
    } else {
        try {
            $conn->beginTransaction();
            
            // Insert diagnosis
            $query = "
                INSERT INTO diagnoses (consultation_id, patient_id, diagnosis_code, diagnosis_name, description, severity, diagnosed_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ";
            $stmt = $conn->prepare($query);
            $stmt->execute([
                $consultation_id, $consultation['patient_id'], $diagnosis_code, $diagnosis_name, 
                $diagnosis_description, $severity, $_SESSION['user_id']
            ]);
            $diagnosis_id = $conn->lastInsertId();
            
            // Insert treatment if provided
            if (!empty($treatment_name)) {
                $query = "
                    INSERT INTO treatments (consultation_id, patient_id, diagnosis_id, treatment_name, description, instructions, start_date, end_date, status, prescribed_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)
                ";
                $stmt = $conn->prepare($query);
                $stmt->execute([
                    $consultation_id, $consultation['patient_id'], $diagnosis_id, $treatment_name,
                    $treatment_description, $treatment_instructions, $start_date, $end_date, $_SESSION['user_id']
                ]);
            }
            
            $conn->commit();
            $success = 'Diagnosis and treatment recorded successfully!';
            
            // Redirect to view consultation
            header('Location: ' . BASE_URL . '/pages/doctor/view_consultation.php?id=' . $consultation_id);
            exit();
        } catch (PDOException $e) {
            $conn->rollBack();
            $error = 'Error recording diagnosis and treatment. Please try again.';
            error_log("Diagnosis/Treatment error: " . $e->getMessage());
        }
    }
}

// Render the page header only after any redirect has had a chance to run
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2>Diagnosis and Treatment</h2>
    </div>
    <div class="card-body">
        <div class="btn-group" style="margin-bottom: 1rem;">
            <a href="<?php echo BASE_URL; ?>/pages/doctor/view_patient.php?id=<?php echo $consultation['patient_id']; ?>" class="btn btn-secondary">Back to Patient</a>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3>Consultation Summary</h3>
            </div>
            <div class="card-body">
                <p><strong>Patient:</strong> <?php echo htmlspecialchars($consultation['first_name'] . ' ' . $consultation['last_name']); ?></p>
                <p><strong>Date:</strong> <?php echo date('M d, Y h:i A', strtotime($consultation['consultation_date'])); ?></p>
                <p><strong>Chief Complaint:</strong> <?php echo htmlspecialchars($consultation['chief_complaint'] ?? ''); ?></p>
                <?php if ($consultation['vital_signs']): ?>
                <p><strong>Vital Signs:</strong> <?php echo htmlspecialchars($consultation['vital_signs'] ?? ''); ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h3>Record Diagnosis</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="diagnosis_code">Diagnosis Code (Optional)</label>
                            <input type="text" id="diagnosis_code" name="diagnosis_code" value="<?php echo htmlspecialchars($_POST['diagnosis_code'] ?? ''); ?>" placeholder="e.g., ICD-10 code">
                        </div>
                        
                        <div class="form-group">
                            <label for="severity">Severity</label>
                            <select id="severity" name="severity">
                                <option value="">Select Severity</option>
                                <option value="mild" <?php echo (($_POST['severity'] ?? '') === 'mild') ? 'selected' : ''; ?>>Mild</option>
                                <option value="moderate" <?php echo (($_POST['severity'] ?? '') === 'moderate') ? 'selected' : ''; ?>>Moderate</option>
                                <option value="severe" <?php echo (($_POST['severity'] ?? '') === 'severe') ? 'selected' : ''; ?>>Severe</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="diagnosis_name">Diagnosis Name *</label>
                        <input type="text" id="diagnosis_name" name="diagnosis_name" required value="<?php echo htmlspecialchars($_POST['diagnosis_name'] ?? ''); ?>" placeholder="e.g., Acute Bronchitis">
                    </div>
                    
                    <div class="form-group">
                        <label for="diagnosis_description">Description</label>
                        <textarea id="diagnosis_description" name="diagnosis_description"><?php echo htmlspecialchars($_POST['diagnosis_description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="card-header" style="margin-top: 2rem;">
                        <h3>Record Treatment (Optional)</h3>
                    </div>
                    
                    <div class="form-group">
                        <label for="treatment_name">Treatment Name</label>
                        <input type="text" id="treatment_name" name="treatment_name" value="<?php echo htmlspecialchars($_POST['treatment_name'] ?? ''); ?>" placeholder="e.g., Antibiotic Therapy">
                    </div>
                    
                    <div class="form-group">
                        <label for="treatment_description">Treatment Description</label>
                        <textarea id="treatment_description" name="treatment_description"><?php echo htmlspecialchars($_POST['treatment_description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="treatment_instructions">Instructions</label>
                        <textarea id="treatment_instructions" name="treatment_instructions"><?php echo htmlspecialchars($_POST['treatment_instructions'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="start_date">Start Date</label>
                            <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($_POST['start_date'] ?? date('Y-m-d')); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="end_date">End Date (Optional)</label>
                            <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($_POST['end_date'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">Save Diagnosis & Treatment</button>
                        <a href="<?php echo BASE_URL; ?>/pages/doctor/view_consultation.php?id=<?php echo $consultation_id; ?>" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
