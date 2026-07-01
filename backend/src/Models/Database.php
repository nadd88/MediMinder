<?php

namespace App\Models;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            // Check for Render's DATABASE_URL first (PostgreSQL)
            $databaseUrl = getenv('DATABASE_URL');
            
            try {
                if ($databaseUrl) {
                    // Parse Render's DATABASE_URL format: postgresql://user:password@host:port/database
                    $dbUrlParts = parse_url($databaseUrl);
                    
                    $host = $dbUrlParts['host'] ?? 'localhost';
                    $port = $dbUrlParts['port'] ?? 5432;
                    $dbname = ltrim($dbUrlParts['path'] ?? '/mediminder', '/');
                    $user = $dbUrlParts['user'] ?? 'postgres';
                    $pass = $dbUrlParts['pass'] ?? '';
                    
                    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=require";
                    
                    self::$instance = new PDO($dsn, $user, $pass, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]);
                } else {
                    $driver = getenv('DB_CONNECTION') ?: ($_ENV['DB_CONNECTION'] ?? 'sqlite');

                    if ($driver === 'sqlite') {
                        $dbPath = dirname(__DIR__, 1) . '/database/mediminder.sqlite';
                        $directory = dirname($dbPath);

                        if (!is_dir($directory)) {
                            mkdir($directory, 0777, true);
                        }

                        self::$instance = new PDO("sqlite:$dbPath", null, null, [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        ]);
                        self::$instance->exec('PRAGMA foreign_keys = ON');
                    } else {
                        $host = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? '127.0.0.1');
                        $dbname = getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? 'mediminder');
                        $user = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? 'root');
                        $pass = getenv('DB_PASS') ?: ($_ENV['DB_PASS'] ?? '');

                        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

                        self::$instance = new PDO($dsn, $user, $pass, [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        ]);
                    }
                }
                
                self::ensureSchema(self::$instance);
                self::ensureSeedData(self::$instance);
            } catch (PDOException $e) {
                error_log($e->getMessage());
                throw new PDOException('Database connection failed: ' . $e->getMessage());
            }
        }

        return self::$instance;
    }

    private static function ensureSchema(PDO $db): void
    {
        $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
        
        if ($driver === 'pgsql') {
            // PostgreSQL schema
            $db->exec("
                CREATE TABLE IF NOT EXISTS users (
                    id SERIAL PRIMARY KEY,
                    name TEXT NOT NULL,
                    email TEXT UNIQUE NOT NULL,
                    password_hash TEXT NOT NULL,
                    role TEXT NOT NULL,
                    dob TEXT NULL
                )
            ");

            $db->exec("
                CREATE TABLE IF NOT EXISTS medications (
                    id SERIAL PRIMARY KEY,
                    patient_id INTEGER NOT NULL,
                    medicine_name TEXT NOT NULL,
                    dosage TEXT NOT NULL,
                    frequency TEXT NOT NULL,
                    schedule_time TEXT NOT NULL,
                    instructions TEXT,
                    remaining_quantity INTEGER NOT NULL DEFAULT 30,
                    status TEXT NOT NULL DEFAULT 'Pending',
                    last_refill TEXT NULL,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE
                )
            ");

            $db->exec("
                CREATE TABLE IF NOT EXISTS dose_logs (
                    id SERIAL PRIMARY KEY,
                    medication_id INTEGER NOT NULL,
                    patient_id INTEGER NOT NULL,
                    status TEXT NOT NULL DEFAULT 'Pending',
                    scheduled_at TEXT NOT NULL,
                    taken_at TEXT NULL,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE (medication_id, patient_id, scheduled_at),
                    FOREIGN KEY (medication_id) REFERENCES medications(id) ON DELETE CASCADE,
                    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE
                )
            ");

            $db->exec("
                CREATE TABLE IF NOT EXISTS patient_caregiver (
                    id SERIAL PRIMARY KEY,
                    patient_id INTEGER NOT NULL,
                    caregiver_id INTEGER NOT NULL,
                    status TEXT NOT NULL DEFAULT 'active',
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE (patient_id, caregiver_id),
                    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (caregiver_id) REFERENCES users(id) ON DELETE CASCADE
                )
            ");
        } else {
            // SQLite schema
            $db->exec(
                "CREATE TABLE IF NOT EXISTS users (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL,
                    email TEXT UNIQUE NOT NULL,
                    password_hash TEXT NOT NULL,
                    role TEXT NOT NULL,
                    dob TEXT NULL
                )"
            );

            $db->exec(
                "CREATE TABLE IF NOT EXISTS medications (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    patient_id INTEGER NOT NULL,
                    medicine_name TEXT NOT NULL,
                    dosage TEXT NOT NULL,
                    frequency TEXT NOT NULL,
                    schedule_time TEXT NOT NULL,
                    instructions TEXT,
                    remaining_quantity INTEGER NOT NULL DEFAULT 30,
                    status TEXT NOT NULL DEFAULT 'Pending',
                    last_refill TEXT NULL,
                    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE
                )"
            );

            $db->exec(
                "CREATE TABLE IF NOT EXISTS dose_logs (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    medication_id INTEGER NOT NULL,
                    patient_id INTEGER NOT NULL,
                    status TEXT NOT NULL DEFAULT 'Pending',
                    scheduled_at TEXT NOT NULL,
                    taken_at TEXT NULL,
                    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE (medication_id, patient_id, scheduled_at),
                    FOREIGN KEY (medication_id) REFERENCES medications(id) ON DELETE CASCADE,
                    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE
                )"
            );

            $db->exec(
                "CREATE TABLE IF NOT EXISTS patient_caregiver (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    patient_id INTEGER NOT NULL,
                    caregiver_id INTEGER NOT NULL,
                    status TEXT NOT NULL DEFAULT 'active',
                    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE (patient_id, caregiver_id),
                    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (caregiver_id) REFERENCES users(id) ON DELETE CASCADE
                )"
            );
        }
    }

    private static function ensureSeedData(PDO $db): void
    {
        $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
        
        $users = [
            ['Sarah Tan', 'patient@email.com', 'Patient', '1990-05-14'],
            ['Nur Rashidah', 'caregiver@email.com', 'Caregiver', '1985-03-10'],
            ['Dr. Rashid', 'admin@email.com', 'Admin', null],
        ];
        $passwordHash = password_hash('password123', PASSWORD_BCRYPT);
        
        // Handle INSERT OR IGNORE for SQLite, ON CONFLICT for PostgreSQL
        if ($driver === 'pgsql') {
            $insertSql = 'INSERT INTO users (name, email, password_hash, role, dob) VALUES (:name, :email, :password_hash, :role, :dob) ON CONFLICT (email) DO UPDATE SET name = :name, password_hash = :password_hash, role = :role, dob = :dob';
        } else {
            $insertSql = 'INSERT OR IGNORE INTO users (name, email, password_hash, role, dob) VALUES (:name, :email, :password_hash, :role, :dob)';
        }
        
        $userStmt = $db->prepare($insertSql);
        foreach ($users as [$name, $email, $role, $dob]) {
            $userStmt->execute([
                'name' => $name,
                'email' => $email,
                'password_hash' => $passwordHash,
                'role' => $role,
                'dob' => $dob,
            ]);
        }

        $patientStmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $patientStmt->execute(['patient@email.com']);
        $patientId = (int) $patientStmt->fetchColumn();

        $caregiverStmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $caregiverStmt->execute(['caregiver@email.com']);
        $caregiverId = (int) $caregiverStmt->fetchColumn();

        if ($patientId > 0 && $caregiverId > 0) {
            $linkCheckStmt = $db->prepare(
                'SELECT id FROM patient_caregiver WHERE patient_id = :patient_id AND caregiver_id = :caregiver_id'
            );
            $linkCheckStmt->execute(['patient_id' => $patientId, 'caregiver_id' => $caregiverId]);

            if (!$linkCheckStmt->fetch()) {
                $linkStmt = $db->prepare(
                    "INSERT INTO patient_caregiver (patient_id, caregiver_id, status) VALUES (:patient_id, :caregiver_id, 'active')"
                );
                $linkStmt->execute(['patient_id' => $patientId, 'caregiver_id' => $caregiverId]);
            }
        }
        
        if ($patientId <= 0) {
            return;
        }

        $countStmt = $db->prepare("SELECT COUNT(*) FROM medications WHERE patient_id = ?");
        $countStmt->execute([$patientId]);
        $medCount = (int) $countStmt->fetchColumn();
        
        if ($medCount === 0) {
            $medStmt = $db->prepare(
                'INSERT INTO medications (patient_id, medicine_name, dosage, frequency, schedule_time, instructions, remaining_quantity, status, last_refill)
                 VALUES (:patient_id, :medicine_name, :dosage, :frequency, :schedule_time, :instructions, :remaining_quantity, :status, :last_refill)'
            );
            foreach ([
                ['Paracetamol', '500mg', 'Twice Daily', '08:00:00', 'Take after meals', 20],
                ['Vitamin C', '1000mg', 'Once Daily', '13:00:00', 'Take with water', 15],
                ['Amoxicillin', '250mg', 'Three Times Daily', '20:00:00', 'Finish the course', 12],
            ] as [$name, $dosage, $frequency, $time, $instructions, $remaining]) {
                $medStmt->execute([
                    'patient_id' => $patientId,
                    'medicine_name' => $name,
                    'dosage' => $dosage,
                    'frequency' => $frequency,
                    'schedule_time' => $time,
                    'instructions' => $instructions,
                    'remaining_quantity' => $remaining,
                    'status' => 'Pending',
                    'last_refill' => date('Y-m-d', strtotime('-7 days')),
                ]);
            }
        }

        $medications = $db->prepare('SELECT id, schedule_time FROM medications WHERE patient_id = ?');
        $medications->execute([$patientId]);
        
        if ($driver === 'pgsql') {
            $insertDoseSql = 'INSERT INTO dose_logs (medication_id, patient_id, status, scheduled_at) VALUES (:medication_id, :patient_id, :status, :scheduled_at) ON CONFLICT DO NOTHING';
        } else {
            $insertDoseSql = 'INSERT OR IGNORE INTO dose_logs (medication_id, patient_id, status, scheduled_at) VALUES (:medication_id, :patient_id, :status, :scheduled_at)';
        }
        
        $insertDose = $db->prepare($insertDoseSql);

        $today = date('Y-m-d');
        foreach ($medications->fetchAll() as $medication) {
            for ($offset = 29; $offset >= 0; $offset--) {
                $day = date('Y-m-d', strtotime("-{$offset} days"));
                $status = $day === $today ? 'Pending' : (($offset % 5 === 0) ? 'Missed' : 'Taken');
                $insertDose->execute([
                    'medication_id' => $medication['id'],
                    'patient_id' => $patientId,
                    'status' => $status,
                    'scheduled_at' => $day . ' ' . $medication['schedule_time'],
                ]);
            }
        }
    }
}

