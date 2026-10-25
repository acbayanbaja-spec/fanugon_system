<?php
/**
 * MedCare System - Staff: Dispense Prescription
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

if ($prescription['status'] !== 'pending') {
    echo '<div class="alert alert-warning">This prescription has already been processed.</div>';
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

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dispense_quantities = $_POST['dispense_quantities'] ?? [];
    $notes = sanitizeInput($_POST['notes'] ?? '');
    
    if (empty($dispense_quantities)) {
        $error = 'Please specify quantities to dispense.';
    } else {
        try {
            $conn->beginTransaction();
            
            $total_dispensed_now = 0;
            
            foreach ($prescription_items as $item) {
                $item_id = $item['item_id'];
                $dispense_quantity = (int)($dispense_quantities[$item_id] ?? 0);
                $remaining_to_dispense = $item['quantity_required'] - $item['quantity_dispensed'];
                
                if ($dispense_quantity <= 0) {
                    continue; // Skip items with 0 dispense quantity
                }
                
                if ($dispense_quantity > $remaining_to_dispense) {
                    throw new Exception("Cannot dispense more than required for: " . $item['medication_name']);
                }
                
                // Check expiration
                if ($item['expiration_date'] && $item['expiration_date'] < date('Y-m-d')) {
                    throw new Exception("Cannot dispense expired medication: " . $item['medication_name']);
                }
                
                // Deduct stock atomically; fails if another user took the stock in the meantime
                $query = "UPDATE inventory SET quantity = quantity - ?, updated_at = CURRENT_TIMESTAMP WHERE inventory_id = ? AND quantity >= ?";
                $stmt = $conn->prepare($query);
                $stmt->execute([$dispense_quantity, $item['inventory_id'], $dispense_quantity]);
                if ($stmt->rowCount() === 0) {
                    throw new Exception("Insufficient stock for: " . $item['medication_name']);
                }
                
                // Update prescription item
                $query = "UPDATE prescription_items SET quantity_dispensed = quantity_dispensed + ?, updated_at = CURRENT_TIMESTAMP WHERE item_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->execute([$dispense_quantity, $item_id]);
                
                // Record inventory transaction
                $query = "
                    INSERT INTO inventory_transactions (inventory_id, transaction_type, quantity, reference_type, reference_id, notes, performed_by)
                    VALUES (?, 'out', ?, 'prescription', ?, ?, ?)
                ";
                $stmt = $conn->prepare($query);
                $stmt->execute([$item['inventory_id'], $dispense_quantity, $prescription_id, 'Dispensed for prescription #' . $prescription_id . ($notes ? ' - ' . $notes : ''), $_SESSION['user_id']]);
                
                $total_dispensed_now += $dispense_quantity;
            }
            
            if ($total_dispensed_now === 0) {
                throw new Exception('Please enter a quantity to dispense for at least one medication.');
            }
            
            // Mark the prescription as dispensed only when EVERY item is fully dispensed
            $stmt = $conn->prepare("SELECT COUNT(*) FROM prescription_items WHERE prescription_id = ? AND quantity_dispensed < quantity_required");
            $stmt->execute([$prescription_id]);
            if ((int)$stmt->fetchColumn() === 0) {
                $query = "UPDATE prescriptions SET status = 'dispensed', updated_at = CURRENT_TIMESTAMP WHERE prescription_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->execute([$prescription_id]);
            }
            
            $conn->commit();
            $success = 'Medications dispensed successfully!';
            
            // Refresh data
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
            
            $query = "
                SELECT pi.*, i.quantity as available_quantity, i.unit, i.expiration_date 
                FROM prescription_items pi 
                JOIN inventory i ON pi.inventory_id = i.inventory_id 
                WHERE pi.prescription_id = ?
            ";
            $stmt = $conn->prepare($query);
            $stmt->execute([$prescription_id]);
            $prescription_items = $stmt->fetchAll();
            
        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $error = ($e instanceof PDOException) ? 'Error dispensing medications. Please try again.' : $e->getMessage();
            error_log("Dispensing error: " . $e->getMessage());
        }
    }
}
?>

<div class="card">
    <div class="card-header">
        <h2>Dispense Prescription</h2>
    </div>
    <div class="card-body">
        <div class="btn-group" style="margin-bottom: 1rem;">
            <a href="<?php echo BASE_URL; ?>/pages/staff/view_prescription.php?id=<?php echo $prescription_id; ?>" class="btn btn-secondary">Back to Prescription</a>
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
                <div><strong>Date:</strong> <?php echo date('M d, Y', strtotime($prescription['prescription_date'])); ?></div>
                <?php if ($prescription['notes']): ?>
                <div><strong>Notes:</strong> <?php echo htmlspecialchars($prescription['notes'] ?? ''); ?></div>
                <?php endif; ?>
            </div>
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
        
        <?php if ($prescription['status'] === 'pending'): ?>
        <div class="card">
            <div class="card-header">
                <h3>Dispense Medications</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Medication</th>
                                    <th>Dosage</th>
                                    <th>Frequency</th>
                                    <th>Required</th>
                                    <th>Already Dispensed</th>
                                    <th>Available Stock</th>
                                    <th>Quantity to Dispense</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($prescription_items as $item): ?>
                                    <?php 
                                    $remaining = $item['quantity_required'] - $item['quantity_dispensed'];
                                    $can_dispense = $item['available_quantity'] >= $remaining && $item['expiration_date'] >= date('Y-m-d');
                                    ?>
                                    <tr>
                                        <td>
                                            <?php echo htmlspecialchars($item['medication_name'] ?? ''); ?>
                                            <?php if ($item['expiration_date'] < date('Y-m-d')): ?>
                                                <span class="badge badge-danger">Expired</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['dosage'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($item['frequency'] ?? 'N/A'); ?></td>
                                        <td><?php echo $item['quantity_required']; ?></td>
                                        <td><?php echo $item['quantity_dispensed']; ?></td>
                                        <td>
                                            <?php if ($item['available_quantity'] < $remaining): ?>
                                                <span class="badge badge-danger"><?php echo $item['available_quantity']; ?> (Insufficient)</span>
                                            <?php else: ?>
                                                <span class="badge badge-success"><?php echo $item['available_quantity']; ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($remaining > 0 && $can_dispense): ?>
                                                <input type="number" name="dispense_quantities[<?php echo $item['item_id']; ?>]" 
                                                       min="0" max="<?php echo $remaining; ?>" 
                                                       value="<?php echo $remaining; ?>" 
                                                       style="width: 80px;">
                                            <?php elseif ($remaining <= 0): ?>
                                                <span class="badge badge-success">Complete</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Cannot dispense</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="form-group" style="margin-top: 1rem;">
                        <label for="notes">Dispensing Notes</label>
                        <textarea id="notes" name="notes"><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="btn-group">
                        <button type="submit" class="btn btn-success">Dispense Medications</button>
                        <a href="<?php echo BASE_URL; ?>/pages/staff/view_prescription.php?id=<?php echo $prescription_id; ?>" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
        <?php else: ?>
        <div class="alert alert-success">
            <strong>Prescription Completed:</strong> This prescription has been fully dispensed.
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
