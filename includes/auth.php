<?php
/**
 * MedCare System - Authentication Functions
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

/**
 * Check if user has specific role
 */
function hasRole($role) {
    return isLoggedIn() && $_SESSION['role'] === $role;
}

/**
 * Require login - redirect to login page if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/index.php');
        exit();
    }
}

/**
 * Require specific role - redirect if user doesn't have the role
 */
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        header('Location: ' . BASE_URL . '/pages/auth/unauthorized.php');
        exit();
    }
}

/**
 * Check if user can access patient record
 * Doctors and staff can access all patients
 * Patients can only access their own records
 */
function canAccessPatient($patient_id) {
    requireLogin();
    
    $role = $_SESSION['role'];
    $user_id = $_SESSION['user_id'];
    
    // Doctors and staff can access all patient records
    if ($role === 'doctor' || $role === 'staff') {
        return true;
    }
    
    // Patients can only access their own records
    if ($role === 'patient') {
        $database = new Database();
        $conn = $database->getConnection();
        
        $query = "SELECT patient_id FROM patients WHERE user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$user_id]);
        $patient = $stmt->fetch();
        
        if ($patient && $patient['patient_id'] == $patient_id) {
            return true;
        }
    }
    
    return false;
}

/**
 * Sanitize input data
 */
function sanitizeInput($data) {
    // Text is stored exactly as typed (just trimmed). It is made safe when it is
    // DISPLAYED, with htmlspecialchars() on every page. Encoding it here as well
    // made apostrophes show up as "&#039;" (e.g. "Patient&#039;s").
    // SQL injection is prevented separately by the PDO prepared statements.
    return trim((string)$data);
}

/**
 * Hash password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Login user
 */
function loginUser($username, $password) {
    $database = new Database();
    $conn = $database->getConnection();
    
    $query = "SELECT user_id, username, password, role, full_name, is_active FROM users WHERE username = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && $user['is_active'] && verifyPassword($password, $user['password'])) {
        // New session id on login (prevents session fixation)
        session_regenerate_id(true);
        
        // Set session variables
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['last_activity'] = time();
        
        return true;
    }
    
    return false;
}

/**
 * Logout user
 */
function logoutUser() {
    session_unset();
    session_destroy();
    header('Location: ' . BASE_URL . '/index.php');
    exit();
}

/**
 * Check session timeout
 */
function checkSessionTimeout() {
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        logoutUser();
    }
    $_SESSION['last_activity'] = time();
}

/**
 * Get current user info
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'role' => $_SESSION['role'],
        'full_name' => $_SESSION['full_name']
    ];
}

/**
 * Redirect based on role
 */
function redirectBasedOnRole() {
    $role = $_SESSION['role'];
    
    switch ($role) {
        case 'doctor':
            header('Location: ' . BASE_URL . '/pages/doctor/dashboard.php');
            break;
        case 'staff':
            header('Location: ' . BASE_URL . '/pages/staff/dashboard.php');
            break;
        case 'patient':
            header('Location: ' . BASE_URL . '/pages/patient/dashboard.php');
            break;
        default:
            header('Location: ' . BASE_URL . '/index.php');
    }
    exit();
}

/**
 * Auto-login a user programmatically after registration
 */
function setLoggedInUser($user_id, $username, $role, $full_name) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = $username;
    $_SESSION['role'] = $role;
    $_SESSION['full_name'] = $full_name;
    $_SESSION['last_activity'] = time();
}

/**
 * Register a new patient account and patient profile record
 */
function registerPatient($data) {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Required fields check
    $required = [
        'username' => 'Username',
        'password' => 'Password',
        'first_name' => 'First name',
        'last_name' => 'Last name',
        'contact_number' => 'Contact number'
    ];
    
    foreach ($required as $key => $label) {
        if (empty(trim($data[$key] ?? ''))) {
            return ['success' => false, 'message' => "$label is required."];
        }
    }
    
    $username = trim($data['username']);
    $password = $data['password'];
    $confirm_password = $data['confirm_password'] ?? '';
    $email = trim($data['email'] ?? '');
    $first_name = trim($data['first_name']);
    $last_name = trim($data['last_name']);
    $full_name = $first_name . ' ' . $last_name;
    
    // Validate username format (alphanumeric, underscore, hyphen)
    if (!preg_match('/^[a-zA-Z0-9_\-\.]{3,30}$/', $username)) {
        return ['success' => false, 'message' => 'Username must be 3-30 characters and contain only letters, numbers, dashes, dots, or underscores.'];
    }
    
    // Check password length
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        return ['success' => false, 'message' => 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long.'];
    }
    
    // Check password confirmation if provided
    if ($confirm_password !== '' && $password !== $confirm_password) {
        return ['success' => false, 'message' => 'Passwords do not match.'];
    }
    
    // Check email format if provided
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Please provide a valid email address.'];
    }
    
    // Check if username exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Username is already taken. Please choose another username.'];
    }
    
    // Check if email exists
    if (!empty($email)) {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Email address is already in use.'];
        }
    }
    
    try {
        $conn->beginTransaction();
        
        // 1. Create user in users table
        $password_hash = hashPassword($password);
        $user_sql = "INSERT INTO users (username, password, role, email, full_name, is_active) VALUES (?, ?, 'patient', ?, ?, 1)";
        $stmt = $conn->prepare($user_sql);
        $stmt->execute([$username, $password_hash, !empty($email) ? $email : null, $full_name]);
        $user_id = (int)$conn->lastInsertId();
        
        // 2. Create patient in patients table
        $patient_sql = "
            INSERT INTO patients (
                user_id, first_name, last_name, date_of_birth, gender,
                contact_number, address, emergency_contact_name, emergency_contact_number,
                blood_type, allergies
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        $stmt = $conn->prepare($patient_sql);
        $stmt->execute([
            $user_id,
            $first_name,
            $last_name,
            !empty($data['date_of_birth']) ? $data['date_of_birth'] : null,
            in_array($data['gender'] ?? '', ['male', 'female', 'other'], true) ? $data['gender'] : 'other',
            $data['contact_number'],
            !empty($data['address']) ? $data['address'] : null,
            !empty($data['emergency_contact_name']) ? $data['emergency_contact_name'] : null,
            !empty($data['emergency_contact_number']) ? $data['emergency_contact_number'] : null,
            !empty($data['blood_type']) ? $data['blood_type'] : null,
            !empty($data['allergies']) ? $data['allergies'] : null
        ]);
        $patient_id = (int)$conn->lastInsertId();
        
        // 3. Create default patient priority
        $stmt = $conn->prepare("
            INSERT INTO patient_priority (patient_id, priority_level, urgency_reason, assigned_by)
            VALUES (?, 'medium', 'Self-registered patient awaiting initial consultation', 1)
        ");
        $stmt->execute([$patient_id]);
        
        $conn->commit();
        
        return [
            'success' => true,
            'message' => 'Account registered successfully!',
            'user_id' => $user_id,
            'patient_id' => $patient_id,
            'username' => $username,
            'full_name' => $full_name
        ];
    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log("Patient registration error: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred during registration. Please try again.'];
    }
}
?>
