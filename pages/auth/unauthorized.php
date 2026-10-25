<?php
/**
 * MedCare System - Unauthorized Access Page
 */
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2>Access Denied</h2>
    </div>
    <div class="card-body">
        <div class="alert alert-danger">
            <p>You do not have permission to access this page.</p>
        </div>
        <p>Please contact your system administrator if you believe this is an error.</p>
        <div style="margin-top: 1rem;">
            <a href="<?php echo BASE_URL; ?>/index.php" class="btn btn-primary">Return to Login</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
