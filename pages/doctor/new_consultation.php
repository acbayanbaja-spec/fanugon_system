<?php
/**
 * MedCare System - Doctor: New Consultation
 *
 * Step 1 (no patient_id): choose the patient - today's appointments first,
 *         then a searchable list of all patients.
 * Step 2 (patient_id):    record the consultation (chief complaint, vital
 *         signs, examination notes), then continue to diagnosis & treatment.
 */
require_once __DIR__ . '/../../includes/auth.php';
requireRole('doctor');

require_once __DIR__ . '/../../config/config.php';

$database = new Database();
$conn = $database->getConnection();

$doctor_id  = (int)$_SESSION['user_id'];
$patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;

/* =====================================================================
 * STEP 1 - choose a patient
 * ===================================================================== */
if (!$patient_id) {
    $search = trim($_GET['search'] ?? '');

    // This doctor's appointments today (not cancelled / no-show)
    $stmt = $conn->prepare("
        SELECT a.appointment_time, a.appointment_type, a.reason, a.status,
               p.patient_id, p.first_name, p.last_name
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        WHERE a.doctor_id = ? AND a.appointment_date = CURDATE()
          AND a.status IN ('scheduled', 'confirmed', 'completed')
        ORDER BY a.appointment_time
    ");
    $stmt->execute([$doctor_id]);
    $todays_appointments = $stmt->fetchAll();

    // All patients (optionally filtered), with their last consultation date
    $query = "
        SELECT p.patient_id, p.first_name, p.last_name, p.date_of_birth, p.gender, p.contact_number,
               pp.priority_level,
               (SELECT MAX(c.consultation_date) FROM consultations c WHERE c.patient_id = p.patient_id) AS last_visit
        FROM patients p
        LEFT JOIN patient_priority pp ON pp.priority_id = (
            SELECT pp2.priority_id FROM patient_priority pp2
            WHERE pp2.patient_id = p.patient_id
            ORDER BY pp2.assigned_at DESC, pp2.priority_id DESC LIMIT 1
        )
    ";
    $params = [];
    if ($search !== '') {
        $query .= " WHERE CONCAT(p.first_name, ' ', p.last_name) LIKE ? OR CONCAT(p.last_name, ', ', p.first_name) LIKE ?
                    OR p.contact_number LIKE ? OR p.patient_id = ?";
        $like = '%' . $search . '%';
        $params = [$like, $like, $like, (int)ltrim($search, '#0')];
    }
    $query .= " ORDER BY p.last_name, p.first_name LIMIT 50";
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $patients = $stmt->fetchAll();

    // This doctor's recent consultations
    $stmt = $conn->prepare("
        SELECT c.consultation_id, c.consultation_date, c.chief_complaint, p.first_name, p.last_name,
               (SELECT COUNT(*) FROM diagnoses d WHERE d.consultation_id = c.consultation_id) AS diagnosis_count
        FROM consultations c
        JOIN patients p ON c.patient_id = p.patient_id
        WHERE c.doctor_id = ?
        ORDER BY c.consultation_date DESC
        LIMIT 5
    ");
    $stmt->execute([$doctor_id]);
    $recent = $stmt->fetchAll();

    require_once __DIR__ . '/../../includes/header.php';
    ?>
<div class="page-container">
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title-group">
                <div class="page-title-icon doctor">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                </div>
                <div>
                    <h1 class="page-title">New Consultation</h1>
                    <p class="page-subtitle">Step 1 of 3 · Choose the patient you are seeing</p>
                </div>
            </div>
            <div class="page-header-actions">
                <a href="<?php echo BASE_URL; ?>/pages/doctor/new_patient.php" class="btn btn-outline btn-sm">+ Register New Patient</a>
            </div>
        </div>
    </div>

    <ol class="consult-steps" aria-label="Consultation steps">
        <li class="is-current"><span>1</span> Choose patient</li>
        <li><span>2</span> Consultation notes</li>
        <li><span>3</span> Diagnosis &amp; treatment</li>
    </ol>

    <?php if ($todays_appointments): ?>
    <div class="card">
        <div class="card-header"><h3>Your appointments today</h3></div>
        <div class="card-body p-0">
            <div class="table-container">
                <table>
                    <thead><tr><th>Time</th><th>Patient</th><th>Reason</th><th>Status</th><th style="text-align:right;">Action</th></tr></thead>
                    <tbody>
                    <?php foreach ($todays_appointments as $a): ?>
                        <tr>
                            <td><strong><?php echo date('g:i A', strtotime($a['appointment_time'])); ?></strong></td>
                            <td><?php echo htmlspecialchars($a['last_name'] . ', ' . $a['first_name']); ?></td>
                            <td><?php echo htmlspecialchars($a['reason'] ?? ucfirst(str_replace('_', ' ', $a['appointment_type'] ?? ''))); ?></td>
                            <td><span class="badge badge-<?php echo $a['status'] === 'completed' ? 'success' : ($a['status'] === 'confirmed' ? 'info' : 'secondary'); ?>"><?php echo ucfirst($a['status']); ?></span></td>
                            <td style="text-align:right;">
                                <a class="btn btn-primary btn-sm" href="<?php echo BASE_URL; ?>/pages/doctor/new_consultation.php?patient_id=<?php echo (int)$a['patient_id']; ?>">Start consultation</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header"><h3>Find a patient</h3></div>
        <div class="card-body">
            <form method="GET" action="" class="consult-search">
                <input type="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name, contact number or patient ID (e.g. 0006)" aria-label="Search patients" autofocus>
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if ($search !== ''): ?>
                    <a href="<?php echo BASE_URL; ?>/pages/doctor/new_consultation.php" class="btn btn-secondary">Clear</a>
                <?php endif; ?>
            </form>
        </div>
        <div class="card-body p-0">
            <?php if (!$patients): ?>
                <div class="empty-state">
                    <p>No patient matched “<?php echo htmlspecialchars($search); ?>”.</p>
                    <a href="<?php echo BASE_URL; ?>/pages/doctor/new_patient.php" class="btn btn-outline btn-sm" style="margin-top:1rem;">Register a new patient</a>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead><tr><th>Patient</th><th>Age / Gender</th><th>Contact</th><th>Priority</th><th>Last visit</th><th style="text-align:right;">Action</th></tr></thead>
                        <tbody>
                        <?php foreach ($patients as $p):
                            $age = $p['date_of_birth'] ? date_diff(date_create($p['date_of_birth']), date_create('today'))->y . ' yrs' : '—';
                        ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($p['last_name'] . ', ' . $p['first_name']); ?></strong>
                                    <div style="font-size:0.75rem; color:var(--slate-400);">ID: #<?php echo str_pad($p['patient_id'], 4, '0', STR_PAD_LEFT); ?></div>
                                </td>
                                <td><?php echo $age; ?><?php echo $p['gender'] ? ' · ' . ucfirst($p['gender']) : ''; ?></td>
                                <td><?php echo htmlspecialchars($p['contact_number'] ?? '—'); ?></td>
                                <td>
                                    <?php if ($p['priority_level']): ?>
                                        <span class="badge priority-<?php echo $p['priority_level']; ?>"><?php echo ucfirst($p['priority_level']); ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Not set</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $p['last_visit'] ? date('M d, Y', strtotime($p['last_visit'])) : 'First visit'; ?></td>
                                <td style="text-align:right;">
                                    <a class="btn btn-primary btn-sm" href="<?php echo BASE_URL; ?>/pages/doctor/new_consultation.php?patient_id=<?php echo (int)$p['patient_id']; ?>">Start consultation</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($recent): ?>
    <div class="card">
        <div class="card-header"><h3>Your recent consultations</h3></div>
        <div class="card-body p-0">
            <div class="table-container">
                <table>
                    <thead><tr><th>Date</th><th>Patient</th><th>Chief complaint</th><th>Diagnosis</th><th style="text-align:right;">Action</th></tr></thead>
                    <tbody>
                    <?php foreach ($recent as $c): ?>
                        <tr>
                            <td><?php echo date('M d, Y g:i A', strtotime($c['consultation_date'])); ?></td>
                            <td><?php echo htmlspecialchars($c['last_name'] . ', ' . $c['first_name']); ?></td>
                            <td><?php echo htmlspecialchars(mb_strimwidth(html_entity_decode($c['chief_complaint'] ?? '', ENT_QUOTES), 0, 60, '…')); ?></td>
                            <td>
                                <?php if ($c['diagnosis_count'] > 0): ?>
                                    <span class="badge badge-success">Recorded</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Not yet</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:right;">
                                <?php if ($c['diagnosis_count'] > 0): ?>
                                    <a class="btn btn-outline btn-sm" href="<?php echo BASE_URL; ?>/pages/doctor/view_consultation.php?id=<?php echo (int)$c['consultation_id']; ?>">View</a>
                                <?php else: ?>
                                    <a class="btn btn-primary btn-sm" href="<?php echo BASE_URL; ?>/pages/doctor/consultation_diagnosis.php?consultation_id=<?php echo (int)$c['consultation_id']; ?>">Add diagnosis</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php
    require_once __DIR__ . '/../../includes/footer.php';
    exit();
}

/* =====================================================================
 * STEP 2 - record the consultation
 * ===================================================================== */
$stmt = $conn->prepare("SELECT * FROM patients WHERE patient_id = ?");
$stmt->execute([$patient_id]);
$patient = $stmt->fetch();

if (!$patient) {
    require_once __DIR__ . '/../../includes/header.php';
    echo '<div class="alert alert-danger">Patient not found. <a href="' . BASE_URL . '/pages/doctor/new_consultation.php">Choose another patient</a>.</div>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit();
}

// Previous consultation (helps the doctor compare)
$stmt = $conn->prepare("
    SELECT c.consultation_date, c.chief_complaint, c.vital_signs, u.full_name AS doctor_name
    FROM consultations c JOIN users u ON c.doctor_id = u.user_id
    WHERE c.patient_id = ? ORDER BY c.consultation_date DESC LIMIT 1
");
$stmt->execute([$patient_id]);
$last_consult = $stmt->fetch();

// Vital-sign fields: name => [label, unit, placeholder, input attributes]
$vital_fields = [
    'bp'     => ['Blood pressure', 'mmHg', '120/80', 'inputmode="numeric" pattern="\d{2,3}/\d{2,3}" title="Systolic/diastolic, e.g. 120/80"'],
    'hr'     => ['Heart rate', 'bpm', '72', 'type="number" min="20" max="250"'],
    'temp'   => ['Temperature', '°C', '36.8', 'type="number" step="0.1" min="30" max="45"'],
    'rr'     => ['Respiratory rate', '/min', '16', 'type="number" min="4" max="80"'],
    'spo2'   => ['Oxygen saturation (SpO₂)', '%', '98', 'type="number" min="50" max="100"'],
    'weight' => ['Weight', 'kg', '60', 'type="number" step="0.1" min="0.5" max="400"'],
];
$vital_labels = ['bp' => 'BP', 'hr' => 'HR', 'temp' => 'Temp', 'rr' => 'RR', 'spo2' => 'SpO2', 'weight' => 'Weight'];
$vital_units  = ['bp' => ' mmHg', 'hr' => ' bpm', 'temp' => '°C', 'rr' => '/min', 'spo2' => '%', 'weight' => ' kg'];

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $chief_complaint = sanitizeInput($_POST['chief_complaint'] ?? '');
    $notes = sanitizeInput($_POST['notes'] ?? '');

    // Build the vital-signs text from the individual boxes, e.g.
    // "BP: 120/80 mmHg, HR: 72 bpm, Temp: 36.8°C"
    $vital_parts = [];
    foreach ($vital_labels as $key => $label) {
        $value = trim($_POST['vital_' . $key] ?? '');
        if ($value === '') {
            continue;
        }
        if ($key === 'bp' ? !preg_match('/^\d{2,3}\/\d{2,3}$/', $value) : !is_numeric($value)) {
            $error = 'Please check the value entered for ' . strtolower($vital_fields[$key][0]) . '.';
            break;
        }
        $vital_parts[] = $label . ': ' . $value . $vital_units[$key];
    }
    $other_vitals = sanitizeInput($_POST['vital_other'] ?? '');
    if ($other_vitals !== '') {
        $vital_parts[] = $other_vitals;
    }
    $vital_signs = implode(', ', $vital_parts);

    if (!$error && empty($chief_complaint)) {
        $error = 'Chief complaint is required - write the main reason the patient came in today.';
    }

    if (!$error) {
        try {
            $stmt = $conn->prepare("
                INSERT INTO consultations (patient_id, doctor_id, chief_complaint, vital_signs, notes)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$patient_id, $doctor_id, $chief_complaint, $vital_signs ?: null, $notes ?: null]);
            $consultation_id = $conn->lastInsertId();

            // Next step: diagnosis & treatment
            header('Location: ' . BASE_URL . '/pages/doctor/consultation_diagnosis.php?consultation_id=' . $consultation_id);
            exit();
        } catch (PDOException $e) {
            $error = 'Error recording consultation. Please try again.';
            error_log("Consultation error: " . $e->getMessage());
        }
    }
}

$age = $patient['date_of_birth'] ? date_diff(date_create($patient['date_of_birth']), date_create('today'))->y . ' yrs' : 'N/A';
$has_allergy = !empty($patient['allergies']) && strtolower(trim($patient['allergies'])) !== 'none';

// Render the page header only after any redirect has had a chance to run
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-container">
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title-group">
                <div class="page-title-icon doctor">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                </div>
                <div>
                    <h1 class="page-title">New Consultation</h1>
                    <p class="page-subtitle">Step 2 of 3 · <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></p>
                </div>
            </div>
            <div class="page-header-actions">
                <a href="<?php echo BASE_URL; ?>/pages/doctor/new_consultation.php" class="btn btn-outline btn-sm">Change patient</a>
                <a href="<?php echo BASE_URL; ?>/pages/doctor/view_patient.php?id=<?php echo $patient_id; ?>" class="btn btn-outline btn-sm">Patient record</a>
            </div>
        </div>
    </div>

    <ol class="consult-steps" aria-label="Consultation steps">
        <li class="is-done"><span>✓</span> Choose patient</li>
        <li class="is-current"><span>2</span> Consultation notes</li>
        <li><span>3</span> Diagnosis &amp; treatment</li>
    </ol>

    <!-- Patient summary -->
    <div class="card consult-patient">
        <div>
            <strong><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></strong>
            <span class="consult-muted">#<?php echo str_pad($patient_id, 4, '0', STR_PAD_LEFT); ?></span>
            <div class="consult-muted">
                Age: <?php echo $age; ?> · Gender: <?php echo $patient['gender'] ? ucfirst($patient['gender']) : 'N/A'; ?> · Blood type: <?php echo htmlspecialchars($patient['blood_type'] ?: 'N/A'); ?>
            </div>
        </div>
        <div class="consult-allergy <?php echo $has_allergy ? 'is-alert' : ''; ?>">
            <span>Allergies</span>
            <strong><?php echo htmlspecialchars($patient['allergies'] ?: 'None reported'); ?></strong>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <svg class="alert-icon" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <div><?php echo htmlspecialchars($error); ?></div>
        </div>
    <?php endif; ?>

    <div class="consult-layout">
        <!-- Form -->
        <div class="card">
            <div class="card-header"><h3>Consultation notes</h3></div>
            <div class="card-body">
                <form method="POST" action="" id="consultForm">
                    <div class="form-group">
                        <label for="chief_complaint">1. Chief complaint <span class="required">*</span></label>
                        <p class="consult-hint">The main reason the patient came in, in their own words, with how long it has been happening.</p>
                        <textarea id="chief_complaint" name="chief_complaint" required rows="3"
                                  placeholder="e.g. Fever and cough for 3 days, with body weakness"><?php echo htmlspecialchars(html_entity_decode($_POST['chief_complaint'] ?? '', ENT_QUOTES)); ?></textarea>
                        <div class="consult-chips" aria-label="Common complaints - click to add">
                            <?php foreach (['Fever', 'Cough', 'Headache', 'Body pain', 'Difficulty breathing', 'Stomach pain', 'Dizziness', 'Follow-up check-up', 'BP monitoring'] as $chip): ?>
                                <button type="button" class="consult-chip" data-insert="<?php echo htmlspecialchars($chip); ?>"><?php echo htmlspecialchars($chip); ?></button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <fieldset class="consult-fieldset">
                        <legend>2. Vital signs <span class="consult-optional">(fill in what you measured)</span></legend>
                        <div class="consult-vitals">
                            <?php foreach ($vital_fields as $key => [$label, $unit, $placeholder, $attrs]): ?>
                                <div class="consult-vital">
                                    <label for="vital_<?php echo $key; ?>"><?php echo $label; ?></label>
                                    <div class="consult-unit-input">
                                        <input id="vital_<?php echo $key; ?>" name="vital_<?php echo $key; ?>" <?php echo strpos($attrs, 'type=') === false ? 'type="text" ' : ''; ?><?php echo $attrs; ?>
                                               placeholder="<?php echo $placeholder; ?>" value="<?php echo htmlspecialchars($_POST['vital_' . $key] ?? ''); ?>">
                                        <span><?php echo $unit; ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="form-group" style="margin-top:0.75rem;">
                            <label for="vital_other">Other measurements</label>
                            <input type="text" id="vital_other" name="vital_other" placeholder="e.g. Blood sugar (RBS): 145 mg/dL, Height: 160 cm"
                                   value="<?php echo htmlspecialchars(html_entity_decode($_POST['vital_other'] ?? '', ENT_QUOTES)); ?>">
                        </div>
                    </fieldset>

                    <div class="form-group">
                        <label for="notes">3. Examination notes <span class="consult-optional">(optional)</span></label>
                        <p class="consult-hint">What you found when examining the patient, and any history worth noting.</p>
                        <textarea id="notes" name="notes" rows="4"
                                  placeholder="e.g. Throat slightly red, no swollen glands. Clear breath sounds. No known contact with sick persons."><?php echo htmlspecialchars(html_entity_decode($_POST['notes'] ?? '', ENT_QUOTES)); ?></textarea>
                    </div>

                    <div class="btn-group" style="margin-top: 1rem;">
                        <button type="submit" class="btn btn-primary">Save &amp; continue to diagnosis →</button>
                        <a href="<?php echo BASE_URL; ?>/pages/doctor/new_consultation.php" class="btn btn-secondary">Cancel</a>
                    </div>
                    <p class="consult-muted" style="margin-top:0.75rem;">Next you will record the diagnosis and treatment, then you can create a prescription.</p>
                </form>
            </div>
        </div>

        <!-- Guide -->
        <aside class="consult-guide">
            <div class="card">
                <div class="card-header"><h3>What to write</h3></div>
                <div class="card-body">
                    <dl class="consult-guide-list">
                        <dt>Chief complaint</dt>
                        <dd>Symptom + duration. <em>“Headache for 2 days, worse in the morning.”</em></dd>
                        <dt>Vital signs</dt>
                        <dd>Only the ones you took. Normal adult ranges: BP below 120/80, HR 60–100, Temp 36.1–37.2 °C, RR 12–20, SpO₂ 95–100%.</dd>
                        <dt>Examination notes</dt>
                        <dd>What you saw or heard on examination, plus relevant history. <em>“Wheezing on both lungs. Uses inhaler 3×/week.”</em></dd>
                        <dt>Diagnosis &amp; medicines</dt>
                        <dd>Not here — that is the next page.</dd>
                    </dl>
                </div>
            </div>

            <?php if ($last_consult): ?>
            <div class="card">
                <div class="card-header"><h3>Last visit</h3></div>
                <div class="card-body consult-last">
                    <div class="consult-muted"><?php echo date('M d, Y', strtotime($last_consult['consultation_date'])); ?> · <?php echo htmlspecialchars($last_consult['doctor_name']); ?></div>
                    <p><strong>Complaint:</strong> <?php echo htmlspecialchars(html_entity_decode($last_consult['chief_complaint'] ?? '—', ENT_QUOTES)); ?></p>
                    <?php if ($last_consult['vital_signs']): ?>
                        <p><strong>Vitals:</strong> <?php echo htmlspecialchars(html_entity_decode($last_consult['vital_signs'], ENT_QUOTES)); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </aside>
    </div>
</div>

<script>
// Clicking a common complaint adds it to the chief complaint box
document.querySelectorAll('.consult-chip').forEach(function (chip) {
    chip.addEventListener('click', function () {
        var box = document.getElementById('chief_complaint');
        var text = chip.getAttribute('data-insert');
        box.value = box.value.trim() ? box.value.trim().replace(/[,.]$/, '') + ', ' + text.toLowerCase() : text;
        box.focus();
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
