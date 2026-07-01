# MediMinder

<div align="center">
  <img src="https://img.shields.io/badge/version-1.0.0-blue?style=for-the-badge" alt="Version 1.0.0">
  <img src="https://img.shields.io/badge/license-MIT-green?style=for-the-badge" alt="MIT License">
  <img src="https://img.shields.io/badge/status-active-success?style=for-the-badge" alt="Status: Active">
  <img src="https://img.shields.io/badge/PRs-welcome-brightgreen?style=for-the-badge" alt="PRs Welcome">
</div>

---

## Table of Contents

- [Overview](#overview)
- [Key Features](#key-features)
- [Technology Stack](#technology-stack)
- [System Architecture](#system-architecture)
- [Project Structure](#project-structure)
- [Prerequisites](#prerequisites)
- [Local Setup](#local-setup)
- [Demo Accounts](#demo-accounts)
- [API Endpoints](#api-endpoints)
- [Deployment](#deployment)
- [License](#license)

---

## Overview

**MediMinder** is a comprehensive medication management platform designed to improve medication adherence and streamline care coordination. The system serves three primary user roles: **Patients**, **Caregivers**, and **Administrators**, each with tailored dashboards and functionalities.

### Problem Statement

Medication non-adherence is a significant healthcare challenge, particularly among elderly patients and individuals managing multiple prescriptions. Existing solutions often lack caregiver oversight and meaningful adherence analytics.

### Solution

MediMinder bridges this gap by providing:
- Real-time medication tracking with one-tap dose logging
- Role-based access control for patients, caregivers, and administrators
- Adherence analytics with visual progress charts
- Caregiver monitoring for linked patients
- Secure audit trails for compliance tracking

---

## Key Features

### Patient Experience

- **Medication Dashboard:** View daily medication schedule with dosage, frequency, and timing
- **Dose Tracking:** Mark medications as `Taken` or `Missed` with a single tap
- **Supply Overview:** Track remaining medication quantities and receive refill alerts
- **Adherence Analytics:** View 7-day and 30-day adherence percentage charts
- **Mobile-First Design:** Fully responsive interface for desktop and mobile devices

### Caregiver Experience

- **Patient Monitoring:** View all linked patients in a consolidated dashboard
- **Adherence Oversight:** Review medication adherence summaries per patient
- **Alert System:** Receive notifications for missed doses or low supply
- **Patient Search:** Quick search and filter functionality

### Administrator Experience

- **Patient Management:** View and manage all registered patients
- **Prescription Management:** Create and assign medication regimens
- **Adherence Reporting:** Generate detailed adherence reports (CSV/PDF)
- **Audit Logs:** Track all system actions for compliance

---

## Technology Stack

### Frontend

| Technology | Purpose |
|------------|---------|
| **Vue 3** | Progressive JavaScript framework for UI development |
| **Vite** | Fast build tool and development server |
| **Vue Router** | Client-side routing and navigation |
| **Pinia** | State management with TypeScript support |
| **Tailwind CSS** | Utility-first CSS framework for responsive design |
| **Capacitor** | Cross-platform mobile wrapper (Android) |

### Backend

| Technology | Purpose |
|------------|---------|
| **PHP 8.2** | Server-side scripting language |
| **Slim 4** | Micro-framework for REST API development |
| **JWT** | Stateless authentication with JSON Web Tokens |
| **PDO** | Secure database abstraction layer |
| **bcrypt** | Password hashing for secure storage |

### Database

| Technology | Purpose |
|------------|---------|
| **MySQL** | Relational database for production |
| **SQLite** | Lightweight database for local development |

### Infrastructure

| Platform | Purpose |
|----------|---------|
| **Vercel** | Frontend hosting and CDN |
| **Render** | Backend API hosting |
| **GitHub** | Version control and CI/CD |
| **Capacitor** | Android APK generation |

---

## System Architecture

┌─────────────────────────────────────────────────────────────────────────────────────────────┐
│ SYSTEM ARCHITECTURE │
├─────────────────────────────────────────────────────────────────────────────────────────────┤
│ │
│ ┌──────────────────────────┐ ┌──────────────────────────┐ ┌────────────────────┐ │
│ │ FRONTEND LAYER │ │ BACKEND LAYER │ │ DATA LAYER │ │
│ ├──────────────────────────┤ ├──────────────────────────┤ ├────────────────────┤ │
│ │ │ │ │ │ │ │
│ │ ┌──────────────────┐ │ │ ┌──────────────────┐ │ │ ┌────────────┐ │ │
│ │ │ Vue 3 + Vite │ │ │ │ PHP Slim 4 │ │ │ │ MySQL │ │ │
│ │ │ - Tailwind CSS │ │ │ │ - JWT Auth │ │ │ │ - Users │ │ │
│ │ │ - Pinia Store │ │ │ │ - PDO Layer │ │ │ │ - Meds │ │ │
│ │ │ - Vue Router │ │ │ │ - Validators │ │ │ │ - Logs │ │ │
│ │ └────────┬─────────┘ │ │ └────────┬─────────┘ │ │ └─────┬──────┘ │ │
│ │ │ │ │ │ │ │ │ │ │
│ │ │ HTTPS/JSON │ │ │ PDO/SQL │ │ │ │ │
│ │ └─────────────┼─────┼────────────┘ │ │ │ │ │
│ │ │ │ │ │ │ │ │
│ │ ┌──────────────────┐ │ │ ┌──────────────────┐ │ │ ┌─────┴──────┐ │ │
│ │ │ Capacitor │ │ │ │ Middleware │ │ │ │ Audit Log │ │ │
│ │ │ (Android Wrap) │ │ │ │ - Auth │ │ │ │ (History) │ │ │
│ │ └──────────────────┘ │ │ │ - Rate Limit │ │ │ └────────────┘ │ │
│ │ │ │ │ - CORS │ │ │ │ │
│ └──────────────────────────┘ │ └──────────────────┘ │ └────────────────────┘ │
│ └──────────────────────────┘ │
│ │
│ ┌──────────────────────────┐ ┌────────────────────────────────────────────────────┐ │
│ │ DEPLOYMENT │ │ MOBILE BUILD │ │
│ ├──────────────────────────┤ ├────────────────────────────────────────────────────┤ │
│ │ Frontend: Vercel │ │ Capacitor Android │ │
│ │ Backend: Render │ │ APK Generation │ │
│ │ Database: Render MySQL │ │ Google Play Store (optional) │ │
│ └──────────────────────────┘ └────────────────────────────────────────────────────┘ │
│ │
└─────────────────────────────────────────────────────────────────────────────────────────────┘


---

## Prerequisites

| Tool | Version | Verification |
|------|---------|--------------|
| Node.js | 18.x or newer | `node -v` |
| npm | 9.x or newer | `npm -v` |
| PHP | 8.2 or newer | `php -v` |
| Composer | 2.x or newer | `composer -v` |
| MySQL | 8.x or newer | `mysql -v` |
| Git | 2.x or newer | `git --version` |

### Additional Tools (Optional)

| Tool | Purpose |
|------|---------|
| [Android Studio](https://developer.android.com/studio) | Capacitor Android development |
| [Postman](https://www.postman.com/) | API testing and documentation |
| [VS Code](https://code.visualstudio.com/) | Recommended IDE |

---

## Local Setup

### Step 1: Clone the Repository

```bash
git clone https://github.com/your-org/mediminder.git
cd mediminder

# Navigate to backend directory
cd backend

# Install PHP dependencies
composer install

# Create environment file
cp .env.example .env

# Update .env with your database credentials
# DB_HOST=127.0.0.1
# DB_NAME=mediminder
# DB_USER=root
# DB_PASS=

# Run database migrations (or import schema.sql)
mysql -u root < sql/schema.sql

# Open a new terminal
cd mediminder-frontend

# Install Node dependencies
npm install

# Copy environment file
cp .env.development .env.local

# Start the development server
npm run dev -- --host 0.0.0.0 --port 5173