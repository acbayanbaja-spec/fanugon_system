<?php
/**
 * MedCare System - Patient: View Health Education Resource
 */
require_once __DIR__ . '/../../includes/auth.php';
requireRole('patient');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/header.php';

$database = new Database();
$conn = $database->getConnection();

$resource_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$resource_id) {
    echo '<div class="alert alert-danger">Invalid resource ID.</div>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit();
}

// Get resource (only published)
$query = "
    SELECT he.*, u.full_name as author_name 
    FROM health_education he 
    JOIN users u ON he.created_by = u.user_id 
    WHERE he.resource_id = ? AND he.status = 'published'
";
$stmt = $conn->prepare($query);
$stmt->execute([$resource_id]);
$resource = $stmt->fetch();

if (!$resource) {
    echo '<div class="alert alert-danger">Resource not found or not available.</div>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit();
}
?>

<div class="card">
    <div class="card-header">
        <h2>Health Education Resource</h2>
    </div>
    <div class="card-body">
        <div class="btn-group" style="margin-bottom: 1rem;">
            <a href="<?php echo BASE_URL; ?>/pages/patient/health_education.php" class="btn btn-secondary">Back to Library</a>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3><?php echo htmlspecialchars($resource['title'] ?? ''); ?></h3>
                <span class="badge badge-info"><?php echo htmlspecialchars($resource['category'] ?? ''); ?></span>
            </div>
            <div class="card-body">
                <div class="form-row">
                    <div><strong>Author:</strong> <?php echo htmlspecialchars($resource['author'] ?? $resource['author_name']); ?></div>
                    <div><strong>Published:</strong> <?php echo $resource['published_date'] ? date('M d, Y', strtotime($resource['published_date'])) : date('M d, Y', strtotime($resource['created_at'])); ?></div>
                </div>
                
                <?php if ($resource['summary']): ?>
                <div style="margin: 1rem 0;">
                    <strong>Summary:</strong>
                    <p><?php echo htmlspecialchars($resource['summary'] ?? ''); ?></p>
                </div>
                <?php endif; ?>
                
                <div style="margin: 1rem 0;">
                    <strong>Content:</strong>
                    <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 4px; margin-top: 0.5rem; line-height: 1.8;">
                        <?php echo nl2br(htmlspecialchars($resource['content'] ?? '')); ?>
                    </div>
                </div>
                
                <div class="alert alert-info" style="margin-top: 1rem;">
                    <strong>Disclaimer:</strong> This health education material is for informational purposes only and should not replace professional medical advice. Please consult with your healthcare provider for specific medical concerns.
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
