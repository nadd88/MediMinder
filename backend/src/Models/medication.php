<?php

namespace App\Models;

use PDO;

class Medication
{
    public static function findAll(): array
    {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT * FROM medications ORDER BY name");
        return $stmt->fetchAll();
    }

    public static function findById(int $id): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM medications WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }
}