# MediMinder — Backend & Admin Module

Database + REST API + Clinic Administrator UI for the MediMinder medication
adherence system (SCSM2223, Group Obsidian).

This package was built for the **Database & Security Lead** scope:
MySQL schema (DDL), PDO data-access with prepared statements, JWT auth,
role-based access control, input validation, drug-interaction seeding, and an
immutable audit log — plus the Vue admin views that consume the API.

---

## Stack

- **API:** PHP 8.1+ · Slim 4 · PDO · firebase/php-jwt · vlucas/phpdotenv
- **Database:** MySQL 8 / MariaDB 10.4+ (InnoDB, utf8mb4)
- **Frontend (admin views):** Vue 3 · Pinia · Vue Router · axios · Chart.js · Tailwind

---

## 1. Database setup

Create the database and load schema + seed data:

```bash
mysql -u root -p -e "CREATE DATABASE mediminder CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p mediminder < backend/database/schema.sql
mysql -u root -p mediminder < backend/database/seed.sql
```

> On Laragon (Windows), open the MySQL console from the Laragon menu and run the
> same three commands, or import the two `.sql` files via HeidiSQL.

### Demo accounts (seeded)

All seeded users share the password **`password123`**.

| Role      | Email                        | Notes                             |
|-----------|------------------------------|-----------------------------------|
| Admin     | `admin@mediminder.app`       | Clinic administrator (login here) |
| Caregiver | `caregiver@mediminder.app`   | Linked to demo patients           |
| Patient   | `ahmad@mediminder.app`       | Main demo patient (rich data)     |
| Patient   | `sarah@mediminder.app`       | Secondary demo patient            |

---

## 2. API setup

```bash
cd backend
cp .env.example .env        # then edit DB_* and JWT_SECRET
composer install
php -S localhost:8080 -t public
```

The API is now at `http://localhost:8080/api`.

### `.env` keys

| Key           | Example                        |
|---------------|--------------------------------|
| `DB_HOST`     | `127.0.0.1`                    |
| `DB_NAME`     | `mediminder`                   |
| `DB_USER`     | `root`                         |
| `DB_PASS`     | *(your password)*              |
| `JWT_SECRET`  | *(long random string)*         |
| `CORS_ORIGIN` | `http://localhost:5173`        |

---

## 3. Admin frontend

The admin views live in `mediminder-frontend/src/views/admin/` and slot into the
existing Vue app. From the frontend folder:

```bash
cd mediminder-frontend
npm install
npm run dev
```

Optionally set `VITE_API_URL` (defaults to `http://localhost:8080/api`).

Log in with the **Admin** account, then visit `/admin/patients`. The route guard
(`router/index.js`) blocks non-Admin and unauthenticated users.

---

## API reference (admin)

All `/api/admin/*` routes require a valid JWT **and** the `Admin` role.

| Method | Path                                          | Purpose                          |
|--------|-----------------------------------------------|----------------------------------|
| POST   | `/api/auth/login`                             | Login, returns JWT               |
| GET    | `/api/me`                                     | Current user (auth)              |
| GET    | `/api/admin/patients`                         | List patients                    |
| POST   | `/api/admin/patients`                         | Create patient                   |
| GET    | `/api/admin/patients/{id}`                    | Patient detail                   |
| GET    | `/api/admin/medications?q=`                   | Search drug catalogue            |
| GET    | `/api/admin/patients/{id}/prescriptions`      | List prescriptions               |
| POST   | `/api/admin/patients/{id}/prescriptions`      | Create prescription (+audit)     |
| PUT    | `/api/admin/prescriptions/{id}`               | Update prescription (+audit)     |
| GET    | `/api/admin/patients/{id}/interaction-check`  | Drug-interaction warnings        |
| GET    | `/api/admin/patients/{id}/report`             | Adherence summary (JSON)         |
| GET    | `/api/admin/patients/{id}/report.csv`         | Adherence report (CSV export)    |
| GET    | `/api/admin/audit?patient_id=&limit=`         | Audit log                        |

---

## Design notes

- **`Missed` is derived, not stored.** `dose_log.status` stays
  `Pending / Taken / Skipped` per the PR1 data dictionary; a dose counts as
  *Missed* when `status = 'Pending' AND scheduled_at < NOW()`.
- **Adherence %** = `Taken / (Taken + Skipped + Missed)` over *due* doses only
  (upcoming doses are excluded).
- **Two supporting tables** beyond the six core entities: `drug_interaction`
  (static lookup, Objective 7) and `audit_log` (Objective 4 immutable trail).
- **Security:** all queries use PDO prepared statements (no string interpolation),
  passwords are bcrypt-hashed, JWT is HS256, and RBAC is enforced in middleware.
