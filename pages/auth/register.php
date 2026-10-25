<?php
/**
 * MedCare System - Patient Registration Page
 * Allows new patients to create an online portal account and clinical record
 */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';

// If already logged in, redirect to their role's dashboard
if (isLoggedIn()) {
    redirectBasedOnRole();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = registerPatient($_POST);
    
    if ($result['success']) {
        // Automatically log the patient in and redirect to dashboard
        setLoggedInUser($result['user_id'], $result['username'], 'patient', $result['full_name']);
        header('Location: ' . BASE_URL . '/pages/patient/dashboard.php?registered=1');
        exit();
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - MedCare System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-wrapper">
        <div class="auth-card register-card">
            <div class="auth-header">
                <div class="brand-badge">
                    <svg class="brand-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
                        <path d="M12 5v6"/>
                        <path d="M9 8h6"/>
                    </svg>
                    <span>MedCare Health</span>
                </div>
                <h2>Create Your Patient Account</h2>
                <p>Register for access to your records, prescriptions, and healthcare team</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <svg class="alert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <div><?php echo htmlspecialchars($error); ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="auth-form" autocomplete="off">
                <!-- Section 1: Account Login Details -->
                <div class="form-section-title">
                    <span class="step-num">1</span>
                    <div>
                        <h4>Account Credentials</h4>
                        <small>These details will be used to log in</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="username">Username <span class="required">*</span></label>
                        <input type="text" id="username" name="username" placeholder="e.g. jdelacruz" required 
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" autofocus>
                        <small class="field-hint">3-30 alphanumeric characters, no spaces</small>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" placeholder="e.g. juan@example.com"
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        <small class="field-hint">Optional, for account notifications</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password <span class="required">*</span></label>
                        <input type="password" id="password" name="password" placeholder="At least 6 characters" required>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required>
                    </div>
                </div>

                <!-- Section 2: Personal Profile -->
                <div class="form-section-title">
                    <span class="step-num">2</span>
                    <div>
                        <h4>Patient Information</h4>
                        <small>Personal details for medical care records</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name <span class="required">*</span></label>
                        <input type="text" id="first_name" name="first_name" placeholder="e.g. Juan" required
                               value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="last_name">Last Name <span class="required">*</span></label>
                        <input type="text" id="last_name" name="last_name" placeholder="e.g. Dela Cruz" required
                               value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-row form-row-3">
                    <div class="form-group">
                        <label for="date_of_birth">Date of Birth</label>
                        <input type="date" id="date_of_birth" name="date_of_birth"
                               value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender">
                            <option value="male" <?php echo (($_POST['gender'] ?? '') === 'male') ? 'selected' : ''; ?>>Male</option>
                            <option value="female" <?php echo (($_POST['gender'] ?? '') === 'female') ? 'selected' : ''; ?>>Female</option>
                            <option value="other" <?php echo (($_POST['gender'] ?? '') === 'other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="blood_type">Blood Type</label>
                        <select id="blood_type" name="blood_type">
                            <option value="">Unknown / Select</option>
                            <?php 
                            $blood_types = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                            foreach ($blood_types as $bt): 
                            ?>
                                <option value="<?php echo $bt; ?>" <?php echo (($_POST['blood_type'] ?? '') === $bt) ? 'selected' : ''; ?>><?php echo $bt; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="contact_number">Mobile / Contact Number <span class="required">*</span></label>
                        <input type="tel" id="contact_number" name="contact_number" placeholder="e.g. 09171234567" required
                               value="<?php echo htmlspecialchars($_POST['contact_number'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="allergies">Known Allergies</label>
                        <input type="text" id="allergies" name="allergies" placeholder="e.g. Penicillin, Aspirin, or None"
                               value="<?php echo htmlspecialchars($_POST['allergies'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="address">Residential Address</label>
                    <input type="text" id="address" name="address" placeholder="Barangay, Municipality, Province (e.g. Poblacion, Tupi, South Cotabato)"
                           value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>">
                </div>

                <!-- Section 3: Emergency Contact -->
                <div class="form-section-title">
                    <span class="step-num">3</span>
                    <div>
                        <h4>Emergency Contact (Optional)</h4>
                        <small>Person to contact in case of urgent medical needs</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="emergency_contact_name">Emergency Contact Name</label>
                        <input type="text" id="emergency_contact_name" name="emergency_contact_name" placeholder="e.g. Maria Dela Cruz"
                               value="<?php echo htmlspecialchars($_POST['emergency_contact_name'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="emergency_contact_number">Emergency Contact Phone</label>
                        <input type="tel" id="emergency_contact_number" name="emergency_contact_number" placeholder="e.g. 09181234567"
                               value="<?php echo htmlspecialchars($_POST['emergency_contact_number'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-actions" style="margin-top: 1.5rem;">
                    <button type="submit" class="btn btn-primary btn-block btn-lg">
                        <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
                        Create Patient Account
                    </button>
                </div>
            </form>

            <div class="auth-footer">
                <p>Already have an account? <a href="<?php echo BASE_URL; ?>/index.php" class="auth-link">Sign in here</a></p>
                <div class="auth-subinfo">
                    <span>MedCare Healthcare System &bull; Tupi, South Cotabato</span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
