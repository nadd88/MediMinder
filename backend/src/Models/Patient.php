<?php

namespace App\Models;

use PDO;

class Patient
{
    /**
     * List all users with role = Patient, with age computed from dob.
     */
    public static function all(): array
    {
        $db = Database::getConnection();
        $stmt = $db->query(
            "SELECT id, name, email, dob,
                    TIMESTAMPDIFF(YEAR, dob, CURDATE()) AS age
             FROM users
             WHERE role = 'Patient'
             ORDER BY name ASC"
        );
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            "SELECT id, name, email, dob,
                    TIMESTAMPDIFF(YEAR, dob, CURDATE()) AS age
             FROM users
             WHERE id = :id AND role = 'Patient'
             LIMIT 1"
        );
        $stmt->execute(['id' => $id]);
        $patient = $stmt->fetch();
        return $patient ?: null;
    }

    public static function exists(int $id): bool
    {
        return self::find($id) !== null;
    }
}
