<?php

namespace App\Models;

use PDO;

class DoseLog
{
    /**
     * Overall taken/skipped/missed counts + adherence % for a patient
     * within a date range.
     */
    public static function summary(int $patientId, string $from, string $to): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            "SELECT
                SUM(status = 'Taken')   AS taken,
                SUM(status = 'Skipped') AS skipped,
                SUM(status = 'Missed')  AS missed,
                COUNT(*)                AS total
             FROM dose_logs
             WHERE patient_id = :patient_id
               AND scheduled_date BETWEEN :from AND :to"
        );
        $stmt->execute(['patient_id' => $patientId, 'from' => $from, 'to' => $to]);
        $row = $stmt->fetch() ?: ['taken' => 0, 'skipped' => 0, 'missed' => 0, 'total' => 0];

        $taken = (int) ($row['taken'] ?? 0);
        $skipped = (int) ($row['skipped'] ?? 0);
        $missed = (int) ($row['missed'] ?? 0);
        $total = (int) ($row['total'] ?? 0);
        $adherence = $total > 0 ? round(($taken / $total) * 100) : 0;

        return compact('taken', 'skipped', 'missed', 'adherence');
    }

    /** Per-medication breakdown for the table under the trend chart. */
    public static function perMedication(int $patientId, string $from, string $to): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            "SELECT m.name AS medication, m.strength,
                    SUM(dl.status = 'Taken')  AS taken,
                    SUM(dl.status = 'Missed') AS missed,
                    COUNT(*) AS total
             FROM dose_logs dl
             JOIN prescriptions p ON p.id = dl.prescription_id
             JOIN medications m ON m.id = p.medication_id
             WHERE dl.patient_id = :patient_id
               AND dl.scheduled_date BETWEEN :from AND :to
             GROUP BY m.id, m.name, m.strength
             ORDER BY m.name ASC"
        );
        $stmt->execute(['patient_id' => $patientId, 'from' => $from, 'to' => $to]);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$r) {
            $r['taken'] = (int) $r['taken'];
            $r['missed'] = (int) $r['missed'];
            $r['adherence'] = $r['total'] > 0 ? (int) round(($r['taken'] / $r['total']) * 100) : 0;
            unset($r['total']);
        }
        return $rows;
    }

    /** Daily adherence % trend, used for the line chart. */
    public static function dailyTrend(int $patientId, string $from, string $to): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            "SELECT scheduled_date AS day,
                    SUM(status = 'Taken') AS taken,
                    COUNT(*) AS total
             FROM dose_logs
             WHERE patient_id = :patient_id
               AND scheduled_date BETWEEN :from AND :to
             GROUP BY scheduled_date
             ORDER BY scheduled_date ASC"
        );
        $stmt->execute(['patient_id' => $patientId, 'from' => $from, 'to' => $to]);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$r) {
            $r['adherence'] = $r['total'] > 0 ? (int) round(($r['taken'] / $r['total']) * 100) : 0;
            unset($r['taken'], $r['total']);
        }
        return $rows;
    }

    /** Raw rows for CSV export. */
    public static function rowsForExport(int $patientId, string $from, string $to): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            "SELECT dl.scheduled_date, m.name AS medication, dl.status, dl.logged_at
             FROM dose_logs dl
             JOIN prescriptions p ON p.id = dl.prescription_id
             JOIN medications m ON m.id = p.medication_id
             WHERE dl.patient_id = :patient_id
               AND dl.scheduled_date BETWEEN :from AND :to
             ORDER BY dl.scheduled_date ASC"
        );
        $stmt->execute(['patient_id' => $patientId, 'from' => $from, 'to' => $to]);
        return $stmt->fetchAll();
    }
}
