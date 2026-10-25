<?php
/**
 * MedCare System - Staff: View Health Education Resource
 */
require_once __DIR__ . '/../../includes/auth.php';
requireRole('staff');

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

// Get resource
$query = "
    SELECT he.*, u.full_name as created_by_name 
    FROM health_education he 
    JOIN users u ON he.created_by = u.user_id 
    WHERE he.resource_id = ?
";
$stmt = $conn->prepare($query);
$stmt->execute([$resource_id]);
$resource = $stmt->fetch();

if (!$resource) {
    echo '<div class="alert alert-danger">Resource not found.</div>';
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
            <a href="<?php echo BASE_URL; ?>/pages/staff/health_education.php" class="btn btn-secondary">Back to Resources</a>
            <a href="<?php echo BASE_URL; ?>/pages/staff/edit_health_education.php?id=<?php echo $resource_id; ?>" class="btn btn-primary">Edit Resource</a>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3><?php echo htmlspecialchars($resource['title'] ?? ''); ?></h3>
                <span class="badge badge-<?php echo $resource['status'] === 'published' ? 'success' : ($resource['status'] === 'archived' ? 'secondary' : 'warning'); ?>">
                    <?php echo ucfirst($resource['status'] ?? ''); ?>
                </span>
            </div>
            <div class="card-body">
                <div class="form-row">
                    <div><strong>Category:</strong> <?php echo htmlspecialchars($resource['category'] ?? ''); ?></div>
                    <div><strong>Author:</strong> <?php echo htmlspecialchars($resource['author'] ?? $resource['created_by_name']); ?></div>
                </div>
                <div class="form-row">
                    <div><strong>Created:</strong> <?php echo date('M d, Y', strtotime($resource['created_at'])); ?></div>
                    <div><strong>Published:</strong> <?php echo $resource['published_date'] ? date('M d, Y', strtotime($resource['published_date'])) : 'Not published'; ?></div>
                </div>
                
                <?php if ($resource['summary']): ?>
                <div style="margin: 1rem 0;">
                    <strong>Summary:</strong>
                    <p><?php echo htmlspecialchars($resource['summary'] ?? ''); ?></p>
                </div>
                <?php endif; ?>
                
                <div style="margin: 1rem 0;">
                    <strong>Content:</strong>
                    <div style="background: #f8f9fa; padding: 1rem; border-radius: 4px; margin-top: 0.5rem;">
                        <?php echo nl2br(htmlspecialchars($resource['content'] ?? '')); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
