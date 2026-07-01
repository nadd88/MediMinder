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

-- Seed test accounts for development
-- All three use the same password: password123
INSERT INTO users (name, email, password_hash, role, dob) VALUES
('Sarah Tan', 'patient@email.com', '$2b$10$ViwP5WAHCy2CHrOSj5cvEO8GYE88AY1Dmc8GedryoA/rjwNrxZ9c6', 'Patient', '1990-05-14'),
('Nur Rashidah', 'caregiver@email.com', '$2b$10$ViwP5WAHCy2CHrOSj5cvEO8GYE88AY1Dmc8GedryoA/rjwNrxZ9c6', 'Caregiver', '1985-03-10'),
('Dr. Rashid', 'admin@email.com', '$2b$10$ViwP5WAHCy2CHrOSj5cvEO8GYE88AY1Dmc8GedryoA/rjwNrxZ9c6', 'Admin', NULL)
ON DUPLICATE KEY UPDATE name = name;