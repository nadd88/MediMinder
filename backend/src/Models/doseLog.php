<?php

namespace App\Models;

use PDO;

class DoseLog
{
    public static function create(
        int $prescriptionId,
        string $scheduledTime,
        ?string $status = 'pending'
    ): int {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            INSERT INTO dose_logs (prescription_id, scheduled_time, status)
            VALUES (:prescription_id, :scheduled_time, :status)
        ");
        $stmt->execute([
            'prescription_id' => $prescriptionId,
            'scheduled_time' => $scheduledTime,
            'status' => $status
        ]);
        return (int) $db->lastInsertId();
    }

    public static function markDose(int $doseLogId, string $status, ?string $takenAt = null): bool
    {
        $db = Database::getConnection();
        $takenAt = $takenAt ?? date('Y-m-d H:i:s');
        $stmt = $db->prepare("
            UPDATE dose_logs 
            SET status = :status, taken_at = :taken_at 
            WHERE id = :id
        ");
        return $stmt->execute([
            'id' => $doseLogId,
            'status' => $status,
            'taken_at' => $takenAt
        ]);
    }

    public static function getTodayDoses(int $patientId): array
    {
        $db = Database::getConnection();
        $today = date('Y-m-d');
        $stmt = $db->prepare("
            SELECT dl.*, p.dose, p.frequency, m.name as medication_name
            FROM dose_logs dl
            JOIN prescriptions p ON dl.prescription_id = p.id
            JOIN medications m ON p.medication_id = m.id
            WHERE p.patient_id = :patient_id AND DATE(dl.scheduled_time) = :today
            ORDER BY dl.scheduled_time ASC
        ");
        $stmt->execute(['patient_id' => $patientId, 'today' => $today]);
        return $stmt->fetchAll();
    }

    public static function getAdherenceStats(int $patientId, int $days = 30): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total_doses,
                SUM(CASE WHEN status = 'taken' THEN 1 ELSE 0 END) as taken_doses,
                SUM(CASE WHEN status = 'skipped' THEN 1 ELSE 0 END) as skipped_doses,
                SUM(CASE WHEN status = 'missed' THEN 1 ELSE 0 END) as missed_doses
            FROM dose_logs dl
            JOIN prescriptions p ON dl.prescription_id = p.id
            WHERE p.patient_id = :patient_id 
            AND dl.scheduled_time >= DATE_SUB(NOW(), INTERVAL :days DAY)
        ");
        $stmt->execute(['patient_id' => $patientId, 'days' => $days]);
        $result = $stmt->fetch();
        
        $total = (int)($result['total_doses'] ?? 0);
        $taken = (int)($result['taken_doses'] ?? 0);
        
        return [
            'total_doses' => $total,
            'taken_doses' => $taken,
            'skipped_doses' => (int)($result['skipped_doses'] ?? 0),
            'missed_doses' => (int)($result['missed_doses'] ?? 0),
            'adherence_percentage' => $total > 0 ? round(($taken / $total) * 100) : 0
        ];
    }
}