-- MediMinder Database Schema
-- Run this once to set up your local database:
--   mysql -u root < schema.sql

CREATE DATABASE IF NOT EXISTS mediminder;
USE mediminder;

CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('Patient', 'Caregiver', 'Admin') NOT NULL,
    dob DATE NULL
);

CREATE TABLE IF NOT EXISTS medications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    medicine_name VARCHAR(100) NOT NULL,
    dosage VARCHAR(50) NOT NULL,
    frequency VARCHAR(50) NOT NULL,
    schedule_time TIME NOT NULL,
    instructions TEXT,
    remaining_quantity INT DEFAULT 30,
    status ENUM('Pending','Taken','Missed') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_medication_patient
        FOREIGN KEY (patient_id)
        REFERENCES users(id)
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS dose_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    medication_id INT NOT NULL,
    patient_id INT NOT NULL,
    status ENUM('Taken','Missed') NOT NULL,
    taken_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_log_medication
        FOREIGN KEY (medication_id)
        REFERENCES medications(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_log_patient
        FOREIGN KEY (patient_id)
        REFERENCES users(id)
        ON DELETE CASCADE
);



-- Seed test accounts for development
-- All three use the same password: password123
INSERT INTO users (name, email, password_hash, role, dob) VALUES
('Sarah Tan', 'patient@email.com', '$2b$10$ViwP5WAHCy2CHrOSj5cvEO8GYE88AY1Dmc8GedryoA/rjwNrxZ9c6', 'Patient', '1990-05-14'),
('Nur Rashidah', 'caregiver@email.com', '$2b$10$ViwP5WAHCy2CHrOSj5cvEO8GYE88AY1Dmc8GedryoA/rjwNrxZ9c6', 'Caregiver', '1985-03-10'),
('Dr. Rashid', 'admin@email.com', '$2b$10$ViwP5WAHCy2CHrOSj5cvEO8GYE88AY1Dmc8GedryoA/rjwNrxZ9c6', 'Admin', NULL)
ON DUPLICATE KEY UPDATE name = name;


-- Medications + Schedule for Sarah Tan (Patient ID: 1)
INSERT INTO medications
(patient_id, medicine_name, dosage, frequency, schedule_time, instructions, remaining_quantity)

VALUES
(1,'Paracetamol','500mg','Twice Daily','08:00:00','Take after meals',20),

(1,'Vitamin C','1000mg','Once Daily','13:00:00','Take with water',15),

(1,'Amoxicillin','250mg','Three Times Daily','20:00:00','Finish the course',12);