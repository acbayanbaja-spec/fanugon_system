<?php
/**
 * MedCare System - Staff: View Prescription Details
 */
require_once __DIR__ . '/../../includes/auth.php';
requireRole('staff');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/header.php';

$database = new Database();
$conn = $database->getConnection();

$prescription_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$prescription_id) {
    echo '<div class="alert alert-danger">Invalid prescription ID.</div>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit();
}

// Get prescription information
$query = "
    SELECT p.*, patient.first_name, patient.last_name, u.full_name as doctor_name 
    FROM prescriptions p 
    JOIN patients patient ON p.patient_id = patient.patient_id 
    JOIN users u ON p.doctor_id = u.user_id 
    WHERE p.prescription_id = ?
";
$stmt = $conn->prepare($query);
$stmt->execute([$prescription_id]);
$prescription = $stmt->fetch();

if (!$prescription) {
    echo '<div class="alert alert-danger">Prescription not found.</div>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit();
}

// Get prescription items
$query = "
    SELECT pi.*, i.quantity as available_quantity, i.unit, i.expiration_date 
    FROM prescription_items pi 
    JOIN inventory i ON pi.inventory_id = i.inventory_id 
    WHERE pi.prescription_id = ?
";
$stmt = $conn->prepare($query);
$stmt->execute([$prescription_id]);
$prescription_items = $stmt->fetchAll();
?>

<div class="card">
    <div class="card-header">
        <h2>Prescription Details</h2>
    </div>
    <div class="card-body">
        <div class="btn-group" style="margin-bottom: 1rem;">
            <a href="<?php echo BASE_URL; ?>/pages/staff/prescriptions.php" class="btn btn-secondary">Back to Prescriptions</a>
            <a href="<?php echo BASE_URL; ?>/pages/staff/view_patient.php?id=<?php echo $prescription['patient_id']; ?>" class="btn btn-secondary">View Patient</a>
            <?php if ($prescription['status'] === 'pending'): ?>
                <a href="<?php echo BASE_URL; ?>/pages/staff/dispense_prescription.php?id=<?php echo $prescription_id; ?>" class="btn btn-success">Dispense Medications</a>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3>Prescription Information</h3>
            </div>
            <div class="card-body">
                <div class="form-row">
                    <div><strong>Patient:</strong> <?php echo htmlspecialchars($prescription['first_name'] . ' ' . $prescription['last_name']); ?></div>
                    <div><strong>Doctor:</strong> <?php echo htmlspecialchars($prescription['doctor_name'] ?? ''); ?></div>
                </div>
                <div class="form-row">
                    <div><strong>Date:</strong> <?php echo date('M d, Y h:i A', strtotime($prescription['prescription_date'])); ?></div>
                    <div><strong>Status:</strong> 
                        <span class="badge badge-<?php echo $prescription['status'] === 'dispensed' ? 'success' : ($prescription['status'] === 'cancelled' ? 'danger' : 'warning'); ?>">
                            <?php echo ucfirst($prescription['status'] ?? ''); ?>
                        </span>
                    </div>
                </div>
                <?php if ($prescription['notes']): ?>
                <div><strong>Notes:</strong> <?php echo htmlspecialchars($prescription['notes'] ?? ''); ?></div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3>Medications</h3>
            </div>
            <div class="card-body">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Medication</th>
                                <th>Dosage</th>
                                <th>Frequency</th>
                                <th>Duration</th>
                                <th>Required</th>
                                <th>Dispensed</th>
                                <th>Available Stock</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($prescription_items as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['medication_name'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($item['dosage'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($item['frequency'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($item['duration'] ?? 'N/A'); ?></td>
                                    <td><?php echo $item['quantity_required']; ?></td>
                                    <td><?php echo $item['quantity_dispensed']; ?></td>
                                    <td>
                                        <?php if ($item['expiration_date'] < date('Y-m-d')): ?>
                                            <span class="badge badge-danger">Expired</span>
                                        <?php elseif ($item['available_quantity'] < $item['quantity_required']): ?>
                                            <span class="badge badge-warning"><?php echo $item['available_quantity']; ?> (Low)</span>
                                        <?php else: ?>
                                            <span class="badge badge-success"><?php echo $item['available_quantity']; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($item['quantity_dispensed'] >= $item['quantity_required']): ?>
                                            <span class="badge badge-success">Fully Dispensed</span>
                                        <?php elseif ($item['quantity_dispensed'] > 0): ?>
                                            <span class="badge badge-warning">Partially Dispensed</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <?php if ($prescription['status'] === 'pending'): ?>
        <div class="card">
            <div class="card-header">
                <h3>Inventory Status</h3>
            </div>
            <div class="card-body">
                <?php 
                $all_available = true;
                $stock_issues = [];
                
                foreach ($prescription_items as $item) {
                    if ($item['expiration_date'] < date('Y-m-d')) {
                        $all_available = false;
                        $stock_issues[] = $item['medication_name'] . ' is expired';
                    } elseif ($item['available_quantity'] < $item['quantity_required']) {
                        $all_available = false;
                        $stock_issues[] = $item['medication_name'] . ' has insufficient stock (Available: ' . $item['available_quantity'] . ', Required: ' . $item['quantity_required'] . ')';
                    }
                }
                
                if ($all_available): ?>
                    <div class="alert alert-success">
                        <strong>All medications are available in inventory.</strong> You can proceed with dispensing.
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger">
                        <strong>Cannot dispense - Inventory Issues:</strong>
                        <ul>
                            <?php foreach ($stock_issues as $issue): ?>
                                <li><?php echo htmlspecialchars($issue); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <p>Please restock the required medications or contact the prescribing doctor for alternatives.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
