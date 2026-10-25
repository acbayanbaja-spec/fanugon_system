<?php
/**
 * MedCare System - Patient: My Appointments
 *
 * Lets a patient request an appointment with a doctor. The record is written to
 * the existing `appointments` table (the single source of truth), so it appears
 * automatically on the selected doctor's calendar.
 */
require_once __DIR__ . '/../../includes/auth.php';
requireRole('patient');

require_once __DIR__ . '/../../config/config.php';

$database = new Database();
$conn = $database->getConnection();

$today = date('Y-m-d');
$valid_types = ['consultation', 'follow_up', 'treatment', 'vaccination', 'screening', 'other'];

// Resolve this patient's own record (authorization: patient acts on self only)
$stmt = $conn->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$patient = $stmt->fetch();

if (!$patient) {
    require_once __DIR__ . '/../../includes/header.php';
    echo '<div class="page-container"><div class="alert alert-danger">Patient record not found. Please contact staff.</div></div>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit();
}
$patient_id = (int)$patient['patient_id'];

// Doctors available for booking
$stmt = $conn->prepare("SELECT user_id, full_name FROM users WHERE role = 'doctor' AND is_active = 1 ORDER BY full_name");
$stmt->execute();
$doctors = $stmt->fetchAll();

$form = [
    'doctor_id'        => 0,
    'appointment_date' => $today,
    'appointment_time' => '09:00',
    'appointment_type' => 'consultation',
    'reason'           => '',
];
$errors = [];

// --- Create appointment -----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'request_appointment') {
    $form['doctor_id']        = (int)($_POST['doctor_id'] ?? 0);
    $form['appointment_date'] = trim($_POST['appointment_date'] ?? '');
    $form['appointment_time'] = trim($_POST['appointment_time'] ?? '');
    $form['appointment_type'] = $_POST['appointment_type'] ?? 'consultation';
    $form['reason']           = sanitizeInput($_POST['reason'] ?? '');

    // Validate the selected account actually has the doctor role
    if ($form['doctor_id'] <= 0) {
        $errors[] = 'Please select a doctor.';
    } else {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE user_id = ? AND role = 'doctor'");
        $stmt->execute([$form['doctor_id']]);
        if (!$stmt->fetch()) {
            $errors[] = 'The selected doctor is not available.';
        }
    }

    $date_value = DateTime::createFromFormat('Y-m-d', $form['appointment_date']);
    if (!$date_value || $date_value->format('Y-m-d') !== $form['appointment_date']) {
        $errors[] = 'Please provide a valid appointment date.';
    } elseif ($form['appointment_date'] < $today) {
        $errors[] = 'Please choose a date that is not in the past.';
    }

    $time_value = DateTime::createFromFormat('H:i', $form['appointment_time']);
    if (!$time_value || $time_value->format('H:i') !== $form['appointment_time']) {
        $errors[] = 'Please provide a valid appointment time (HH:MM).';
    }

    if (!in_array($form['appointment_type'], $valid_types, true)) {
        $errors[] = 'Please select a valid appointment type.';
    }

    // Reuse the double-booking rule applied by the doctor's booking form
    if (empty($errors)) {
        $start_seconds = ((int)substr($form['appointment_time'], 0, 2) * 3600)
                       + ((int)substr($form['appointment_time'], 3, 2) * 60);
        $end_seconds = $start_seconds + (30 * 60);

        $stmt = $conn->prepare("
            SELECT COUNT(*) FROM appointments
            WHERE doctor_id = ? AND appointment_date = ? AND status <> 'cancelled'
              AND TIME_TO_SEC(appointment_time) < ?
              AND (TIME_TO_SEC(appointment_time) + (duration_minutes * 60)) > ?
        ");
        $stmt->execute([$form['doctor_id'], $form['appointment_date'], $end_seconds, $start_seconds]);
        if ((int)$stmt->fetchColumn() > 0) {
            $errors[] = 'That time is already taken. Please choose another time.';
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("
                INSERT INTO appointments (
                    patient_id, doctor_id, appointment_date, appointment_time,
                    duration_minutes, appointment_type, status, reason, notes
                ) VALUES (?, ?, ?, ?, 30, ?, 'scheduled', ?, NULL)
            ");
            $stmt->execute([
                $patient_id,
                $form['doctor_id'],
                $form['appointment_date'],
                $form['appointment_time'] . ':00',
                $form['appointment_type'],
                !empty($form['reason']) ? $form['reason'] : null,
            ]);

            header('Location: ' . BASE_URL . '/pages/patient/appointments.php?requested=1');
            exit();
        } catch (PDOException $e) {
            error_log("Patient appointment request error: " . $e->getMessage());
            $errors[] = 'Unable to save the appointment. Please try again.';
        }
    }
}
// --- Cancel own appointment -------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel') {
    $appointment_id = (int)($_POST['appointment_id'] ?? 0);
    if ($appointment_id > 0) {
        try {
            // Ownership is enforced by the patient_id filter
            $stmt = $conn->prepare("
                UPDATE appointments SET status = 'cancelled'
                WHERE appointment_id = ? AND patient_id = ? AND status IN ('scheduled', 'confirmed')
            ");
            $stmt->execute([$appointment_id, $patient_id]);
        } catch (PDOException $e) {
            error_log("Patient appointment cancel error: " . $e->getMessage());
        }
    }
    header('Location: ' . BASE_URL . '/pages/patient/appointments.php');
    exit();
}

// --- List the patient's appointments ---------------------------------------
try {
    $stmt = $conn->prepare("
        SELECT a.*, u.full_name AS doctor_name
        FROM appointments a
        JOIN users u ON a.doctor_id = u.user_id
        WHERE a.patient_id = ?
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
    ");
    $stmt->execute([$patient_id]);
    $appointments = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Patient appointment list error: " . $e->getMessage());
    $appointments = [];
}

// Rendered only after every redirect has been issued (POST/redirect/GET)
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-container">

    <?php if (isset($_GET['requested'])): ?>
        <div class="alert alert-success">
            <svg class="alert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"></path><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 012-2V5a2 2 0 012-2h11"></path></svg>
            <div>Your appointment request has been saved. It is now visible on your doctor's calendar.</div>
        </div>
    <?php endif; ?>

    <div class="page-header">
        <div class="page-header-content">
            <div class="page-title-group">
                <div class="page-title-icon doctor">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </div>
                <div>
                    <h1 class="page-title">My Appointments</h1>
                    <p class="page-subtitle">Request a visit and follow it on your doctor's schedule.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-two-col">

        <!-- Request form -->
        <div class="card">
            <div class="card-header">
                <h3>Request an Appointment</h3>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul style="margin:0;padding-left:1.1rem;">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if (empty($doctors)): ?>
                    <div class="empty-state small">No doctors are currently available for booking.</div>
                <?php else: ?>
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="request_appointment">

                        <div class="form-group">
                            <label for="doctor_id">Doctor <span class="required">*</span></label>
                            <select id="doctor_id" name="doctor_id" required>
                                <option value="">-- Select a doctor --</option>
                                <?php foreach ($doctors as $doctor): ?>
                                    <option value="<?php echo (int)$doctor['user_id']; ?>" <?php echo $form['doctor_id'] === (int)$doctor['user_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($doctor['full_name'] ?? ''); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="appointment_date">Date <span class="required">*</span></label>
                                <input type="date" id="appointment_date" name="appointment_date" min="<?php echo $today; ?>"
                                       value="<?php echo htmlspecialchars($form['appointment_date'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="appointment_time">Time <span class="required">*</span></label>
                                <input type="time" id="appointment_time" name="appointment_time"
                                       value="<?php echo htmlspecialchars($form['appointment_time'] ?? ''); ?>" required>
                            </div>
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

                        <div class="form-group">
                            <label for="reason">Reason for Visit</label>
                            <input type="text" id="reason" name="reason" placeholder="e.g. General consultation"
                                   value="<?php echo htmlspecialchars($form['reason'] ?? ''); ?>">
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:6px;vertical-align:-3px"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            Request Appointment
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Appointment list -->
        <div class="card">
            <div class="card-header">
                <h3>Appointment History</h3>
            </div>
            <div class="card-body p-0">
                <?php if (empty($appointments)): ?>
                    <div class="empty-state small">No appointments scheduled.</div>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Doctor</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th style="text-align:right;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($appointments as $appt): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($appt['appointment_date'])); ?></td>
                                        <td><?php echo date('g:i A', strtotime($appt['appointment_time'])); ?></td>
                                        <td><?php echo htmlspecialchars($appt['doctor_name'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($appt['reason'] ?: '—'); ?></td>
                                        <td>
                                            <span class="badge appt-status-<?php echo htmlspecialchars($appt['status'] ?? ''); ?>">
                                                <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $appt['status'] ?? ''))); ?>
                                            </span>
                                        </td>
                                        <td style="text-align:right;">
                                            <?php if (in_array($appt['status'], ['scheduled', 'confirmed'], true)): ?>
                                                <form method="POST" action="">
                                                    <input type="hidden" name="action" value="cancel">
                                                    <input type="hidden" name="appointment_id" value="<?php echo (int)$appt['appointment_id']; ?>">
                                                    <button type="submit" class="btn btn-outline btn-sm">Cancel</button>
                                                </form>
                                            <?php endif; ?>
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