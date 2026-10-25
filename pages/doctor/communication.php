<?php
/**
 * MedCare System - Doctor: Healthcare Team Communication
 */
require_once __DIR__ . '/../../includes/auth.php';
requireRole('doctor');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/header.php';

$database = new Database();
$conn = $database->getConnection();

$current_user_id = $_SESSION['user_id'];

// Get sent messages
$query = "
    SELECT hc.*, 
           CASE 
               WHEN hc.recipient_id IS NULL THEN 'All Staff'
               ELSE r.full_name 
           END as recipient_name,
           p.first_name, p.last_name
    FROM healthcare_communication hc
    LEFT JOIN users r ON hc.recipient_id = r.user_id
    LEFT JOIN patients p ON hc.patient_id = p.patient_id
    WHERE hc.sender_id = ?
    ORDER BY hc.created_at DESC
";
$stmt = $conn->prepare($query);
$stmt->execute([$current_user_id]);
$sent_messages = $stmt->fetchAll();

// Get received messages
$query = "
    SELECT hc.*, s.full_name as sender_name,
           p.first_name, p.last_name
    FROM healthcare_communication hc
    JOIN users s ON hc.sender_id = s.user_id
    LEFT JOIN patients p ON hc.patient_id = p.patient_id
    WHERE (hc.recipient_id = ? OR (hc.recipient_id IS NULL AND hc.sender_id <> ?))
    ORDER BY hc.created_at DESC
";
$stmt = $conn->prepare($query);
$stmt->execute([$current_user_id, $current_user_id]);
$received_messages = $stmt->fetchAll();

// Get staff members for recipient selection
$query = "SELECT user_id, full_name FROM users WHERE role = 'staff' AND is_active = 1 ORDER BY full_name";
$stmt = $conn->query($query);
$staff_members = $stmt->fetchAll();

// Get patients for patient-related messages
$query = "SELECT patient_id, first_name, last_name FROM patients ORDER BY last_name, first_name LIMIT 50";
$stmt = $conn->query($query);
$patients = $stmt->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipient_id = !empty($_POST['recipient_id']) ? (int)$_POST['recipient_id'] : null;
    $patient_id = !empty($_POST['patient_id']) ? (int)$_POST['patient_id'] : null;
    $subject = sanitizeInput($_POST['subject'] ?? '');
    $message = sanitizeInput($_POST['message'] ?? '');
    $message_type = $_POST['message_type'] ?? 'general';
    if (!in_array($message_type, ['general', 'patient_update', 'treatment_update', 'consultation_request', 'inventory_alert'], true)) {
        $message_type = 'general';
    }
    
    if (empty($message)) {
        $error = 'Message content is required.';
    } else {
        try {
            $query = "
                INSERT INTO healthcare_communication (sender_id, recipient_id, patient_id, subject, message, message_type)
                VALUES (?, ?, ?, ?, ?, ?)
            ";
            $stmt = $conn->prepare($query);
            $stmt->execute([$current_user_id, $recipient_id, $patient_id, $subject, $message, $message_type]);
            
            $success = 'Message sent successfully!';
            $_POST = array();
            
            // Refresh messages
            $query = "
                SELECT hc.*, 
                       CASE 
                           WHEN hc.recipient_id IS NULL THEN 'All Staff'
                           ELSE r.full_name 
                       END as recipient_name,
                       p.first_name, p.last_name
                FROM healthcare_communication hc
                LEFT JOIN users r ON hc.recipient_id = r.user_id
                LEFT JOIN patients p ON hc.patient_id = p.patient_id
                WHERE hc.sender_id = ?
                ORDER BY hc.created_at DESC
            ";
            $stmt = $conn->prepare($query);
            $stmt->execute([$current_user_id]);
            $sent_messages = $stmt->fetchAll();
            
            $query = "
                SELECT hc.*, s.full_name as sender_name,
                       p.first_name, p.last_name
                FROM healthcare_communication hc
                JOIN users s ON hc.sender_id = s.user_id
                LEFT JOIN patients p ON hc.patient_id = p.patient_id
                WHERE (hc.recipient_id = ? OR (hc.recipient_id IS NULL AND hc.sender_id <> ?))
                ORDER BY hc.created_at DESC
            ";
            $stmt = $conn->prepare($query);
            $stmt->execute([$current_user_id, $current_user_id]);
            $received_messages = $stmt->fetchAll();
            
        } catch (PDOException $e) {
            $error = 'Error sending message. Please try again.';
            error_log("Message send error: " . $e->getMessage());
        }
    }
}

// Mark messages as read
if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $message_id = (int)$_GET['mark_read'];
    $query = "UPDATE healthcare_communication SET is_read = 1, read_at = CURRENT_TIMESTAMP WHERE message_id = ? AND recipient_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$message_id, $current_user_id]);
    
    // Refresh received messages
    $query = "
        SELECT hc.*, s.full_name as sender_name,
               p.first_name, p.last_name
        FROM healthcare_communication hc
        JOIN users s ON hc.sender_id = s.user_id
        LEFT JOIN patients p ON hc.patient_id = p.patient_id
        WHERE (hc.recipient_id = ? OR (hc.recipient_id IS NULL AND hc.sender_id <> ?))
        ORDER BY hc.created_at DESC
    ";
    $stmt = $conn->prepare($query);
    $stmt->execute([$current_user_id, $current_user_id]);
    $received_messages = $stmt->fetchAll();
}
?>

<div class="card">
    <div class="card-header">
        <h2>Healthcare Team Communication</h2>
    </div>
    <div class="card-body">
        <div class="btn-group" style="margin-bottom: 1rem;">
            <a href="<?php echo BASE_URL; ?>/pages/doctor/dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
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
                <h3>Send New Message</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="recipient_id">Recipient</label>
                            <select id="recipient_id" name="recipient_id">
                                <option value="">All Staff</option>
                                <?php foreach ($staff_members as $staff): ?>
                                    <option value="<?php echo $staff['user_id']; ?>" <?php echo (isset($_POST['recipient_id']) && $_POST['recipient_id'] == $staff['user_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($staff['full_name'] ?? ''); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="patient_id">Related Patient (Optional)</label>
                            <select id="patient_id" name="patient_id">
                                <option value="">No Related Patient</option>
                                <?php foreach ($patients as $patient): ?>
                                    <option value="<?php echo $patient['patient_id']; ?>" <?php echo (isset($_POST['patient_id']) && $_POST['patient_id'] == $patient['patient_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($patient['last_name'] . ', ' . $patient['first_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="subject">Subject</label>
                            <input type="text" id="subject" name="subject" value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="message_type">Message Type</label>
                            <select id="message_type" name="message_type">
                                <option value="general" <?php echo (($_POST['message_type'] ?? '') === 'general' ? 'selected' : ''); ?>>General</option>
                                <option value="patient_update" <?php echo (($_POST['message_type'] ?? '') === 'patient_update' ? 'selected' : ''); ?>>Patient Update</option>
                                <option value="treatment_update" <?php echo (($_POST['message_type'] ?? '') === 'treatment_update' ? 'selected' : ''); ?>>Treatment Update</option>
                                <option value="consultation_request" <?php echo (($_POST['message_type'] ?? '') === 'consultation_request' ? 'selected' : ''); ?>>Consultation Request</option>
                                <option value="inventory_alert" <?php echo (($_POST['message_type'] ?? '') === 'inventory_alert' ? 'selected' : ''); ?>>Inventory Alert</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Message *</label>
                        <textarea id="message" name="message" required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Send Message</button>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3>Received Messages</h3>
            </div>
            <div class="card-body">
                <?php if (empty($received_messages)): ?>
                    <div class="empty-state">
                        <p>No received messages.</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>From</th>
                                    <th>Type</th>
                                    <th>Patient</th>
                                    <th>Subject</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($received_messages as $msg): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y h:i A', strtotime($msg['created_at'])); ?></td>
                                        <td><?php echo htmlspecialchars($msg['sender_name'] ?? ''); ?></td>
                                        <td><?php echo ucfirst($msg['message_type'] ?? ''); ?></td>
                                        <td><?php echo $msg['patient_id'] ? htmlspecialchars($msg['first_name'] . ' ' . $msg['last_name']) : 'N/A'; ?></td>
                                        <td><?php echo htmlspecialchars(substr($msg['subject'] ?? 'No subject', 0, 20)) . '...'; ?></td>
                                        <td>
                                            <?php if ($msg['is_read']): ?>
                                                <span class="badge badge-success">Read</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">Unread</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!$msg['is_read']): ?>
                                                <a href="<?php echo BASE_URL; ?>/pages/doctor/communication.php?mark_read=<?php echo $msg['message_id']; ?>" class="action-link">Mark Read</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="7" style="background: #f8f9fa; padding: 0.5rem;">
                                            <small><?php echo htmlspecialchars($msg['message'] ?? ''); ?></small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3>Sent Messages</h3>
            </div>
            <div class="card-body">
                <?php if (empty($sent_messages)): ?>
                    <div class="empty-state">
                        <p>No sent messages.</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>To</th>
                                    <th>Type</th>
                                    <th>Patient</th>
                                    <th>Subject</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sent_messages as $msg): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y h:i A', strtotime($msg['created_at'])); ?></td>
                                        <td><?php echo htmlspecialchars($msg['recipient_name'] ?? ''); ?></td>
                                        <td><?php echo ucfirst($msg['message_type'] ?? ''); ?></td>
                                        <td><?php echo $msg['patient_id'] ? htmlspecialchars($msg['first_name'] . ' ' . $msg['last_name']) : 'N/A'; ?></td>
                                        <td><?php echo htmlspecialchars(substr($msg['subject'] ?? 'No subject', 0, 20)) . '...'; ?></td>
                                    </tr>
                                    <tr>
                                        <td colspan="5" style="background: #f8f9fa; padding: 0.5rem;">
                                            <small><?php echo htmlspecialchars($msg['message'] ?? ''); ?></small>
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
