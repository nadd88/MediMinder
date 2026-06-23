-- =====================================================================
--  MediMinder — Database Schema (DDL)
--  Cross-Platform Application Development (SCSM2223) — Group Obsidian
--  Database & Security Lead: Zahra Aulia Putri (A24CS9006)
--
--  Engine : MySQL 8 / MariaDB 10.4+  (InnoDB, utf8mb4)
--  Notes  : The six CORE tables below match the PR1 ER diagram and data
--           dictionary exactly (users, patient_caregiver, medication,
--           prescription, dose_log, notification). Two SUPPORTING tables
--           (drug_interaction, audit_log) implement features required by
--           the PR1 scope: the static drug-interaction lookup (Objective 7)
--           and the audit trail of dose/prescription changes.
-- =====================================================================

DROP DATABASE IF EXISTS mediminder;
CREATE DATABASE mediminder
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE mediminder;

SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- 1. users
--    Core authentication, profile, and RBAC role for every account.
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id              INT             NOT NULL AUTO_INCREMENT,
    name            VARCHAR(100)    NOT NULL,
    email           VARCHAR(255)    NOT NULL,
    password_hash   VARCHAR(255)    NOT NULL,
    role            ENUM('Patient','Caregiver','Admin') NOT NULL,
    dob             DATE            NULL,
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 2. patient_caregiver
--    Junction table mapping the many-to-many patient <-> caregiver link.
-- ---------------------------------------------------------------------
CREATE TABLE patient_caregiver (
    id              INT             NOT NULL AUTO_INCREMENT,
    patient_id      INT             NOT NULL,
    caregiver_id    INT             NOT NULL,
    status          ENUM('Pending','Active','Inactive') NOT NULL DEFAULT 'Active',
    PRIMARY KEY (id),
    UNIQUE KEY uq_pc_pair (patient_id, caregiver_id),
    KEY idx_pc_patient (patient_id),
    KEY idx_pc_caregiver (caregiver_id),
    CONSTRAINT fk_pc_patient   FOREIGN KEY (patient_id)   REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_pc_caregiver FOREIGN KEY (caregiver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 3. medication
--    Master catalogue of drugs (form, strength, default unit).
-- ---------------------------------------------------------------------
CREATE TABLE medication (
    id              INT             NOT NULL AUTO_INCREMENT,
    name            VARCHAR(150)    NOT NULL,
    form            VARCHAR(50)     NOT NULL,
    strength        VARCHAR(50)     NOT NULL,
    default_unit    VARCHAR(30)     NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_medication_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 4. prescription
--    Binds a catalogue medication to a patient with dose/frequency/dates.
-- ---------------------------------------------------------------------
CREATE TABLE prescription (
    id              INT             NOT NULL AUTO_INCREMENT,
    patient_id      INT             NOT NULL,
    medication_id   INT             NOT NULL,
    dose            VARCHAR(50)     NOT NULL,
    frequency       VARCHAR(100)    NOT NULL,
    start_date      DATE            NOT NULL,
    end_date        DATE            NULL,
    notes           TEXT            NULL,
    PRIMARY KEY (id),
    KEY idx_rx_patient (patient_id),
    KEY idx_rx_medication (medication_id),
    CONSTRAINT fk_rx_patient    FOREIGN KEY (patient_id)    REFERENCES users(id)      ON DELETE CASCADE,
    CONSTRAINT fk_rx_medication FOREIGN KEY (medication_id) REFERENCES medication(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 5. dose_log
--    Immutable record of every scheduled dose and its outcome.
--    NOTE: enum is Pending/Taken/Skipped per the data dictionary.
--    "Missed" is DERIVED (status = 'Pending' AND scheduled_at < NOW())
--    in the API/queries, not stored as a column value.
-- ---------------------------------------------------------------------
CREATE TABLE dose_log (
    id              INT             NOT NULL AUTO_INCREMENT,
    prescription_id INT             NOT NULL,
    scheduled_at    DATETIME        NOT NULL,
    taken_at        DATETIME        NULL,
    status          ENUM('Pending','Taken','Skipped') NOT NULL DEFAULT 'Pending',
    PRIMARY KEY (id),
    KEY idx_dose_prescription (prescription_id),
    KEY idx_dose_scheduled (scheduled_at),
    CONSTRAINT fk_dose_prescription FOREIGN KEY (prescription_id) REFERENCES prescription(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 6. notification
--    Log of in-app / Capacitor local notifications sent to a user.
-- ---------------------------------------------------------------------
CREATE TABLE notification (
    id              INT             NOT NULL AUTO_INCREMENT,
    user_id         INT             NOT NULL,
    type            VARCHAR(50)     NOT NULL,
    body            TEXT            NOT NULL,
    sent_at         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    read_at         DATETIME        NULL,
    PRIMARY KEY (id),
    KEY idx_notification_user (user_id),
    CONSTRAINT fk_notification_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
--  SUPPORTING TABLES (beyond the 6-entity core ERD)
-- =====================================================================

-- ---------------------------------------------------------------------
-- 7. drug_interaction  (static lookup, seeded into the DB — Objective 7)
--    A pair of medications and the clinical warning to surface.
--    Stored normalised so medication_a_id < medication_b_id (enforced
--    in seed data) to avoid duplicate mirrored rows.
-- ---------------------------------------------------------------------
CREATE TABLE drug_interaction (
    id              INT             NOT NULL AUTO_INCREMENT,
    medication_a_id INT             NOT NULL,
    medication_b_id INT             NOT NULL,
    severity        ENUM('Low','Moderate','High') NOT NULL DEFAULT 'Moderate',
    warning         VARCHAR(255)    NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_interaction_pair (medication_a_id, medication_b_id),
    CONSTRAINT fk_di_med_a FOREIGN KEY (medication_a_id) REFERENCES medication(id) ON DELETE CASCADE,
    CONSTRAINT fk_di_med_b FOREIGN KEY (medication_b_id) REFERENCES medication(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 8. audit_log  (audit trail — PR1 scope)
--    Records who did what and when: dose status changes AND prescription
--    create/update actions. Powers the admin "Audit log" panel.
-- ---------------------------------------------------------------------
CREATE TABLE audit_log (
    id              INT             NOT NULL AUTO_INCREMENT,
    actor_id        INT             NULL,           -- user who performed the action (NULL = system)
    patient_id      INT             NULL,           -- patient the action concerns
    action          VARCHAR(60)     NOT NULL,       -- e.g. dose_taken, dose_skipped, prescription_created
    entity_type     VARCHAR(40)     NOT NULL,       -- e.g. dose_log, prescription
    entity_id       INT             NULL,
    detail          VARCHAR(255)    NULL,           -- human-readable summary
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_audit_patient (patient_id),
    KEY idx_audit_created (created_at),
    CONSTRAINT fk_audit_actor   FOREIGN KEY (actor_id)   REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_audit_patient FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
