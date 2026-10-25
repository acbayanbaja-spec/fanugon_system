# MedCare – Bug Fix Log (October 2026)

All fixes were tested end to end (login as doctor, staff and patient; every page
opened; every form submitted) against a MySQL-compatible database running in
strict mode, the same mode XAMPP's MySQL/MariaDB uses.

## Important: re-import the database
`database/schema.sql` changed (sample stock history). Re-import it in phpMyAdmin.
It can now be imported again at any time — it resets the database to the sample data.

## Broken features (now fixed)

| Area | Problem | Fix |
|---|---|---|
| New Consultation, Diagnosis, New Prescription | The page printed the HTML header before saving, so the redirect afterwards failed ("Cannot modify header information – headers already sent"). Users saw a warning and often re-submitted, creating duplicate consultations. | Header is now loaded after the form is handled (`pages/doctor/new_consultation.php`, `consultation_diagnosis.php`, `new_prescription.php`). |
| Diagnosis & Treatment | Saving failed with "Error recording diagnosis" when Severity or the treatment dates were left blank (blank text is not allowed in ENUM/DATE columns in strict mode). | Blank optional fields are stored as NULL; end date can't be before start date. |
| Edit Patient (staff) | Saving failed if Date of Birth or Gender was blank (same cause). | Blank fields stored as NULL. |
| Add Stock | Saving failed if "Reference Type" was left blank; an adjustment could never set stock to 0 (e.g. writing off expired stock). | Blank reference → NULL; adjustment to 0 allowed and logged as "Adjusted from X to Y". Row is locked while updating. |
| Dispense Prescription | Prescription was marked **dispensed** even when one medicine was left at 0, or when nothing was dispensed at all. | Status becomes "dispensed" only when every item is fully dispensed; an all-zero submit shows an error. Stock deduction is now atomic. |
| New Prescription | Opening it without a consultation tried to save NULL into a required column. The medicine name was taken from the browser. | Requires a valid consultation of that patient; medicine name comes from inventory; quantity must be ≥ 1. |
| Patient lists / dashboards | After a doctor changed a patient's priority, that patient appeared twice (once per priority record) and priority counts were inflated. | Only the latest priority record is used (doctor & staff patient lists, dashboards, patient dashboard). |
| Staff dashboard | "Pending prescriptions" counted status `'active'`, which doesn't exist, so it always showed 0. | Counts `'pending'`. |
| Health Education (staff) | Creating a resource while a search/filter was active showed "Error creating resource" even though it was saved. Published resources had no publish date. | Uses the same filtered query to refresh; publish date set. |
| Edit Inventory | Batch number and expiration date could not be corrected. | Fields added to the edit form. |
| Edit Appointment (doctor) | Editing an existing appointment could fail time validation (database time has seconds), and any status change was allowed (e.g. completed → scheduled). | Time normalised; same status rules as the calendar. |
| Team Messages | Your own "All Staff" broadcasts appeared in your own inbox. | Excluded from your inbox (still under Sent). |
| Many pages | Pages showed "Deprecated: Passing null to htmlspecialchars()" for empty fields (e.g. self-registered patients with no birth date). | Output calls are null-safe. |

## Layout
* The sidebar had no styles (the stylesheet was written for an older top-menu layout). Sidebar styles added, with the active page highlighted.
* On phones the "Menu" button did nothing and the sidebar squeezed the page. The sidebar now slides in from the left.
* Bootstrap's JavaScript is now loaded, and the layout `<div>`s opened in `header.php` are closed in `footer.php`.

## Security
* Maintenance scripts (`setup.php`, `fix_passwords.php`, `reset_passwords.php`, `diagnose_login.php`, `check_db.php`, `test_connection.php`, `integration_test.php`) could be opened by anyone on the network — e.g. resetting every password. They now only work from the server itself (`http://localhost/...`). Delete them before a real deployment.
* `fix_passwords.php` crashed (undefined `$users`) — fixed.
* Session ID is regenerated on login (prevents session fixation).
* Inputs for priority, gender, message type, resource status, item type, etc. are validated against allowed values.
* `config/database.php` now reads the credentials from `config/config.php`, so they only need changing in one place.
