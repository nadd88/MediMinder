<?php

namespace App\Models;

use PDO;

class Medication
{
    public static function findByPatient(int $patientId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT * FROM medications 
            WHERE patient_id = :patient_id 
            ORDER BY schedule_time ASC
        ");
        $stmt->execute(['patient_id' => $patientId]);
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

    public static function create(
        int $patientId,
        string $medicineName,
        string $dosage,
        string $frequency,
        string $scheduleTime,
        ?string $instructions = null,
        int $remainingQuantity = 30
    ): int {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO medications 
            (patient_id, medicine_name, dosage, frequency, schedule_time, instructions, remaining_quantity)
            VALUES (:patient_id, :medicine_name, :dosage, :frequency, :schedule_time, :instructions, :remaining_quantity)
        ");
        $stmt->execute([
            'patient_id' => $patientId,
            'medicine_name' => $medicineName,
            'dosage' => $dosage,
            'frequency' => $frequency,
            'schedule_time' => $scheduleTime,
            'instructions' => $instructions,
            'remaining_quantity' => $remainingQuantity
        ]);
        return (int) $db->lastInsertId();
    }

    public static function updateStatus(int $id, string $status): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE medications SET status = :status WHERE id = :id");
        return $stmt->execute(['id' => $id, 'status' => $status]);
    }

    public static function getTodayMedications(int $patientId): array
    {
        $db = Database::getConnection();
        $today = date('Y-m-d');
        $stmt = $db->prepare("
            SELECT * FROM medications 
            WHERE patient_id = :patient_id 
            AND status != 'Taken'
            ORDER BY schedule_time ASC
        ");
        $stmt->execute(['patient_id' => $patientId]);
        return $stmt->fetchAll();
    }
}