# MediMinder

MediMinder is a web-based medication management platform designed for patients, caregivers, and administrators. It brings together medication schedules, dose tracking, adherence monitoring, caregiver coordination, and basic admin management in one simple experience.

## Overview

The app is built to help users stay on top of medication routines and support better treatment follow-through. Patients can review their medications, update dose status, and view progress over time. Caregivers can monitor linked patients, while administrators can access overview and prescription-related workflows.

## Main features

### Patient experience
- Medication dashboard with daily medication overview
- Dose tracking with status updates such as taken, pending, or missed
- Medication supply overview
- Adherence reporting for recent periods
- Clean, mobile-friendly interface for everyday use

### Caregiver experience
- View linked patients
- Monitor medication-related activity for assigned patients
- Review patient medication overview and recent status updates
- Access caregiver-specific alerts and patient summaries

### Admin experience
- Admin dashboard overview
- Patient and prescription management views
- Prescription-related navigation and management workflows

## Tech stack

### Frontend
- Vue 3
- Vite
- Vue Router
- Pinia
- Tailwind CSS

### Backend
- PHP with Slim 4
- JWT-based authentication
- SQLite by default for local development

## Project structure

- backend/ — PHP API, controllers, models, and database setup
- mediminder-frontend/ — Vue frontend application and UI views
- README.md — project overview and setup instructions

## Requirements

Before running the project, make sure you have:

- Node.js 18 or newer
- npm
- PHP 8.2 or newer
- Composer

## Local setup

### 1. Start the backend

```bash
cd backend
composer install
php -S localhost:8000 -t public
```

The API will be available at:

```text
http://localhost:8000
```

### 2. Start the frontend

```bash
cd mediminder-frontend
npm install
npm run dev -- --host 0.0.0.0 --port 5173
```

Open the app in your browser at:

```text
http://localhost:5173
```

## Demo accounts

A sample patient account is already available for quick testing:

- Email: patient@email.com
- Password: password123

## Notes

- The backend uses SQLite by default and creates the local database automatically on first run.
- The frontend is configured to communicate with the backend at http://localhost:8000.
- The current project is suitable for local development and demo purposes.

## License

This project is intended for academic and educational use.
