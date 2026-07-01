-- =========================================================
-- MediMinder — Admin Module schema
-- Run this AFTER your existing `users` table already exists.
-- Assumes: users(id, name, email, password_hash, role, dob, created_at)
--          with role ENUM('Patient','Caregiver','Admin')
-- =========================================================

-- Reference only — uncomment if `users` doesn't exist yet in this DB.
-- CREATE TABLE IF NOT EXISTS users (
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     name VARCHAR(100) NOT NULL,
--     email VARCHAR(150) NOT NULL UNIQUE,
--     password_hash VARCHAR(255) NOT NULL,
--     role ENUM('Patient','Caregiver','Admin') NOT NULL,
--     dob DATE NULL,
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
-- ) ENGINE=InnoDB;

-- Drug catalogue
CREATE TABLE IF NOT EXISTS medications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    generic_name VARCHAR(150) NULL,
    strength VARCHAR(50) NULL,
    form VARCHAR(50) NULL,          -- tablet, capsule, syrup, etc.
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Known interactions between two drugs (symmetric pair)
CREATE TABLE IF NOT EXISTS drug_interactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medication_a_id INT NOT NULL,
    medication_b_id INT NOT NULL,
    severity ENUM('Low','Moderate','Severe') NOT NULL DEFAULT 'Moderate',
    warning VARCHAR(255) NOT NULL,
    FOREIGN KEY (medication_a_id) REFERENCES medications(id) ON DELETE CASCADE,
    FOREIGN KEY (medication_b_id) REFERENCES medications(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_pair (medication_a_id, medication_b_id)
) ENGINE=InnoDB;

-- Prescriptions issued by an Admin to a Patient
CREATE TABLE IF NOT EXISTS prescriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    medication_id INT NOT NULL,
    prescribed_by INT NOT NULL,      -- users.id of the Admin who created it
    dose VARCHAR(100) NOT NULL,
    frequency VARCHAR(50) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NULL,
    notes VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (medication_id) REFERENCES medications(id) ON DELETE RESTRICT,
    FOREIGN KEY (prescribed_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Individual dose events used to compute adherence
CREATE TABLE IF NOT EXISTS dose_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prescription_id INT NOT NULL,
    patient_id INT NOT NULL,
    scheduled_date DATE NOT NULL,
    status ENUM('Taken','Skipped','Missed') NOT NULL DEFAULT 'Missed',
    logged_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (prescription_id) REFERENCES prescriptions(id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Admin/security audit trail (also feeds the "Audit log" nav item)
CREATE TABLE IF NOT EXISTS audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    actor_id INT NULL,               -- users.id who performed the action (NULL = system)
    patient_id INT NULL,             -- related patient, if any
    action VARCHAR(100) NOT NULL,    -- e.g. 'prescription.created'
    detail VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- --- Sample seed data (safe to remove) ---
INSERT INTO medications (name, generic_name, strength, form) VALUES
    ('Metformin', 'Metformin HCl', '500mg', 'tablet'),
    ('Lisinopril', 'Lisinopril', '10mg', 'tablet'),
    ('Aspirin', 'Acetylsalicylic acid', '75mg', 'tablet'),
    ('Warfarin', 'Warfarin sodium', '5mg', 'tablet');

INSERT INTO drug_interactions (medication_a_id, medication_b_id, severity, warning) VALUES
    (3, 4, 'Severe', 'Increased bleeding risk when Aspirin is combined with Warfarin');
