<?php

namespace App\Models;

use PDO;

class DoseLog
{
    public static function create(int $medicationId, int $patientId, string $status, ?string $takenAt = null): int
    {
        $db = Database::getConnection();
        $takenAt = $takenAt ?? date('Y-m-d H:i:s');
        $stmt = $db->prepare("
            INSERT INTO dose_logs (medication_id, patient_id, status, taken_at)
            VALUES (:medication_id, :patient_id, :status, :taken_at)
        ");
        $stmt->execute([
            'medication_id' => $medicationId,
            'patient_id' => $patientId,
            'status' => $status,
            'taken_at' => $takenAt
        ]);
        return (int) $db->lastInsertId();
    }

    public static function getTodayDoses(int $patientId): array
    {
        $db = Database::getConnection();
        $today = date('Y-m-d');
        $stmt = $db->prepare("
            SELECT dl.*, m.medicine_name, m.dosage, m.schedule_time
            FROM dose_logs dl
            JOIN medications m ON dl.medication_id = m.id
            WHERE dl.patient_id = :patient_id AND DATE(dl.created_at) = :today
            ORDER BY dl.created_at DESC
        ");
        $stmt->execute(['patient_id' => $patientId, 'today' => $today]);
        return $stmt->fetchAll();
    }

    public static function getAdherenceStats(int $patientId, int $days = 30): array
    {
        $db = Database::getConnection();
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total_doses,
                SUM(CASE WHEN status = 'Taken' THEN 1 ELSE 0 END) as taken_doses,
                SUM(CASE WHEN status = 'Missed' THEN 1 ELSE 0 END) as missed_doses
            FROM dose_logs
            WHERE patient_id = :patient_id 
            AND created_at >= :cutoff
        ");
        $stmt->execute(['patient_id' => $patientId, 'cutoff' => $cutoff]);
        $result = $stmt->fetch();
        
        $total = (int)($result['total_doses'] ?? 0);
        $taken = (int)($result['taken_doses'] ?? 0);
        
        return [
            'total_doses' => $total,
            'taken_doses' => $taken,
            'missed_doses' => (int)($result['missed_doses'] ?? 0),
            'adherence_percentage' => $total > 0 ? round(($taken / $total) * 100) : 0
        ];
    }

    public static function getWeeklyAdherence(int $patientId): array
    {
        $db = Database::getConnection();
        $cutoff = date('Y-m-d H:i:s', strtotime('-7 days'));
        $stmt = $db->prepare("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'Taken' THEN 1 ELSE 0 END) as taken
            FROM dose_logs
            WHERE patient_id = :patient_id 
            AND created_at >= :cutoff
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ");
        $stmt->execute(['patient_id' => $patientId, 'cutoff' => $cutoff]);
        return $stmt->fetchAll();
    }
}