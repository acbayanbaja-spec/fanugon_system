<?php
/**
 * MedCare System - Doctor: New / Edit Appointment
 * Used by the appointment calendar to schedule (or reschedule) a patient visit.
 */
require_once __DIR__ . '/../../includes/auth.php';
requireRole('doctor');

require_once __DIR__ . '/../../config/config.php';

$database = new Database();
$conn = $database->getConnection();

$doctor_id = (int)$_SESSION['user_id'];
$today = date('Y-m-d');

$valid_types    = ['consultation', 'follow_up', 'treatment', 'vaccination', 'screening', 'other'];
$valid_statuses = ['scheduled', 'confirmed', 'completed', 'cancelled', 'no_show'];

$appointment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$edit_mode      = $appointment_id > 0;

// Patients available for scheduling
$patients = $conn->query("
    SELECT patient_id, first_name, last_name, contact_number
    FROM patients
    ORDER BY last_name, first_name
")->fetchAll();

// Current form values
$form = [
    'patient_id'       => 0,
    'appointment_date' => (isset($_GET['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date'])) ? $_GET['date'] : $today,
    'appointment_time' => '09:00',
    'duration_minutes' => 30,
    'appointment_type' => 'consultation',
    'status'           => 'scheduled',
    'reason'           => '',
    'notes'            => '',
];

$errors = [];

if ($edit_mode) {
    // Ownership is enforced server-side so a doctor cannot open or edit
    // an appointment belonging to another doctor by changing the id.
    $stmt = $conn->prepare("SELECT * FROM appointments WHERE appointment_id = ? AND doctor_id = ?");
    $stmt->execute([$appointment_id, $doctor_id]);
    $existing = $stmt->fetch();

    if (!$existing) {
        require_once __DIR__ . '/../../includes/header.php';
        echo '<div class="alert alert-danger">Appointment not found.</div>';
        require_once __DIR__ . '/../../includes/footer.php';
        exit();
    }

    foreach (array_keys($form) as $field) {
        if (isset($existing[$field])) {
            $form[$field] = $existing[$field];
        }
    }
    // Normalise DB values so they match the form inputs (<input type="time"> expects HH:MM)
    $form['patient_id']       = (int)$form['patient_id'];
    $form['duration_minutes'] = (int)$form['duration_minutes'];
    $form['appointment_time'] = substr($form['appointment_time'], 0, 5);
} elseif (isset($_GET['patient_id'])) {
    $form['patient_id'] = (int)$_GET['patient_id'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['patient_id']       = (int)($_POST['patient_id'] ?? 0);
    $form['appointment_date'] = trim($_POST['appointment_date'] ?? '');
    $form['appointment_time'] = substr(trim($_POST['appointment_time'] ?? ''), 0, 5); // drop seconds if the browser sends HH:MM:SS
    $form['duration_minutes'] = (int)($_POST['duration_minutes'] ?? 30);
    $form['appointment_type'] = $_POST['appointment_type'] ?? 'consultation';
    $form['status']           = $_POST['status'] ?? 'scheduled';
    $form['reason']           = sanitizeInput($_POST['reason'] ?? '');
    $form['notes']            = sanitizeInput($_POST['notes'] ?? '');

    // --- Validation -------------------------------------------------------
    if ($form['patient_id'] <= 0) {
        $errors[] = 'Please select a patient.';
    } else {
        $stmt = $conn->prepare("SELECT patient_id FROM patients WHERE patient_id = ?");
        $stmt->execute([$form['patient_id']]);
        if (!$stmt->fetch()) {
            $errors[] = 'The selected patient could not be found.';
        }
    }

    $date_value = DateTime::createFromFormat('Y-m-d', $form['appointment_date']);
    if (!$date_value || $date_value->format('Y-m-d') !== $form['appointment_date']) {
        $errors[] = 'Please provide a valid appointment date.';
    }

    $time_value = DateTime::createFromFormat('H:i', $form['appointment_time']);
    if (!$time_value || $time_value->format('H:i') !== $form['appointment_time']) {
        $errors[] = 'Please provide a valid appointment time (HH:MM).';
    }

    if ($form['duration_minutes'] < 5 || $form['duration_minutes'] > 480) {
        $errors[] = 'Duration must be between 5 and 480 minutes.';
        $form['duration_minutes'] = 30;
    }

    if (!in_array($form['appointment_type'], $valid_types, true)) {
        $errors[] = 'Please select a valid appointment type.';
    }

    if (!in_array($form['status'], $valid_statuses, true)) {
        $errors[] = 'Please select a valid status.';
    } elseif ($edit_mode) {
        // Same transition rules as the calendar (e.g. a completed visit cannot be reopened)
        $allowed_transitions = [
            'scheduled' => ['scheduled', 'confirmed', 'completed', 'cancelled', 'no_show'],
            'confirmed' => ['confirmed', 'completed', 'cancelled', 'no_show'],
            'completed' => ['completed'],
            'cancelled' => ['cancelled', 'scheduled'],
            'no_show'   => ['no_show', 'completed', 'scheduled'],
        ];
        if (!in_array($form['status'], $allowed_transitions[$existing['status']] ?? [], true)) {
            $errors[] = 'An appointment that is ' . str_replace('_', ' ', $existing['status'] ?? '')
                . ' cannot be changed to ' . str_replace('_', ' ', $form['status'] ?? '') . '.';
        }
    }

    // --- Double booking check --------------------------------------------
    if (empty($errors) && $form['status'] !== 'cancelled') {
        // Compare in seconds-from-midnight so both sides share the same scale
        $new_start = strtotime($form['appointment_time']);
        $start_seconds = ((int)date('H', $new_start) * 3600) + ((int)date('i', $new_start) * 60);
        $end_seconds   = $start_seconds + ($form['duration_minutes'] * 60);

        $stmt = $conn->prepare("
            SELECT a.appointment_time, p.first_name, p.last_name
            FROM appointments a
            JOIN patients p ON a.patient_id = p.patient_id
            WHERE a.doctor_id = ?
              AND a.appointment_date = ?
              AND a.status <> 'cancelled'
              AND a.appointment_id <> ?
              AND TIME_TO_SEC(a.appointment_time) < ?
              AND (TIME_TO_SEC(a.appointment_time) + (a.duration_minutes * 60)) > ?
            LIMIT 1
        ");
        $stmt->execute([
            $doctor_id,
            $form['appointment_date'],
            $appointment_id,
            $end_seconds,
            $start_seconds,
        ]);
        $conflict = $stmt->fetch();

        if ($conflict) {
            $errors[] = 'Time conflict: you already have an appointment with '
                . $conflict['first_name'] . ' ' . $conflict['last_name']
                . ' at ' . date('g:i A', strtotime($conflict['appointment_time'])) . '.';
        }
    }

    // --- Save -------------------------------------------------------------
    if (empty($errors)) {
        try {
            if ($edit_mode) {
                $stmt = $conn->prepare("
                    UPDATE appointments
                    SET patient_id = ?, appointment_date = ?, appointment_time = ?,
                        duration_minutes = ?, appointment_type = ?, status = ?,
                        reason = ?, notes = ?
                    WHERE appointment_id = ?
                ");
                $stmt->execute([
                    $form['patient_id'],
                    $form['appointment_date'],
                    $form['appointment_time'] . ':00',
                    $form['duration_minutes'],
                    $form['appointment_type'],
                    $form['status'],
                    !empty($form['reason']) ? $form['reason'] : null,
                    !empty($form['notes']) ? $form['notes'] : null,
                    $appointment_id,
                ]);
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO appointments (
                        patient_id, doctor_id, appointment_date, appointment_time,
                        duration_minutes, appointment_type, status, reason, notes
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $form['patient_id'],
                    $doctor_id,
                    $form['appointment_date'],
                    $form['appointment_time'] . ':00',
                    $form['duration_minutes'],
                    $form['appointment_type'],
                    $form['status'],
                    !empty($form['reason']) ? $form['reason'] : null,
                    !empty($form['notes']) ? $form['notes'] : null,
                ]);
            }

            header('Location: ' . BASE_URL . '/pages/doctor/calendar.php?date='
                . urlencode($form['appointment_date']) . '&saved=1'
                . ($edit_mode ? '&updated=1' : ''));
            exit();
        } catch (PDOException $e) {
            $errors[] = 'Error saving the appointment. Please try again.';
            error_log("Appointment save error: " . $e->getMessage());
        }
    }
}

$selected_patient = null;
foreach ($patients as $patient_row) {
    if ((int)$patient_row['patient_id'] === $form['patient_id']) {
        $selected_patient = $patient_row;
        break;
    }
}

// Rendered only after every redirect has been issued (POST/redirect/GET)
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-container">

    <!-- Page Header -->
    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title-group">
                <div class="page-title-icon doctor">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                </div>
                <div>
                    <h1 class="page-title"><?php echo $edit_mode ? 'Edit Appointment' : 'Schedule Appointment'; ?></h1>
                    <p class="page-subtitle">
                        <?php echo $edit_mode
                            ? 'Update the date, time or details of this patient visit.'
                            : 'Book a patient visit on your calendar schedule.'; ?>
                    </p>
                </div>
            </div>
            <div class="page-header-actions">
                <a href="<?php echo BASE_URL; ?>/pages/doctor/calendar.php?date=<?php echo htmlspecialchars($form['appointment_date'] ?? ''); ?>" class="btn btn-outline btn-sm">
                    <i class="bi bi-arrow-left" style="margin-right:6px;"></i> Back to Calendar
                </a>
            </div>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <svg class="alert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <div>
                <?php foreach ($errors as $error_message): ?>
                    <div><?php echo htmlspecialchars($error_message); ?></div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

<!-- Appointment Form -->
    <div class="card">
        <div class="card-header">
            <h3>Appointment Details</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <div class="form-group">
                    <label for="patient_id">Patient <span class="required">*</span></label>
                    <select id="patient_id" name="patient_id" required>
                        <option value="">Select a patient</option>
                        <?php foreach ($patients as $patient_row): ?>
                            <option value="<?php echo (int)$patient_row['patient_id']; ?>"
                                <?php echo $form['patient_id'] === (int)$patient_row['patient_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($patient_row['last_name'] . ', ' . $patient_row['first_name'] . ' (' . $patient_row['contact_number'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if ($selected_patient): ?>
                    <div class="alert alert-info" style="margin-bottom:1.25rem;">
                        <svg class="alert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                        <div>
                            <strong><?php echo htmlspecialchars($selected_patient['first_name'] . ' ' . $selected_patient['last_name']); ?></strong>
                            &bull; Contact: <?php echo htmlspecialchars($selected_patient['contact_number'] ?? ''); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="form-row">
                    <div class="form-group">
                        <label for="appointment_date">Appointment Date <span class="required">*</span></label>
                        <input type="date" id="appointment_date" name="appointment_date" required
                               min="<?php echo date('Y-m-d', strtotime('-1 year')); ?>"
                               max="<?php echo date('Y-m-d', strtotime('+1 year')); ?>"
                               value="<?php echo htmlspecialchars($form['appointment_date'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="appointment_time">Time <span class="required">*</span></label>
                        <input type="time" id="appointment_time" name="appointment_time" required
                               value="<?php echo htmlspecialchars(substr($form['appointment_time'], 0, 5)); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="duration_minutes">Duration</label>
                        <select id="duration_minutes" name="duration_minutes">
                            <?php foreach ([15, 20, 30, 45, 60, 90, 120] as $minutes): ?>
                                <option value="<?php echo $minutes; ?>" <?php echo (int)$form['duration_minutes'] === $minutes ? 'selected' : ''; ?>>
                                    <?php echo $minutes; ?> minutes
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="appointment_type">Appointment Type <span class="required">*</span></label>
                        <select id="appointment_type" name="appointment_type" required>
                            <?php foreach ($valid_types as $type_option): ?>
                                <option value="<?php echo $type_option; ?>" <?php echo $form['appointment_type'] === $type_option ? 'selected' : ''; ?>>
                                    <?php echo ucwords(str_replace('_', ' ', $type_option)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
<div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <?php foreach ($valid_statuses as $status_option): ?>
                            <option value="<?php echo $status_option; ?>" <?php echo $form['status'] === $status_option ? 'selected' : ''; ?>>
                                <?php echo ucwords(str_replace('_', ' ', $status_option)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="reason">Reason for Visit</label>
                    <input type="text" id="reason" name="reason" placeholder="e.g. Follow-up for blood pressure monitoring"
                           value="<?php echo htmlspecialchars($form['reason'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes" placeholder="Preparation instructions, reminders, or other details..."><?php echo htmlspecialchars($form['notes'] ?? ''); ?></textarea>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:6px;vertical-align:-3px"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                        <?php echo $edit_mode ? 'Save Changes' : 'Schedule Appointment'; ?>
                    </button>
                    <a href="<?php echo BASE_URL; ?>/pages/doctor/calendar.php?date=<?php echo htmlspecialchars($form['appointment_date'] ?? ''); ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>