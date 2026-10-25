<?php
/**
 * MedCare System - Staff: Health Education Management
 */
require_once __DIR__ . '/../../includes/auth.php';
requireRole('staff');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/header.php';

$database = new Database();
$conn = $database->getConnection();

// Handle search and filters
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Build query
$query = "
    SELECT he.*, u.full_name as created_by_name 
    FROM health_education he 
    JOIN users u ON he.created_by = u.user_id 
    WHERE 1=1
";
$params = [];

if ($search) {
    $query .= " AND (he.title LIKE ? OR he.summary LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($category_filter) {
    $query .= " AND he.category = ?";
    $params[] = $category_filter;
}

if ($status_filter) {
    $query .= " AND he.status = ?";
    $params[] = $status_filter;
}

$query .= " ORDER BY he.created_at DESC";
$list_query = $query;
$list_params = $params;

$stmt = $conn->prepare($list_query);
$stmt->execute($list_params);
$resources = $stmt->fetchAll();

// Get unique categories
$query = "SELECT DISTINCT category FROM health_education WHERE category IS NOT NULL ORDER BY category";
$stmt = $conn->query($query);
$categories = $stmt->fetchAll();

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
    $published_date = ($status === 'published') ? date('Y-m-d') : null;
    
    if (empty($title) || empty($category) || empty($content)) {
        $error = 'Title, category, and content are required.';
    } else {
        try {
            $query = "
                INSERT INTO health_education (title, category, content, summary, author, status, published_date, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ";
            $stmt = $conn->prepare($query);
            $stmt->execute([$title, $category, $content, $summary, $author, $status, $published_date, $_SESSION['user_id']]);
            
            $success = 'Health education resource created successfully!';
            $_POST = array();
            
            // Refresh resources
            // Re-run the same (filtered) list query used above
            $stmt = $conn->prepare($list_query);
            $stmt->execute($list_params);
            $resources = $stmt->fetchAll();
            
            $stmt = $conn->query("SELECT DISTINCT category FROM health_education WHERE category IS NOT NULL ORDER BY category");
            $categories = $stmt->fetchAll();
            
        } catch (PDOException $e) {
            $error = 'Error creating resource. Please try again.';
            error_log("Health education error: " . $e->getMessage());
        }
    }
}
?>

<div class="card">
    <div class="card-header">
        <h2>Health Education Management</h2>
    </div>
    <div class="card-body">
        <div class="form-row">
            <div class="form-group">
                <form method="GET" action="">
                    <input type="text" name="search" placeholder="Search resources..." value="<?php echo htmlspecialchars($search); ?>">
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
            
            <div class="form-group">
                <form method="GET" action="">
                    <select name="status" onchange="this.form.submit()">
                        <option value="">All Statuses</option>
                        <option value="draft" <?php echo $status_filter === 'draft' ? 'selected' : ''; ?>>Draft</option>
                        <option value="published" <?php echo $status_filter === 'published' ? 'selected' : ''; ?>>Published</option>
                        <option value="archived" <?php echo $status_filter === 'archived' ? 'selected' : ''; ?>>Archived</option>
                    </select>
                </form>
            </div>
        </div>
        
        <div style="margin-bottom: 1rem;">
            <a href="<?php echo BASE_URL; ?>/pages/staff/dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
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
                <h3>Create New Resource</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="title">Title *</label>
                            <input type="text" id="title" name="title" required value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="category">Category *</label>
                            <input type="text" id="category" name="category" required value="<?php echo htmlspecialchars($_POST['category'] ?? ''); ?>" placeholder="e.g., Disease Prevention, Healthy Living">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="author">Author</label>
                            <input type="text" id="author" name="author" value="<?php echo htmlspecialchars($_POST['author'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="draft" <?php echo (($_POST['status'] ?? '') === 'draft' ? 'selected' : ''); ?>>Draft</option>
                                <option value="published" <?php echo (($_POST['status'] ?? '') === 'published' ? 'selected' : ''); ?>>Published</option>
                                <option value="archived" <?php echo (($_POST['status'] ?? '') === 'archived' ? 'selected' : ''); ?>>Archived</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="summary">Summary</label>
                        <textarea id="summary" name="summary"><?php echo htmlspecialchars($_POST['summary'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="content">Content *</label>
                        <textarea id="content" name="content" required><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Create Resource</button>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3>Health Education Resources</h3>
            </div>
            <div class="card-body">
                <?php if (empty($resources)): ?>
                    <div class="empty-state">
                        <p>No health education resources found.</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Author</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($resources as $resource): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($resource['title'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($resource['category'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($resource['author'] ?? $resource['created_by_name']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $resource['status'] === 'published' ? 'success' : ($resource['status'] === 'archived' ? 'secondary' : 'warning'); ?>">
                                                <?php echo ucfirst($resource['status'] ?? ''); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($resource['created_at'])); ?></td>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>/pages/staff/view_health_education.php?id=<?php echo $resource['resource_id']; ?>" class="action-link">View</a>
                                            <a href="<?php echo BASE_URL; ?>/pages/staff/edit_health_education.php?id=<?php echo $resource['resource_id']; ?>" class="action-link edit">Edit</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
