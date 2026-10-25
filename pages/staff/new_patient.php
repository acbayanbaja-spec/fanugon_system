<?php
/**
 * MedCare System - Staff: Register New Patient
 */
require_once __DIR__ . '/../../includes/auth.php';
requireRole('staff');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/header.php';

$database = new Database();
$conn = $database->getConnection();

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
    $priority_level = $_POST['priority_level'] ?? 'medium';
    if (!in_array($priority_level, ['low', 'medium', 'high', 'critical'], true)) {
        $priority_level = 'medium';
    }
    if (!in_array($gender, ['male', 'female', 'other'], true)) {
        $gender = '';
    }
    $urgency_reason = sanitizeInput($_POST['urgency_reason'] ?? 'Staff initial intake');
    
    // Optional portal login creation
    $create_account = !empty($_POST['create_account']);
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $email = sanitizeInput($_POST['email'] ?? '');
    
    // Validation
    if (empty($first_name) || empty($last_name) || empty($contact_number)) {
        $error = 'First name, last name, and contact number are required.';
    } elseif ($create_account && (empty($username) || empty($password))) {
        $error = 'Username and password are required to create an online patient account.';
    } elseif ($create_account && !preg_match('/^[a-zA-Z0-9_\-\.]{3,30}$/', $username)) {
        $error = 'Username must be 3-30 characters and contain only letters, numbers, dashes, dots, or underscores.';
    } elseif ($create_account && $email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please provide a valid email address.';
    } elseif ($create_account && strlen($password) < PASSWORD_MIN_LENGTH) {
        $error = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters.';
    } else {
        try {
            $conn->beginTransaction();
            $user_id = null;
            
            if ($create_account) {
                // Check if username already exists
                $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
                $check_stmt->execute([$username]);
                if ($check_stmt->fetch()) {
                    throw new Exception('Username already exists. Please choose a different username.');
                }
                
                // Insert into users table
                $hash = hashPassword($password);
                $u_stmt = $conn->prepare("INSERT INTO users (username, password, role, email, full_name, is_active) VALUES (?, ?, 'patient', ?, ?, 1)");
                $u_stmt->execute([$username, $hash, $email ?: null, "$first_name $last_name"]);
                $user_id = $conn->lastInsertId();
            }
            
            $query = "
                INSERT INTO patients (user_id, first_name, last_name, date_of_birth, gender, contact_number, address, 
                    emergency_contact_name, emergency_contact_number, blood_type, allergies)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
            $stmt = $conn->prepare($query);
            $stmt->execute([
                $user_id, $first_name, $last_name, $date_of_birth ?: null, $gender ?: 'other', $contact_number, $address ?: null,
                $emergency_contact_name ?: null, $emergency_contact_number ?: null, $blood_type ?: null, $allergies ?: null
            ]);
            $patient_id = $conn->lastInsertId();
            
            // Assign initial priority
            $p_stmt = $conn->prepare("INSERT INTO patient_priority (patient_id, priority_level, urgency_reason, assigned_by) VALUES (?, ?, ?, ?)");
            $p_stmt->execute([$patient_id, $priority_level, $urgency_reason, $_SESSION['user_id']]);
            
            $conn->commit();
            $success = 'Patient registered successfully!' . ($create_account ? ' Online account created for ' . htmlspecialchars($username) . '.' : '');
            $_POST = array();
        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $error = ($e instanceof PDOException) ? 'Error registering patient. Please try again.' : $e->getMessage();
            error_log("Patient registration error: " . $e->getMessage());
        }
    }
}
?>

<div class="page-container">
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title-group">
                <div class="page-title-icon staff">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="page-title">Register New Patient</h1>
                    <p class="page-subtitle">Create patient record, contact profile, and initial care priority</p>
                </div>
            </div>
            <div class="page-header-actions">
                <a href="<?php echo BASE_URL; ?>/pages/staff/patients.php" class="btn btn-outline btn-sm">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:6px;vertical-align:-3px"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                    Back to Patients
                </a>
            </div>
        </div>
    </div>

    <div class="card">
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
                    <label for="first_name">First Name <span class="required">*</span></label>
                    <input type="text" id="first_name" name="first_name" required value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="last_name">Last Name <span class="required">*</span></label>
                    <input type="text" id="last_name" name="last_name" required value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="form-row form-row-3">
                <div class="form-group">
                    <label for="date_of_birth">Date of Birth</label>
                    <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="gender">Gender</label>
                    <select id="gender" name="gender">
                        <option value="">Select Gender</option>
                        <option value="male" <?php echo (($_POST['gender'] ?? '') === 'male') ? 'selected' : ''; ?>>Male</option>
                        <option value="female" <?php echo (($_POST['gender'] ?? '') === 'female') ? 'selected' : ''; ?>>Female</option>
                        <option value="other" <?php echo (($_POST['gender'] ?? '') === 'other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="blood_type">Blood Type</label>
                    <select id="blood_type" name="blood_type">
                        <option value="">Unknown / Select</option>
                        <?php foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $bt): ?>
                            <option value="<?php echo $bt; ?>" <?php echo (($_POST['blood_type'] ?? '') === $bt) ? 'selected' : ''; ?>><?php echo $bt; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="contact_number">Contact Number <span class="required">*</span></label>
                    <input type="text" id="contact_number" name="contact_number" required value="<?php echo htmlspecialchars($_POST['contact_number'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="priority_level">Initial Priority Level</label>
                    <select id="priority_level" name="priority_level">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="address">Address</label>
                <textarea id="address" name="address" rows="2"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="emergency_contact_name">Emergency Contact Name</label>
                    <input type="text" id="emergency_contact_name" name="emergency_contact_name" value="<?php echo htmlspecialchars($_POST['emergency_contact_name'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="emergency_contact_number">Emergency Contact Number</label>
                    <input type="text" id="emergency_contact_number" name="emergency_contact_number" value="<?php echo htmlspecialchars($_POST['emergency_contact_number'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="allergies">Allergies</label>
                <textarea id="allergies" name="allergies" rows="2" placeholder="List any known drug, food, or seasonal allergies..."><?php echo htmlspecialchars($_POST['allergies'] ?? ''); ?></textarea>
            </div>

            <!-- Optional Online Account Section -->
            <div class="card" style="background: var(--slate-50); border: 1px dashed var(--slate-300); margin-top: 1.5rem;">
                <div class="card-header" style="border-bottom: none; margin-bottom: 0.5rem; padding-bottom: 0;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; font-weight: 700; cursor: pointer;">
                        <input type="checkbox" id="create_account" name="create_account" value="1" onchange="document.getElementById('accountFields').style.display = this.checked ? 'block' : 'none';" <?php echo !empty($_POST['create_account']) ? 'checked' : ''; ?>>
                        Create Online Patient Portal Account (Optional)
                    </label>
                </div>
                <div id="accountFields" style="display: <?php echo !empty($_POST['create_account']) ? 'block' : 'none'; ?>; padding-top: 0.5rem;">
                    <p style="font-size: 0.85rem; color: var(--slate-600); margin-bottom: 1rem;">Provide login credentials so the patient can log in to view their prescriptions and treatments.</p>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="username">Portal Username</label>
                            <input type="text" id="username" name="username" placeholder="e.g. jdelacruz" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" placeholder="patient@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="password">Initial Password</label>
                        <input type="password" id="password" name="password" placeholder="At least 6 characters (e.g. admin123)">
                    </div>
                </div>
            </div>
            
            <div class="btn-group" style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
                    Register Patient
                </button>
                <a href="<?php echo BASE_URL; ?>/pages/staff/patients.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
