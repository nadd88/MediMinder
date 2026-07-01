<?php

namespace App\Models;

use PDO;

class PatientCaregiver
{
    public static function linkPatient(int $patientId, int $caregiverId): int
    {
        $db = Database::getConnection();
        
        // Check if patient-caregiver relationship already exists
        $stmt = $db->prepare("
            SELECT id FROM patient_caregiver 
            WHERE patient_id = :patient_id AND caregiver_id = :caregiver_id
        ");
        $stmt->execute([
            'patient_id' => $patientId,
            'caregiver_id' => $caregiverId
        ]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update status to active if exists
            $stmt = $db->prepare("
                UPDATE patient_caregiver 
                SET status = 'active' 
                WHERE patient_id = :patient_id AND caregiver_id = :caregiver_id
            ");
            $stmt->execute([
                'patient_id' => $patientId,
                'caregiver_id' => $caregiverId
            ]);
            return (int) $existing['id'];
        }
        
        // Create new link
        $stmt = $db->prepare("
            INSERT INTO patient_caregiver (patient_id, caregiver_id, status)
            VALUES (:patient_id, :caregiver_id, 'active')
        ");
        $stmt->execute([
            'patient_id' => $patientId,
            'caregiver_id' => $caregiverId
        ]);
        return (int) $db->lastInsertId();
    }

    public static function getPatientsForCaregiver(int $caregiverId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT u.id, u.name, u.email, u.dob
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
            SELECT u.id, u.name, u.email
            FROM patient_caregiver pc
            JOIN users u ON pc.caregiver_id = u.id
            WHERE pc.patient_id = :patient_id AND pc.status = 'active'
        ");
        $stmt->execute(['patient_id' => $patientId]);
        return $stmt->fetchAll();
    }
}