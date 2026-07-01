<?php

namespace App\Models;

use PDO;

class Prescription
{
    public static function findByPatient(int $patientId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT p.*, m.name as medication_name, m.form, m.strength 
            FROM prescriptions p
            JOIN medications m ON p.medication_id = m.id
            WHERE p.patient_id = :patient_id AND p.end_date >= CURDATE()
            ORDER BY p.start_date DESC
        ");
        $stmt->execute(['patient_id' => $patientId]);
        return $stmt->fetchAll();
    }

    public static function create(
        int $patientId,
        int $medicationId,
        string $dose,
        string $frequency,
        string $startDate,
        ?string $endDate = null,
        ?string $notes = null
    ): int {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO prescriptions (patient_id, medication_id, dose, frequency, start_date, end_date, notes)
            VALUES (:patient_id, :medication_id, :dose, :frequency, :start_date, :end_date, :notes)
        ");
        $stmt->execute([
            'patient_id' => $patientId,
            'medication_id' => $medicationId,
            'dose' => $dose,
            'frequency' => $frequency,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'notes' => $notes
        ]);
        return (int) $db->lastInsertId();
    }

    public static function findById(int $id): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT p.*, m.name as medication_name 
            FROM prescriptions p
            JOIN medications m ON p.medication_id = m.id
            WHERE p.id = :id
        ");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }
}