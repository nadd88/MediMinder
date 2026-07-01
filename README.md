# MediMinder

MediMinder is a simple medication management app for patients, caregivers, and administrators. It helps users track medications, monitor adherence, and review medication-related information in one place.

## What the app includes

- Patient dashboard with medication overview
- Dose tracking and status updates
- Adherence views for recent medication history
- Caregiver access to linked patients
- Admin area for overview and prescription-related pages

## Tech stack

- Frontend: Vue 3, Vite, Pinia, Vue Router, Tailwind CSS
- Backend: Slim 4 (PHP), JWT-based auth, SQLite by default

## Project structure

- backend/ — PHP API and database logic
- mediminder-frontend/ — Vue frontend application

## Requirements

Make sure these are installed:

- Node.js 18+
- npm
- PHP 8.2+
- Composer

## Run locally

### 1) Start the backend

```bash
cd backend
composer install
php -S localhost:8000 -t public
```

The API will run at:

```text
http://localhost:8000
```

### 2) Start the frontend

```bash
cd mediminder-frontend
npm install
npm run dev -- --host 0.0.0.0 --port 5173
```

Open the app at:

```text
http://localhost:5173
```

## Demo login

A sample patient account is already available:

- Email: patient@email.com
- Password: password123

## Notes

- The backend uses SQLite by default and creates the local database automatically on first run.
- The frontend expects the backend API at http://localhost:8000.

## License

This project is intended for academic and educational use.
