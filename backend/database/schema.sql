-- MediMinder Database Schema
-- Run this once to set up your local database

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