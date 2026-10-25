<?php
/**
 * MedCare System - Patient: Health Education Library
 */
require_once __DIR__ . '/../../includes/auth.php';
requireRole('patient');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/header.php';

$database = new Database();
$conn = $database->getConnection();

// Handle search and filters
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';

// Build query - only show published resources
$query = "
    SELECT he.*, u.full_name as author_name 
    FROM health_education he 
    JOIN users u ON he.created_by = u.user_id 
    WHERE he.status = 'published'
";
$params = [];

if ($search) {
    $query .= " AND (he.title LIKE ? OR he.summary LIKE ? OR he.content LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($category_filter) {
    $query .= " AND he.category = ?";
    $params[] = $category_filter;
}

$query .= " ORDER BY he.published_date DESC, he.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$resources = $stmt->fetchAll();

// Get unique categories
$query = "SELECT DISTINCT category FROM health_education WHERE status = 'published' AND category IS NOT NULL ORDER BY category";
$stmt = $conn->query($query);
$categories = $stmt->fetchAll();
?>

<div class="card">
    <div class="card-header">
        <h2>Health Education Library</h2>
    </div>
    <div class="card-body">
        <div class="btn-group" style="margin-bottom: 1rem;">
            <a href="<?php echo BASE_URL; ?>/pages/patient/dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3>Browse Health Resources</h3>
            </div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group">
                        <form method="GET" action="">
                            <input type="text" name="search" placeholder="Search health topics..." value="<?php echo htmlspecialchars($search); ?>">
                        </form>
                    </div>
                    
                    <div class="form-group">
                        <form method="GET" action="">
                            <select name="category" onchange="this.form.submit()">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat['category'] ?? ''); ?>" <?php echo $category_filter === $cat['category'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['category'] ?? ''); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (empty($resources)): ?>
            <div class="empty-state">
                <p>No health education resources found.</p>
            </div>
        <?php else: ?>
            <div class="dashboard-grid">
                <?php foreach ($resources as $resource): ?>
                    <div class="card">
                        <div class="card-header">
                            <h4><?php echo htmlspecialchars($resource['title'] ?? ''); ?></h4>
                            <span class="badge badge-info"><?php echo htmlspecialchars($resource['category'] ?? ''); ?></span>
                        </div>
                        <div class="card-body">
                            <?php if ($resource['summary']): ?>
                                <p><?php echo htmlspecialchars(substr($resource['summary'], 0, 150)) . '...'; ?></p>
                            <?php else: ?>
                                <p><?php echo htmlspecialchars(substr($resource['content'], 0, 150)) . '...'; ?></p>
                            <?php endif; ?>
                            
                            <div style="margin-top: 1rem;">
                                <small>By: <?php echo htmlspecialchars($resource['author'] ?? $resource['author_name']); ?></small><br>
                                <small>Published: <?php echo $resource['published_date'] ? date('M d, Y', strtotime($resource['published_date'])) : date('M d, Y', strtotime($resource['created_at'])); ?></small>
                            </div>
                            
                            <div style="margin-top: 1rem;">
                                <a href="<?php echo BASE_URL; ?>/pages/patient/view_health_education.php?id=<?php echo $resource['resource_id']; ?>" class="btn btn-primary btn-sm">Read More</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
