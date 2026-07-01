<?php

namespace App\Models;

use PDO;

class PatientCaregiver
{
    public static function linkPatient(int $patientId, int $caregiverId, string $status = 'active'): int
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO patient_caregiver (patient_id, caregiver_id, status)
            VALUES (:patient_id, :caregiver_id, :status)
            ON DUPLICATE KEY UPDATE status = :status
        ");
        $stmt->execute([
            'patient_id' => $patientId,
            'caregiver_id' => $caregiverId,
            'status' => $status
        ]);
        return (int) $db->lastInsertId();
    }

    public static function getPatientsForCaregiver(int $caregiverId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT u.id, u.name, u.email, pc.status 
            FROM patient_caregiver pc
            JOIN users u ON pc.patient_id = u.id
            WHERE pc.caregiver_id = :caregiver_id AND pc.status = 'active'
        ");
        $stmt->execute(['caregiver_id' => $caregiverId]);
        return $stmt->fetchAll();
    }

    public static function getCaregiversForPatient(int $patientId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT u.id, u.name, u.email, pc.status 
            FROM patient_caregiver pc
            JOIN users u ON pc.caregiver_id = u.id
            WHERE pc.patient_id = :patient_id AND pc.status = 'active'
        ");
        $stmt->execute(['patient_id' => $patientId]);
        return $stmt->fetchAll();
    }
}