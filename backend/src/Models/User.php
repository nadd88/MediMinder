<?php

namespace App\Models;

use PDO;

class User
{
    public static function findByEmail(string $email): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function findById(int $id): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT id, name, email, role, dob FROM users WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function create(string $name, string $email, string $passwordHash, string $role, ?string $dob): int
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            "INSERT INTO users (name, email, password_hash, role, dob) 
             VALUES (:name, :email, :password_hash, :role, :dob)"
        );
        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'password_hash' => $passwordHash,
            'role' => $role,
            'dob' => $dob,
        ]);
        return (int) $db->lastInsertId();
    }

    public static function getPatientsForCaregiver(int $caregiverId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT * FROM users 
            WHERE role = 'Patient' 
            AND id IN (
                SELECT patient_id FROM patient_caregiver WHERE caregiver_id = :caregiver_id
            )
        ");
        $stmt->execute(['caregiver_id' => $caregiverId]);
        return $stmt->fetchAll();
    }
}