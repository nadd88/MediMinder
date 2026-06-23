-- =====================================================================
--  MediMinder - Seed / Sample Data
--  Run AFTER schema.sql:
--    mysql -u root -p < schema.sql
--    mysql -u root -p mediminder < seed.sql
--
--  Demo login password for EVERY user below is:  password123
--  (stored as a bcrypt hash; verify with PHP password_verify()).
-- =====================================================================
USE mediminder;

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE audit_log;
TRUNCATE TABLE drug_interaction;
TRUNCATE TABLE notification;
TRUNCATE TABLE dose_log;
TRUNCATE TABLE prescription;
TRUNCATE TABLE medication;
TRUNCATE TABLE patient_caregiver;
TRUNCATE TABLE users;
SET FOREIGN_KEY_CHECKS = 1;

-- Users (1 admin, 1 caregiver, 4 patients)
INSERT INTO users (id, name, email, password_hash, role, dob) VALUES
(1, 'Dr. Rashid', 'admin@mediminder.app', '$2b$10$tmS1cTIo8gR8Hbq/HdlE0.t6y5kMiNtvJaCM4.JsPiH.OrgIn6tbG', 'Admin', NULL),
(2, 'Nur Rashidah', 'caregiver@mediminder.app', '$2b$10$tmS1cTIo8gR8Hbq/HdlE0.t6y5kMiNtvJaCM4.JsPiH.OrgIn6tbG', 'Caregiver', NULL),
(3, 'Sarah Tan', 'sarah@mediminder.app', '$2b$10$tmS1cTIo8gR8Hbq/HdlE0.t6y5kMiNtvJaCM4.JsPiH.OrgIn6tbG', 'Patient', '1958-04-22'),
(4, 'Ahmad Hamid', 'ahmad@mediminder.app', '$2b$10$tmS1cTIo8gR8Hbq/HdlE0.t6y5kMiNtvJaCM4.JsPiH.OrgIn6tbG', 'Patient', '1957-09-03'),
(5, 'Rosnah Mat', 'rosnah@mediminder.app', '$2b$10$tmS1cTIo8gR8Hbq/HdlE0.t6y5kMiNtvJaCM4.JsPiH.OrgIn6tbG', 'Patient', '1960-11-15'),
(6, 'Zainab Ali', 'zainab@mediminder.app', '$2b$10$tmS1cTIo8gR8Hbq/HdlE0.t6y5kMiNtvJaCM4.JsPiH.OrgIn6tbG', 'Patient', '1953-02-27');

-- Caregiver links (Nur Rashidah -> Ahmad, Rosnah, Zainab)
INSERT INTO patient_caregiver (patient_id, caregiver_id, status) VALUES
(4, 2, 'Active'),
(5, 2, 'Active'),
(6, 2, 'Active');

-- Medication master catalogue
INSERT INTO medication (id, name, form, strength, default_unit) VALUES
(1, 'Metformin',    'Tablet', '500mg', 'tablet'),
(2, 'Lisinopril',   'Tablet', '10mg',  'tablet'),
(3, 'Aspirin',      'Tablet', '75mg',  'tablet'),
(4, 'Atorvastatin', 'Tablet', '20mg',  'tablet');

-- Drug-interaction lookup (static, seeded - Objective 7). a_id < b_id.
INSERT INTO drug_interaction (medication_a_id, medication_b_id, severity, warning) VALUES
(1, 2, 'Moderate', 'Metformin + Lisinopril: may affect kidney function - monitor renal function and potassium.'),
(2, 3, 'Moderate', 'Lisinopril + Aspirin: NSAIDs/antiplatelets may reduce the blood-pressure-lowering effect of ACE inhibitors.');

-- Prescriptions for Ahmad Hamid (patient_id = 4) - admin demo patient
INSERT INTO prescription (id, patient_id, medication_id, dose, frequency, start_date, end_date, notes) VALUES
(1, 4, 1, '1 tablet', 'Twice daily',  '2025-01-01', '2025-06-30', 'Take with food'),
(2, 4, 2, '1 tablet', 'Once daily',   '2025-02-01', '2025-07-31', 'Take before meal'),
(3, 4, 3, '1 tablet', 'Once daily',   '2025-03-14', '2025-06-19', 'Take after meal'),
(4, 4, 4, '1 tablet', 'Once nightly', '2025-03-15', '2025-09-15', NULL);

-- Prescriptions for Sarah Tan (patient_id = 3)
INSERT INTO prescription (id, patient_id, medication_id, dose, frequency, start_date, end_date, notes) VALUES
(5, 3, 1, '1 tablet', 'Once daily', '2025-06-01', NULL, 'Take with food'),
(6, 3, 2, '1 tablet', 'Once daily', '2025-06-01', NULL, 'Take before meal'),
(7, 3, 3, '1 tablet', 'Once daily', '2025-06-01', NULL, 'Take after meal');

-- Dose logs (30-day window). Past 'Pending' rows are DERIVED as Missed.
INSERT INTO dose_log (prescription_id, scheduled_at, taken_at, status) VALUES
(1, '2025-05-16 08:00:00', '2025-05-16 07:55:00', 'Taken'),
(1, '2025-05-16 20:00:00', '2025-05-16 20:02:00', 'Taken'),
(1, '2025-05-17 08:00:00', '2025-05-17 08:18:00', 'Taken'),
(1, '2025-05-17 20:00:00', '2025-05-17 20:18:00', 'Taken'),
(1, '2025-05-18 08:00:00', '2025-05-18 07:57:00', 'Taken'),
(1, '2025-05-18 20:00:00', '2025-05-18 19:56:00', 'Taken'),
(1, '2025-05-19 08:00:00', '2025-05-19 08:01:00', 'Taken'),
(1, '2025-05-19 20:00:00', '2025-05-19 20:14:00', 'Taken'),
(1, '2025-05-20 08:00:00', '2025-05-20 08:01:00', 'Taken'),
(1, '2025-05-20 20:00:00', '2025-05-20 20:17:00', 'Taken'),
(1, '2025-05-21 08:00:00', '2025-05-21 08:02:00', 'Taken'),
(1, '2025-05-21 20:00:00', '2025-05-21 20:03:00', 'Taken'),
(1, '2025-05-22 08:00:00', '2025-05-22 07:55:00', 'Taken'),
(1, '2025-05-22 20:00:00', '2025-05-22 20:00:00', 'Taken'),
(1, '2025-05-23 08:00:00', '2025-05-23 08:05:00', 'Taken'),
(1, '2025-05-23 20:00:00', '2025-05-23 20:01:00', 'Taken'),
(1, '2025-05-24 08:00:00', NULL, 'Skipped'),
(1, '2025-05-24 20:00:00', '2025-05-24 19:57:00', 'Taken'),
(1, '2025-05-25 08:00:00', '2025-05-25 08:06:00', 'Taken'),
(1, '2025-05-25 20:00:00', '2025-05-25 20:14:00', 'Taken'),
(1, '2025-05-26 08:00:00', '2025-05-26 07:56:00', 'Taken'),
(1, '2025-05-26 20:00:00', '2025-05-26 20:12:00', 'Taken'),
(1, '2025-05-27 08:00:00', '2025-05-27 08:07:00', 'Taken'),
(1, '2025-05-27 20:00:00', '2025-05-27 20:04:00', 'Taken'),
(1, '2025-05-28 08:00:00', '2025-05-28 08:14:00', 'Taken'),
(1, '2025-05-28 20:00:00', '2025-05-28 20:06:00', 'Taken'),
(1, '2025-05-29 08:00:00', '2025-05-29 08:17:00', 'Taken'),
(1, '2025-05-29 20:00:00', '2025-05-29 20:16:00', 'Taken'),
(1, '2025-05-30 08:00:00', '2025-05-30 08:04:00', 'Taken'),
(1, '2025-05-30 20:00:00', NULL, 'Skipped'),
(1, '2025-05-31 08:00:00', '2025-05-31 07:58:00', 'Taken'),
(1, '2025-05-31 20:00:00', '2025-05-31 20:09:00', 'Taken'),
(1, '2025-06-01 08:00:00', '2025-06-01 08:06:00', 'Taken'),
(1, '2025-06-01 20:00:00', '2025-06-01 20:06:00', 'Taken'),
(1, '2025-06-02 08:00:00', '2025-06-02 08:03:00', 'Taken'),
(1, '2025-06-02 20:00:00', '2025-06-02 20:16:00', 'Taken'),
(1, '2025-06-03 08:00:00', '2025-06-03 08:14:00', 'Taken'),
(1, '2025-06-03 20:00:00', '2025-06-03 20:12:00', 'Taken'),
(1, '2025-06-04 08:00:00', '2025-06-04 08:00:00', 'Taken'),
(1, '2025-06-04 20:00:00', '2025-06-04 20:03:00', 'Taken'),
(1, '2025-06-05 08:00:00', NULL, 'Skipped'),
(1, '2025-06-05 20:00:00', '2025-06-05 20:12:00', 'Taken'),
(1, '2025-06-06 08:00:00', '2025-06-06 08:05:00', 'Taken'),
(1, '2025-06-06 20:00:00', '2025-06-06 19:56:00', 'Taken'),
(1, '2025-06-07 08:00:00', '2025-06-07 07:56:00', 'Taken'),
(1, '2025-06-07 20:00:00', '2025-06-07 20:07:00', 'Taken'),
(1, '2025-06-08 08:00:00', '2025-06-08 08:01:00', 'Taken'),
(1, '2025-06-08 20:00:00', '2025-06-08 20:13:00', 'Taken'),
(1, '2025-06-09 08:00:00', '2025-06-09 08:05:00', 'Taken'),
(1, '2025-06-09 20:00:00', '2025-06-09 20:10:00', 'Taken'),
(1, '2025-06-10 08:00:00', '2025-06-10 08:15:00', 'Taken'),
(1, '2025-06-10 20:00:00', '2025-06-10 20:03:00', 'Taken'),
(1, '2025-06-11 08:00:00', '2025-06-11 08:18:00', 'Taken'),
(1, '2025-06-11 20:00:00', '2025-06-11 20:03:00', 'Taken'),
(1, '2025-06-12 08:00:00', '2025-06-12 08:08:00', 'Taken'),
(1, '2025-06-12 20:00:00', '2025-06-12 20:07:00', 'Taken'),
(1, '2025-06-13 08:00:00', '2025-06-13 07:59:00', 'Taken'),
(1, '2025-06-13 20:00:00', '2025-06-13 19:57:00', 'Taken'),
(1, '2025-06-14 08:00:00', '2025-06-14 07:58:00', 'Taken'),
(1, '2025-06-14 20:00:00', '2025-06-14 20:00:00', 'Taken'),
(2, '2025-05-16 08:00:00', '2025-05-16 08:08:00', 'Taken'),
(2, '2025-05-17 08:00:00', '2025-05-17 08:07:00', 'Taken'),
(2, '2025-05-18 08:00:00', '2025-05-18 08:09:00', 'Taken'),
(2, '2025-05-19 08:00:00', '2025-05-19 08:12:00', 'Taken'),
(2, '2025-05-20 08:00:00', NULL, 'Pending'),
(2, '2025-05-21 08:00:00', '2025-05-21 08:18:00', 'Taken'),
(2, '2025-05-22 08:00:00', '2025-05-22 08:12:00', 'Taken'),
(2, '2025-05-23 08:00:00', '2025-05-23 08:15:00', 'Taken'),
(2, '2025-05-24 08:00:00', '2025-05-24 08:04:00', 'Taken'),
(2, '2025-05-25 08:00:00', '2025-05-25 08:09:00', 'Taken'),
(2, '2025-05-26 08:00:00', '2025-05-26 08:18:00', 'Taken'),
(2, '2025-05-27 08:00:00', NULL, 'Pending'),
(2, '2025-05-28 08:00:00', '2025-05-28 08:11:00', 'Taken'),
(2, '2025-05-29 08:00:00', '2025-05-29 08:11:00', 'Taken'),
(2, '2025-05-30 08:00:00', NULL, 'Pending'),
(2, '2025-05-31 08:00:00', NULL, 'Pending'),
(2, '2025-06-01 08:00:00', '2025-06-01 08:15:00', 'Taken'),
(2, '2025-06-02 08:00:00', '2025-06-02 08:01:00', 'Taken'),
(2, '2025-06-03 08:00:00', '2025-06-03 08:00:00', 'Taken'),
(2, '2025-06-04 08:00:00', '2025-06-04 08:11:00', 'Taken'),
(2, '2025-06-05 08:00:00', NULL, 'Pending'),
(2, '2025-06-06 08:00:00', '2025-06-06 08:10:00', 'Taken'),
(2, '2025-06-07 08:00:00', '2025-06-07 08:06:00', 'Taken'),
(2, '2025-06-08 08:00:00', NULL, 'Pending'),
(2, '2025-06-09 08:00:00', NULL, 'Skipped'),
(2, '2025-06-10 08:00:00', '2025-06-10 07:56:00', 'Taken'),
(2, '2025-06-11 08:00:00', '2025-06-11 08:13:00', 'Taken'),
(2, '2025-06-12 08:00:00', NULL, 'Pending'),
(2, '2025-06-13 08:00:00', '2025-06-13 08:10:00', 'Taken'),
(2, '2025-06-14 08:00:00', NULL, 'Skipped'),
(3, '2025-05-16 09:00:00', NULL, 'Pending'),
(3, '2025-05-17 09:00:00', '2025-05-17 08:59:00', 'Taken'),
(3, '2025-05-18 09:00:00', '2025-05-18 09:10:00', 'Taken'),
(3, '2025-05-19 09:00:00', NULL, 'Pending'),
(3, '2025-05-20 09:00:00', '2025-05-20 09:11:00', 'Taken'),
(3, '2025-05-21 09:00:00', NULL, 'Pending'),
(3, '2025-05-22 09:00:00', '2025-05-22 09:01:00', 'Taken'),
(3, '2025-05-23 09:00:00', NULL, 'Pending'),
(3, '2025-05-24 09:00:00', NULL, 'Pending'),
(3, '2025-05-25 09:00:00', NULL, 'Pending'),
(3, '2025-05-26 09:00:00', NULL, 'Pending'),
(3, '2025-05-27 09:00:00', '2025-05-27 09:16:00', 'Taken'),
(3, '2025-05-28 09:00:00', NULL, 'Skipped'),
(3, '2025-05-29 09:00:00', '2025-05-29 09:11:00', 'Taken'),
(3, '2025-05-30 09:00:00', '2025-05-30 09:02:00', 'Taken'),
(3, '2025-05-31 09:00:00', '2025-05-31 09:05:00', 'Taken'),
(3, '2025-06-01 09:00:00', '2025-06-01 09:12:00', 'Taken'),
(3, '2025-06-02 09:00:00', '2025-06-02 09:02:00', 'Taken'),
(3, '2025-06-03 09:00:00', '2025-06-03 09:17:00', 'Taken'),
(3, '2025-06-04 09:00:00', NULL, 'Skipped'),
(3, '2025-06-05 09:00:00', '2025-06-05 08:56:00', 'Taken'),
(3, '2025-06-06 09:00:00', NULL, 'Pending'),
(3, '2025-06-07 09:00:00', '2025-06-07 09:02:00', 'Taken'),
(3, '2025-06-08 09:00:00', '2025-06-08 09:10:00', 'Taken'),
(3, '2025-06-09 09:00:00', '2025-06-09 08:59:00', 'Taken'),
(3, '2025-06-10 09:00:00', NULL, 'Pending'),
(3, '2025-06-11 09:00:00', NULL, 'Pending'),
(3, '2025-06-12 09:00:00', '2025-06-12 09:02:00', 'Taken'),
(3, '2025-06-13 09:00:00', NULL, 'Pending'),
(3, '2025-06-14 09:00:00', NULL, 'Pending'),
(4, '2025-05-16 21:00:00', '2025-05-16 20:58:00', 'Taken'),
(4, '2025-05-17 21:00:00', '2025-05-17 21:06:00', 'Taken'),
(4, '2025-05-18 21:00:00', '2025-05-18 21:09:00', 'Taken'),
(4, '2025-05-19 21:00:00', '2025-05-19 20:56:00', 'Taken'),
(4, '2025-05-20 21:00:00', '2025-05-20 21:15:00', 'Taken'),
(4, '2025-05-21 21:00:00', '2025-05-21 21:07:00', 'Taken'),
(4, '2025-05-22 21:00:00', '2025-05-22 20:58:00', 'Taken'),
(4, '2025-05-23 21:00:00', '2025-05-23 21:01:00', 'Taken'),
(4, '2025-05-24 21:00:00', '2025-05-24 20:59:00', 'Taken'),
(4, '2025-05-25 21:00:00', '2025-05-25 21:03:00', 'Taken'),
(4, '2025-05-26 21:00:00', '2025-05-26 20:57:00', 'Taken'),
(4, '2025-05-27 21:00:00', '2025-05-27 21:12:00', 'Taken'),
(4, '2025-05-28 21:00:00', '2025-05-28 21:15:00', 'Taken'),
(4, '2025-05-29 21:00:00', NULL, 'Pending'),
(4, '2025-05-30 21:00:00', '2025-05-30 20:57:00', 'Taken'),
(4, '2025-05-31 21:00:00', NULL, 'Skipped'),
(4, '2025-06-01 21:00:00', '2025-06-01 21:00:00', 'Taken'),
(4, '2025-06-02 21:00:00', '2025-06-02 21:10:00', 'Taken'),
(4, '2025-06-03 21:00:00', '2025-06-03 21:07:00', 'Taken'),
(4, '2025-06-04 21:00:00', NULL, 'Skipped'),
(4, '2025-06-05 21:00:00', '2025-06-05 20:55:00', 'Taken'),
(4, '2025-06-06 21:00:00', NULL, 'Pending'),
(4, '2025-06-07 21:00:00', '2025-06-07 21:09:00', 'Taken'),
(4, '2025-06-08 21:00:00', '2025-06-08 21:17:00', 'Taken'),
(4, '2025-06-09 21:00:00', NULL, 'Pending'),
(4, '2025-06-10 21:00:00', NULL, 'Pending'),
(4, '2025-06-11 21:00:00', '2025-06-11 21:17:00', 'Taken'),
(4, '2025-06-12 21:00:00', '2025-06-12 21:01:00', 'Taken'),
(4, '2025-06-13 21:00:00', '2025-06-13 20:56:00', 'Taken'),
(4, '2025-06-14 21:00:00', '2025-06-14 21:12:00', 'Taken');

-- Notifications (sample)
INSERT INTO notification (user_id, type, body, sent_at, read_at) VALUES
(4, 'Dose Reminder', 'Time to take your 12:00 PM Lisinopril 10mg.', '2025-06-14 12:00:00', NULL),
(4, 'Refill Warning', 'Aspirin 75mg is running low - 5 tablets left.', '2025-06-13 09:00:00', '2025-06-13 09:30:00'),
(3, 'Dose Reminder', 'Time to take your 8:00 AM Metformin 500mg.', '2025-06-14 08:00:00', '2025-06-14 08:12:00');

-- Audit log (recent admin/patient actions)
INSERT INTO audit_log (actor_id, patient_id, action, entity_type, entity_id, detail, created_at) VALUES
(1, 4, 'prescription_created', 'prescription', NULL, 'Atorvastatin 20mg added for Ahmad Hamid', '2025-06-13 09:15:00'),
(1, 4, 'prescription_updated', 'prescription', 3, 'Aspirin 75mg end date updated to 19 Jun 2025', '2025-06-13 09:20:00'),
(4, 4, 'dose_taken', 'dose_log', NULL, 'Metformin 500mg taken at 08:12', '2025-06-14 08:12:00'),
(4, 4, 'dose_skipped', 'dose_log', NULL, 'Aspirin 75mg skipped at 09:00', '2025-06-14 09:05:00'),
(4, 4, 'dose_taken', 'dose_log', NULL, 'Lisinopril 10mg taken at 08:05', '2025-06-14 08:05:00'),
(3, 3, 'dose_taken', 'dose_log', NULL, 'Metformin 500mg taken at 08:12', '2025-06-14 08:12:00');
