<?php

namespace App\Models;

use PDO;

class Medication
{
    public static function search(string $term = ''): array
    {
        $db = Database::getConnection();
        if ($term === '') {
            $stmt = $db->query("SELECT id, name, generic_name, strength, form FROM medications ORDER BY name ASC");
            return $stmt->fetchAll();
        }

        $stmt = $db->prepare(
            "SELECT id, name, generic_name, strength, form
             FROM medications
             WHERE name LIKE :term OR generic_name LIKE :term
             ORDER BY name ASC"
        );
        $stmt->execute(['term' => "%$term%"]);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT id, name, generic_name, strength, form FROM medications WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $med = $stmt->fetch();
        return $med ?: null;
    }

    public static function exists(int $id): bool
    {
        return self::find($id) !== null;
    }

    /**
     * Check interactions between a candidate medication and a patient's
     * currently active prescriptions.
     */
    public static function checkInteractionsForPatient(int $patientId, int $medicationId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            "SELECT DISTINCT m.name AS other_medication, di.severity, di.warning
             FROM prescriptions p
             JOIN medications m ON m.id = p.medication_id
             JOIN drug_interactions di
               ON (di.medication_a_id = :med_id AND di.medication_b_id = p.medication_id)
               OR (di.medication_b_id = :med_id AND di.medication_a_id = p.medication_id)
             WHERE p.patient_id = :patient_id
               AND (p.end_date IS NULL OR p.end_date >= CURDATE())
               AND p.medication_id != :med_id"
        );
        $stmt->execute(['med_id' => $medicationId, 'patient_id' => $patientId]);
        return $stmt->fetchAll();
    }
}
