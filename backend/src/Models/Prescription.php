<?php

namespace App\Models;

use PDO;

class Prescription
{
    /**
     * List prescriptions for a patient, joined with medication info,
     * with a computed status: Active / Ending / Expired.
     */
    public static function forPatient(int $patientId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            "SELECT p.id, p.patient_id, p.medication_id, p.dose, p.frequency,
                    p.start_date, p.end_date, p.notes, p.created_at,
                    m.name AS medication, m.strength,
                    CASE
                        WHEN p.end_date IS NOT NULL AND p.end_date < CURDATE() THEN 'Expired'
                        WHEN p.end_date IS NOT NULL AND p.end_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 'Ending'
                        ELSE 'Active'
                    END AS status
             FROM prescriptions p
             JOIN medications m ON m.id = p.medication_id
             WHERE p.patient_id = :patient_id
             ORDER BY p.created_at DESC"
        );
        $stmt->execute(['patient_id' => $patientId]);
        return $stmt->fetchAll();
    }

    public static function create(array $data): int
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            "INSERT INTO prescriptions
                (patient_id, medication_id, prescribed_by, dose, frequency, start_date, end_date, notes)
             VALUES
                (:patient_id, :medication_id, :prescribed_by, :dose, :frequency, :start_date, :end_date, :notes)"
        );
        $stmt->execute([
            'patient_id'     => $data['patient_id'],
            'medication_id'  => $data['medication_id'],
            'prescribed_by'  => $data['prescribed_by'],
            'dose'           => $data['dose'],
            'frequency'      => $data['frequency'],
            'start_date'     => $data['start_date'],
            'end_date'       => $data['end_date'] ?: null,
            'notes'          => $data['notes'] ?: null,
        ]);
        return (int) $db->lastInsertId();
    }
}
