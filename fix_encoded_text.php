<?php
require_once __DIR__ . '/includes/local_only.php';
/**
 * One-time cleanup: older versions of MedCare saved text HTML-encoded
 * (e.g. "Patient&#039;s" instead of "Patient's"). This turns those values
 * back into normal text. Safe to run more than once. Localhost only.
 */
require_once __DIR__ . '/config/config.php';

$columns = [
    'patients' => ['patient_id', ['first_name', 'last_name', 'contact_number', 'address', 'emergency_contact_name', 'emergency_contact_number', 'blood_type', 'allergies']],
    'patient_medical_history' => ['history_id', ['condition_name', 'notes']],
    'consultations' => ['consultation_id', ['chief_complaint', 'vital_signs', 'notes']],
    'patient_priority' => ['priority_id', ['urgency_reason']],
    'diagnoses' => ['diagnosis_id', ['diagnosis_code', 'diagnosis_name', 'description']],
    'treatments' => ['treatment_id', ['treatment_name', 'description', 'instructions']],
    'prescriptions' => ['prescription_id', ['notes']],
    'prescription_items' => ['item_id', ['medication_name', 'dosage', 'frequency', 'duration', 'instructions']],
    'inventory' => ['inventory_id', ['item_name', 'category', 'description', 'unit', 'batch_number', 'location', 'manufacturer']],
    'inventory_transactions' => ['transaction_id', ['notes']],
    'treatment_progress' => ['progress_id', ['progress_notes']],
    'healthcare_communication' => ['message_id', ['subject', 'message']],
    'health_education' => ['resource_id', ['title', 'category', 'content', 'summary', 'author']],
    'appointments' => ['appointment_id', ['reason', 'notes']],
];

$conn = (new Database())->getConnection();
echo "<h1>Fix encoded text</h1><ul>";
$total = 0;
foreach ($columns as $table => [$pk, $cols]) {
    foreach ($cols as $col) {
        $rows = $conn->query("SELECT `$pk` AS id, `$col` AS v FROM `$table` WHERE `$col` LIKE '%&%;%'")->fetchAll();
        $update = $conn->prepare("UPDATE `$table` SET `$col` = ? WHERE `$pk` = ?");
        $changed = 0;
        foreach ($rows as $row) {
            $decoded = html_entity_decode($row['v'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($decoded !== $row['v']) {
                $update->execute([$decoded, $row['id']]);
                $changed++;
            }
        }
        if ($changed) {
            echo '<li>' . htmlspecialchars("$table.$col") . ": $changed row(s) fixed</li>";
            $total += $changed;
        }
    }
}
echo "</ul><p><strong>Done.</strong> $total value(s) fixed.</p>";
echo "<p><a href='index.php'>Back to MedCare</a></p>";
