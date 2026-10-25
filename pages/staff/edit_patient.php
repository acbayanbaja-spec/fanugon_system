<?php
/**
 * MedCare System - Staff: Edit Patient
 */
require_once __DIR__ . '/../../includes/auth.php';
requireRole('staff');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/header.php';

$database = new Database();
$conn = $database->getConnection();

$patient_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

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

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = sanitizeInput($_POST['first_name'] ?? '');
    $last_name = sanitizeInput($_POST['last_name'] ?? '');
    $date_of_birth = $_POST['date_of_birth'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $contact_number = sanitizeInput($_POST['contact_number'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    $emergency_contact_name = sanitizeInput($_POST['emergency_contact_name'] ?? '');
    $emergency_contact_number = sanitizeInput($_POST['emergency_contact_number'] ?? '');
    $blood_type = sanitizeInput($_POST['blood_type'] ?? '');
    $allergies = sanitizeInput($_POST['allergies'] ?? '');
    
    // Validation
    if (empty($first_name) || empty($last_name) || empty($contact_number)) {
        $error = 'First name, last name, and contact number are required.';
    } else {
        try {
            $query = "
                UPDATE patients 
                SET first_name = ?, last_name = ?, date_of_birth = ?, gender = ?, contact_number = ?, 
                    address = ?, emergency_contact_name = ?, emergency_contact_number = ?, 
                    blood_type = ?, allergies = ?, updated_at = CURRENT_TIMESTAMP
                WHERE patient_id = ?
            ";
            $stmt = $conn->prepare($query);
            $stmt->execute([
                // Blank optional fields are stored as NULL (MySQL strict mode rejects '' for DATE/ENUM)
                $first_name, $last_name, $date_of_birth ?: null,
                in_array($gender, ['male', 'female', 'other'], true) ? $gender : null,
                $contact_number, $address ?: null,
                $emergency_contact_name ?: null, $emergency_contact_number ?: null, $blood_type ?: null, $allergies ?: null, $patient_id
            ]);
            
            $success = 'Patient information updated successfully!';
            
            // Refresh patient data
            $query = "SELECT * FROM patients WHERE patient_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$patient_id]);
            $patient = $stmt->fetch();
        } catch (PDOException $e) {
            $error = 'Error updating patient information. Please try again.';
            error_log("Patient update error: " . $e->getMessage());
        }
    }
}
?>

<div class="card">
    <div class="card-header">
        <h2>Edit Patient Information</h2>
    </div>
    <div class="card-body">
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
        
        <form method="POST" action="">
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">First Name *</label>
                    <input type="text" id="first_name" name="first_name" required value="<?php echo htmlspecialchars($patient['first_name'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="last_name">Last Name *</label>
                    <input type="text" id="last_name" name="last_name" required value="<?php echo htmlspecialchars($patient['last_name'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="date_of_birth">Date of Birth</label>
                    <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo htmlspecialchars($patient['date_of_birth'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="gender">Gender</label>
                    <select id="gender" name="gender">
                        <option value="">Select Gender</option>
                        <option value="male" <?php echo $patient['gender'] === 'male' ? 'selected' : ''; ?>>Male</option>
                        <option value="female" <?php echo $patient['gender'] === 'female' ? 'selected' : ''; ?>>Female</option>
                        <option value="other" <?php echo $patient['gender'] === 'other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="contact_number">Contact Number *</label>
                <input type="text" id="contact_number" name="contact_number" required value="<?php echo htmlspecialchars($patient['contact_number'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="address">Address</label>
                <textarea id="address" name="address"><?php echo htmlspecialchars($patient['address'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="emergency_contact_name">Emergency Contact Name</label>
                    <input type="text" id="emergency_contact_name" name="emergency_contact_name" value="<?php echo htmlspecialchars($patient['emergency_contact_name'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="emergency_contact_number">Emergency Contact Number</label>
                    <input type="text" id="emergency_contact_number" name="emergency_contact_number" value="<?php echo htmlspecialchars($patient['emergency_contact_number'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="blood_type">Blood Type</label>
                    <input type="text" id="blood_type" name="blood_type" value="<?php echo htmlspecialchars($patient['blood_type'] ?? ''); ?>" placeholder="e.g., O+, A-, B+">
                </div>
                
                <div class="form-group">
                    <label for="allergies">Allergies</label>
                    <textarea id="allergies" name="allergies"><?php echo htmlspecialchars($patient['allergies'] ?? ''); ?></textarea>
                </div>
            </div>
            
            <div class="btn-group">
                <button type="submit" class="btn btn-primary">Update Patient</button>
                <a href="<?php echo BASE_URL; ?>/pages/staff/view_patient.php?id=<?php echo $patient_id; ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
