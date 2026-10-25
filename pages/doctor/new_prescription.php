<?php
/**
 * MedCare System - Doctor: Create Prescription
 */
require_once __DIR__ . '/../../includes/auth.php';
requireRole('doctor');

require_once __DIR__ . '/../../config/config.php';

$database = new Database();
$conn = $database->getConnection();

$consultation_id = isset($_GET['consultation_id']) ? (int)$_GET['consultation_id'] : 0;
$patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;

if (!$patient_id) {
    require_once __DIR__ . '/../../includes/header.php';
    echo '<div class="alert alert-danger">Invalid patient ID.</div>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit();
}

// Get patient information
$query = "SELECT * FROM patients WHERE patient_id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$patient_id]);
$patient = $stmt->fetch();

if (!$patient) {
    require_once __DIR__ . '/../../includes/header.php';
    echo '<div class="alert alert-danger">Patient not found.</div>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit();
}

// A prescription must belong to a consultation of this patient (prescriptions.consultation_id is NOT NULL)
$stmt = $conn->prepare("SELECT consultation_id FROM consultations WHERE consultation_id = ? AND patient_id = ?");
$stmt->execute([$consultation_id, $patient_id]);
if (!$stmt->fetch()) {
    require_once __DIR__ . '/../../includes/header.php';
    echo '<div class="alert alert-danger">Prescriptions are created from a consultation. Please open the patient\'s consultation and choose "Create Prescription".</div>';
    echo '<a class="btn btn-secondary" href="' . BASE_URL . '/pages/doctor/view_patient.php?id=' . $patient_id . '">Back to Patient</a>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit();
}

// Get available medications from inventory
$query = "
    SELECT * FROM inventory 
    WHERE item_type = 'medication' AND is_active = 1 AND expiration_date > CURDATE()
    ORDER BY item_name
";
$stmt = $conn->query($query);
$medications = $stmt->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $notes = sanitizeInput($_POST['notes'] ?? '');
    $medication_items = $_POST['medication_items'] ?? [];
    
    if (empty($medication_items)) {
        $error = 'Please add at least one medication to the prescription.';
    } else {
        try {
            $conn->beginTransaction();
            
            // Create prescription
            $query = "
                INSERT INTO prescriptions (consultation_id, patient_id, doctor_id, notes)
                VALUES (?, ?, ?, ?)
            ";
            $stmt = $conn->prepare($query);
            $stmt->execute([$consultation_id, $patient_id, $_SESSION['user_id'], $notes]);
            $prescription_id = $conn->lastInsertId();
            
            // Add prescription items
            foreach ($medication_items as $item) {
                $inventory_id = (int)($item['inventory_id'] ?? 0);
                $medication_name = '';
                $dosage = sanitizeInput($item['dosage'] ?? '');
                $frequency = sanitizeInput($item['frequency'] ?? '');
                $duration = sanitizeInput($item['duration'] ?? '');
                $quantity_required = (int)($item['quantity_required'] ?? 0);
                $instructions = sanitizeInput($item['instructions'] ?? '');
                
                // Check inventory availability
                $query = "SELECT item_name, item_type, quantity, expiration_date, is_active FROM inventory WHERE inventory_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->execute([$inventory_id]);
                $inventory_item = $stmt->fetch();
                
                if (!$inventory_item || $inventory_item['item_type'] !== 'medication' || !$inventory_item['is_active']) {
                    throw new Exception("Medication not found in inventory.");
                }
                
                // Use the name from inventory rather than the value sent by the browser
                $medication_name = $inventory_item['item_name'];
                
                if ($quantity_required < 1) {
                    throw new Exception("Quantity required must be at least 1 for: $medication_name");
                }
                
                if ($inventory_item['expiration_date'] && $inventory_item['expiration_date'] < date('Y-m-d')) {
                    throw new Exception("Medication has expired.");
                }
                
                if ($inventory_item['quantity'] < $quantity_required) {
                    throw new Exception("Insufficient stock for: $medication_name");
                }
                
                // Add prescription item
                $query = "
                    INSERT INTO prescription_items (prescription_id, inventory_id, medication_name, dosage, frequency, duration, quantity_required, instructions)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ";
                $stmt = $conn->prepare($query);
                $stmt->execute([
                    $prescription_id, $inventory_id, $medication_name, $dosage, $frequency, $duration, $quantity_required, $instructions
                ]);
            }
            
            $conn->commit();
            $success = 'Prescription created successfully!';
            
            // Redirect to view prescription
            header('Location: ' . BASE_URL . '/pages/doctor/view_prescription.php?id=' . $prescription_id);
            exit();
        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $error = $e->getMessage();
            error_log("Prescription error: " . $e->getMessage());
        }
    }
}

// Render the page header only after any redirect has had a chance to run
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h2>Create Prescription</h2>
    </div>
    <div class="card-body">
        <div class="btn-group" style="margin-bottom: 1rem;">
            <a href="<?php echo BASE_URL; ?>/pages/doctor/view_patient.php?id=<?php echo $patient_id; ?>" class="btn btn-secondary">Back to Patient</a>
            <?php if ($consultation_id): ?>
                <a href="<?php echo BASE_URL; ?>/pages/doctor/view_consultation.php?id=<?php echo $consultation_id; ?>" class="btn btn-secondary">Back to Consultation</a>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3>Patient Information</h3>
            </div>
            <div class="card-body">
                <p><strong>Name:</strong> <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></p>
                <p><strong>Contact:</strong> <?php echo htmlspecialchars($patient['contact_number'] ?? ''); ?></p>
                <p><strong>Allergies:</strong> <?php echo htmlspecialchars($patient['allergies'] ?? 'None'); ?></p>
            </div>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h3>Prescription Details</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="" id="prescriptionForm">
                    <div id="medicationItems">
                        <div class="form-row medication-item">
                            <div class="form-group" style="flex: 2;">
                                <label>Medication *</label>
                                <select name="medication_items[0][inventory_id]" class="medication-select" required onchange="updateMedicationName(this)">
                                    <option value="">Select Medication</option>
                                    <?php foreach ($medications as $med): ?>
                                        <option value="<?php echo $med['inventory_id']; ?>" 
                                                data-name="<?php echo htmlspecialchars($med['item_name'] ?? ''); ?>"
                                                data-quantity="<?php echo $med['quantity']; ?>"
                                                data-unit="<?php echo htmlspecialchars($med['unit'] ?? ''); ?>">
                                            <?php echo htmlspecialchars($med['item_name'] ?? ''); ?> (Stock: <?php echo $med['quantity']; ?> <?php echo htmlspecialchars($med['unit'] ?? ''); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="hidden" name="medication_items[0][medication_name]" class="medication-name">
                            </div>
                            
                            <div class="form-group">
                                <label>Dosage</label>
                                <input type="text" name="medication_items[0][dosage]" placeholder="e.g., 500mg">
                            </div>
                            
                            <div class="form-group">
                                <label>Frequency</label>
                                <input type="text" name="medication_items[0][frequency]" placeholder="e.g., 3x daily">
                            </div>
                        </div>
                        
                        <div class="form-row medication-item">
                            <div class="form-group">
                                <label>Duration</label>
                                <input type="text" name="medication_items[0][duration]" placeholder="e.g., 7 days">
                            </div>
                            
                            <div class="form-group">
                                <label>Quantity Required *</label>
                                <input type="number" name="medication_items[0][quantity_required]" min="1" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Instructions</label>
                                <input type="text" name="medication_items[0][instructions]" placeholder="e.g., Take after meals">
                            </div>
                        </div>
                    </div>
                    
                    <button type="button" class="btn btn-secondary" onclick="addMedicationItem()" style="margin-bottom: 1rem;">+ Add Another Medication</button>
                    
                    <div class="form-group">
                        <label for="notes">Additional Notes</label>
                        <textarea id="notes" name="notes"><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">Create Prescription</button>
                        <a href="<?php echo BASE_URL; ?>/pages/doctor/view_patient.php?id=<?php echo $patient_id; ?>" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let medicationItemCount = 1;

function updateMedicationName(select) {
    const container = select.closest('.medication-item').parentElement;
    const nameInput = container.querySelector('.medication-name');
    const selectedOption = select.options[select.selectedIndex];
    nameInput.value = selectedOption.dataset.name || '';
}

function addMedicationItem() {
    const container = document.getElementById('medicationItems');
    const newItem = document.createElement('div');
    newItem.innerHTML = `
        <div class="form-row medication-item" style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #ddd;">
            <div class="form-group" style="flex: 2;">
                <label>Medication *</label>
                <select name="medication_items[${medicationItemCount}][inventory_id]" class="medication-select" required onchange="updateMedicationName(this)">
                    <option value="">Select Medication</option>
                    <?php foreach ($medications as $med): ?>
                        <option value="<?php echo $med['inventory_id']; ?>" 
                                data-name="<?php echo htmlspecialchars($med['item_name'] ?? ''); ?>"
                                data-quantity="<?php echo $med['quantity']; ?>"
                                data-unit="<?php echo htmlspecialchars($med['unit'] ?? ''); ?>">
                            <?php echo htmlspecialchars($med['item_name'] ?? ''); ?> (Stock: <?php echo $med['quantity']; ?> <?php echo htmlspecialchars($med['unit'] ?? ''); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="medication_items[${medicationItemCount}][medication_name]" class="medication-name">
            </div>
            
            <div class="form-group">
                <label>Dosage</label>
                <input type="text" name="medication_items[${medicationItemCount}][dosage]" placeholder="e.g., 500mg">
            </div>
            
            <div class="form-group">
                <label>Frequency</label>
                <input type="text" name="medication_items[${medicationItemCount}][frequency]" placeholder="e.g., 3x daily">
            </div>
        </div>
        
        <div class="form-row medication-item">
            <div class="form-group">
                <label>Duration</label>
                <input type="text" name="medication_items[${medicationItemCount}][duration]" placeholder="e.g., 7 days">
            </div>
            
            <div class="form-group">
                <label>Quantity Required *</label>
                <input type="number" name="medication_items[${medicationItemCount}][quantity_required]" min="1" required>
            </div>
            
            <div class="form-group">
                <label>Instructions</label>
                <input type="text" name="medication_items[${medicationItemCount}][instructions]" placeholder="e.g., Take after meals">
            </div>
            
            <div class="form-group">
                <label>&nbsp;</label>
                <button type="button" class="btn btn-danger btn-sm" onclick="removeMedicationItem(this)">Remove</button>
            </div>
        </div>
    `;
    container.appendChild(newItem);
    medicationItemCount++;
}

function removeMedicationItem(button) {
    const container = button.closest('.medication-item').parentElement;
    container.remove();
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
