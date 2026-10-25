<?php
/**
 * MedCare System - Staff: Edit Health Education Resource
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
$query = "SELECT * FROM health_education WHERE resource_id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$resource_id]);
$resource = $stmt->fetch();

if (!$resource) {
    echo '<div class="alert alert-danger">Resource not found.</div>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitizeInput($_POST['title'] ?? '');
    $category = sanitizeInput($_POST['category'] ?? '');
    $content = sanitizeInput($_POST['content'] ?? '');
    $summary = sanitizeInput($_POST['summary'] ?? '');
    $author = sanitizeInput($_POST['author'] ?? '');
    $status = $_POST['status'] ?? 'draft';
    if (!in_array($status, ['draft', 'published', 'archived'], true)) {
        $status = 'draft';
    }
    $published_date = ($status === 'published' && !$resource['published_date']) ? date('Y-m-d') : $resource['published_date'];
    
    if (empty($title) || empty($category) || empty($content)) {
        $error = 'Title, category, and content are required.';
    } else {
        try {
            $query = "
                UPDATE health_education 
                SET title = ?, category = ?, content = ?, summary = ?, author = ?, status = ?, 
                    published_date = ?, updated_at = CURRENT_TIMESTAMP
                WHERE resource_id = ?
            ";
            $stmt = $conn->prepare($query);
            $stmt->execute([$title, $category, $content, $summary, $author, $status, $published_date, $resource_id]);
            
            $success = 'Health education resource updated successfully!';
            
            // Refresh resource data
            $query = "SELECT * FROM health_education WHERE resource_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$resource_id]);
            $resource = $stmt->fetch();
            
        } catch (PDOException $e) {
            $error = 'Error updating resource. Please try again.';
            error_log("Health education update error: " . $e->getMessage());
        }
    }
}
?>

<div class="card">
    <div class="card-header">
        <h2>Edit Health Education Resource</h2>
    </div>
    <div class="card-body">
        <div class="btn-group" style="margin-bottom: 1rem;">
            <a href="<?php echo BASE_URL; ?>/pages/staff/view_health_education.php?id=<?php echo $resource_id; ?>" class="btn btn-secondary">Back to Resource</a>
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
                <h3>Edit Resource</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="title">Title *</label>
                            <input type="text" id="title" name="title" required value="<?php echo htmlspecialchars($resource['title'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="category">Category *</label>
                            <input type="text" id="category" name="category" required value="<?php echo htmlspecialchars($resource['category'] ?? ''); ?>" placeholder="e.g., Disease Prevention, Healthy Living">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="author">Author</label>
                            <input type="text" id="author" name="author" value="<?php echo htmlspecialchars($resource['author'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="draft" <?php echo $resource['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                <option value="published" <?php echo $resource['status'] === 'published' ? 'selected' : ''; ?>>Published</option>
                                <option value="archived" <?php echo $resource['status'] === 'archived' ? 'selected' : ''; ?>>Archived</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="summary">Summary</label>
                        <textarea id="summary" name="summary"><?php echo htmlspecialchars($resource['summary'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="content">Content *</label>
                        <textarea id="content" name="content" required><?php echo htmlspecialchars($resource['content'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">Update Resource</button>
                        <a href="<?php echo BASE_URL; ?>/pages/staff/view_health_education.php?id=<?php echo $resource_id; ?>" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
