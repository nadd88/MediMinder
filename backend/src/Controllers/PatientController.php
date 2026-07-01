<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class PatientController
{
    public function __construct(private PDO $db)
    {
    }

    private function json(Response $response, array $payload, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }

    private function patientId(Request $request): int
    {
        return (int) $request->getAttribute('user_id');
    }

    private function timeExpression(string $column): string
    {
        return "CASE
            WHEN CAST(strftime('%H', {$column}) AS INTEGER) = 0 THEN '12' || strftime(':%M AM', {$column})
            WHEN CAST(strftime('%H', {$column}) AS INTEGER) < 12 THEN printf('%02d', CAST(strftime('%H', {$column}) AS INTEGER)) || strftime(':%M AM', {$column})
            WHEN CAST(strftime('%H', {$column}) AS INTEGER) = 12 THEN '12' || strftime(':%M PM', {$column})
            ELSE printf('%02d', CAST(strftime('%H', {$column}) AS INTEGER) - 12) || strftime(':%M PM', {$column})
        END";
    }

    public function dashboard(Request $request, Response $response): Response
    {
        $patientId = $this->patientId($request);
        $today = date('Y-m-d');
        $timeSql = $this->timeExpression('m.schedule_time');
        $takenSql = $this->timeExpression('dl.taken_at');

        $stmt = $this->db->prepare(
            "SELECT dl.id,
                    m.id AS medicationId,
                    m.medicine_name AS name,
                    m.dosage AS dose,
                    LOWER(dl.status) AS status,
                    {$timeSql} AS time,
                    CASE WHEN dl.taken_at IS NULL THEN NULL ELSE {$takenSql} END AS takenAt
             FROM dose_logs dl
             JOIN medications m ON m.id = dl.medication_id
             WHERE dl.patient_id = :pid AND date(dl.scheduled_at) = :today
             ORDER BY time(m.schedule_time) ASC"
        );
        $stmt->execute(['pid' => $patientId, 'today' => $today]);
        $medications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $adh = $this->db->prepare(
            "SELECT ROUND(100.0 * SUM(CASE WHEN status = 'Taken' THEN 1 ELSE 0 END) / NULLIF(COUNT(*), 0)) AS pct
             FROM dose_logs
             WHERE patient_id = :pid
               AND date(scheduled_at) BETWEEN date(:today, '-6 days') AND date(:today)"
        );
        $adh->execute(['pid' => $patientId, 'today' => $today]);
        $adherence7day = (int) ($adh->fetch(PDO::FETCH_ASSOC)['pct'] ?? 0);

        $dueToday = count(array_filter($medications, fn($m) => $m['status'] === 'pending'));
        $missedToday = count(array_filter($medications, fn($m) => $m['status'] === 'missed'));

        return $this->json($response, [
            'success' => true,
            'data' => [
                'summary' => [
                    'adherence7day' => $adherence7day,
                    'dueToday' => $dueToday,
                    'missedToday' => $missedToday,
                ],
                'medications' => $medications,
            ],
        ]);
    }

    public function doses(Request $request, Response $response): Response
    {
        $dashboard = $this->dashboard($request, $response);
        return $dashboard;
    }

    public function markDose(Request $request, Response $response, array $args): Response
    {
        $patientId = $this->patientId($request);
        $doseLogId = (int) $args['id'];
        $body = (array) $request->getParsedBody();
        $status = strtolower((string) ($body['status'] ?? ''));

        if (!in_array($status, ['taken', 'skipped'], true)) {
            return $this->json($response, ['success' => false, 'error' => 'Invalid status.'], 422);
        }

        $check = $this->db->prepare(
            'SELECT id, medication_id FROM dose_logs WHERE id = :id AND patient_id = :pid'
        );
        $check->execute(['id' => $doseLogId, 'pid' => $patientId]);
        $row = $check->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return $this->json($response, ['success' => false, 'error' => 'Dose not found.'], 404);
        }

        $this->db->beginTransaction();
        try {
            $dbStatus = $status === 'taken' ? 'Taken' : 'Missed';
            $takenAt = $status === 'taken' ? date('Y-m-d H:i:s') : null;

            $update = $this->db->prepare(
                'UPDATE dose_logs SET status = :status, taken_at = :taken_at WHERE id = :id'
            );
            $update->execute(['status' => $dbStatus, 'taken_at' => $takenAt, 'id' => $doseLogId]);

            $medUpdate = $status === 'taken'
                ? 'UPDATE medications SET remaining_quantity = MAX(remaining_quantity - 1, 0), status = :status WHERE id = :mid'
                : 'UPDATE medications SET status = :status WHERE id = :mid';
            $this->db->prepare($medUpdate)->execute(['status' => $dbStatus, 'mid' => $row['medication_id']]);

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return $this->json($response, ['success' => false, 'error' => 'Could not update dose.'], 500);
        }

        return $this->json($response, [
            'success' => true,
            'data' => [
                'status' => $status,
                'takenAt' => $takenAt ? date('h:i A', strtotime($takenAt)) : null,
            ],
        ]);
    }

    public function adherence(Request $request, Response $response): Response
    {
        $patientId = $this->patientId($request);
        $params = $request->getQueryParams();
        $range = ($params['range'] ?? '7') === '30' ? 30 : 7;
        $today = date('Y-m-d');
        $modifier = '-' . ($range - 1) . ' days';

        $overallStmt = $this->db->prepare(
            "SELECT ROUND(100.0 * SUM(CASE WHEN status = 'Taken' THEN 1 ELSE 0 END) / NULLIF(COUNT(*), 0)) AS pct
             FROM dose_logs
             WHERE patient_id = :pid
               AND date(scheduled_at) BETWEEN date(:today, :modifier) AND date(:today)"
        );
        $overallStmt->execute(['pid' => $patientId, 'today' => $today, 'modifier' => $modifier]);
        $overall = (int) ($overallStmt->fetch(PDO::FETCH_ASSOC)['pct'] ?? 0);

        $seriesStmt = $this->db->prepare(
            "SELECT date(scheduled_at) AS day,
                    ROUND(100.0 * SUM(CASE WHEN status = 'Taken' THEN 1 ELSE 0 END) / NULLIF(COUNT(*), 0)) AS pct
             FROM dose_logs
             WHERE patient_id = :pid
               AND date(scheduled_at) BETWEEN date(:today, :modifier) AND date(:today)
             GROUP BY date(scheduled_at)
             ORDER BY date(scheduled_at) ASC"
        );
        $seriesStmt->execute(['pid' => $patientId, 'today' => $today, 'modifier' => $modifier]);
        $series = $seriesStmt->fetchAll(PDO::FETCH_ASSOC);

        $labels = array_map(fn($r) => date($range === 30 ? 'M j' : 'D', strtotime($r['day'])), $series);
        $values = array_map(fn($r) => (int) $r['pct'], $series);

        $perMedStmt = $this->db->prepare(
            "SELECT m.medicine_name AS name,
                    m.dosage AS dose,
                    ROUND(100.0 * SUM(CASE WHEN dl.status = 'Taken' THEN 1 ELSE 0 END) / NULLIF(COUNT(*), 0)) AS percent
             FROM dose_logs dl
             JOIN medications m ON m.id = dl.medication_id
             WHERE dl.patient_id = :pid
               AND date(dl.scheduled_at) BETWEEN date(:today, :modifier) AND date(:today)
             GROUP BY m.id, m.medicine_name, m.dosage
             ORDER BY percent ASC"
        );
        $perMedStmt->execute(['pid' => $patientId, 'today' => $today, 'modifier' => $modifier]);
        $byMedication = array_map(
            fn($r) => ['name' => trim($r['name'] . ' ' . $r['dose']), 'percent' => (int) $r['percent']],
            $perMedStmt->fetchAll(PDO::FETCH_ASSOC)
        );

        return $this->json($response, [
            'success' => true,
            'data' => [
                'overall' => $overall,
                'labels' => $labels,
                'values' => $values,
                'byMedication' => $byMedication,
            ],
        ]);
    }

    public function supply(Request $request, Response $response): Response
    {
        $patientId = $this->patientId($request);

        $stmt = $this->db->prepare(
            "SELECT id,
                    medicine_name AS name,
                    dosage AS dose,
                    remaining_quantity AS remaining,
                    CASE
                        WHEN LOWER(frequency) LIKE '%three%' THEN 3
                        WHEN LOWER(frequency) LIKE '%twice%' THEN 2
                        ELSE 1
                    END AS dailyDose,
                    30 AS totalPack,
                    COALESCE(last_refill, 'Not recorded') AS lastRefill
             FROM medications
             WHERE patient_id = :pid
             ORDER BY remaining_quantity ASC"
        );
        $stmt->execute(['pid' => $patientId]);

        return $this->json($response, ['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    public function refillSupply(Request $request, Response $response, array $args): Response
    {
        $patientId = $this->patientId($request);
        $medId = (int) $args['id'];
        $body = (array) $request->getParsedBody();
        $amount = (int) ($body['amount'] ?? 0);

        if ($amount <= 0) {
            return $this->json($response, ['success' => false, 'error' => 'Amount must be greater than zero.'], 422);
        }

        $check = $this->db->prepare('SELECT id FROM medications WHERE id = :id AND patient_id = :pid');
        $check->execute(['id' => $medId, 'pid' => $patientId]);
        if (!$check->fetch()) {
            return $this->json($response, ['success' => false, 'error' => 'Not found.'], 404);
        }

        $stmt = $this->db->prepare(
            'UPDATE medications SET remaining_quantity = remaining_quantity + :amount, last_refill = date("now") WHERE id = :id'
        );
        $stmt->execute(['amount' => $amount, 'id' => $medId]);

        return $this->json($response, ['success' => true]);
    }
}
