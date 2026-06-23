<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use App\Support\Http;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Adherence reporting for the admin "Adherence report" screen.
 *
 * Status logic:
 *   Taken   -> status = 'Taken'
 *   Skipped -> status = 'Skipped'
 *   Missed  -> status = 'Pending' AND scheduled_at < NOW()   (DERIVED)
 *   Upcoming-> status = 'Pending' AND scheduled_at >= NOW()  (excluded from %)
 *
 * adherence% = Taken / (Taken + Skipped + Missed)  over due doses only.
 */
final class ReportController
{
    /** GET /api/admin/patients/{id}/report?from=YYYY-MM-DD&to=YYYY-MM-DD */
    public function summary(Request $request, Response $response, array $args): Response
    {
        $patientId = (int) $args['id'];
        [$from, $to] = $this->range($request);

        $patient = $this->patient($patientId);
        if (!$patient) {
            return Http::json($response, ['error' => 'Patient not found.'], 404);
        }

        $overall    = $this->overall($patientId, $from, $to);
        $perMed     = $this->perMedication($patientId, $from, $to);
        $daily      = $this->dailyTrend($patientId, $from, $to);

        return Http::json($response, [
            'patient'        => $patient,
            'range'          => ['from' => $from, 'to' => $to],
            'summary'        => $overall,
            'per_medication' => $perMed,
            'daily_trend'    => $daily,
        ]);
    }

    /** GET /api/admin/patients/{id}/report.csv?from=&to= */
    public function csv(Request $request, Response $response, array $args): Response
    {
        $patientId = (int) $args['id'];
        [$from, $to] = $this->range($request);

        $patient = $this->patient($patientId);
        if (!$patient) {
            return Http::json($response, ['error' => 'Patient not found.'], 404);
        }

        $perMed   = $this->perMedication($patientId, $from, $to);
        $overall  = $this->overall($patientId, $from, $to);

        $fh = fopen('php://temp', 'r+');
        fputcsv($fh, ['MediMinder Adherence Report']);
        fputcsv($fh, ['Patient', $patient['name']]);
        fputcsv($fh, ['Period', $from . ' to ' . $to]);
        fputcsv($fh, []);
        fputcsv($fh, ['Medication', 'Taken', 'Skipped', 'Missed', 'Adherence %']);
        foreach ($perMed as $r) {
            fputcsv($fh, [$r['medication'], $r['taken'], $r['skipped'], $r['missed'], $r['adherence']]);
        }
        fputcsv($fh, []);
        fputcsv($fh, ['Overall', $overall['taken'], $overall['skipped'], $overall['missed'], $overall['adherence']]);
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);

        $filename = 'adherence_' . preg_replace('/\s+/', '_', strtolower($patient['name'])) . '_' . $from . '_' . $to . '.csv';
        $response->getBody()->write($csv);

        return $response
            ->withHeader('Content-Type', 'text/csv')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    // ---- internals -------------------------------------------------

    /** @return array{0:string,1:string} */
    private function range(Request $request): array
    {
        $q    = $request->getQueryParams();
        $to   = $this->validDate($q['to']   ?? null) ?? date('Y-m-d');
        $from = $this->validDate($q['from'] ?? null) ?? date('Y-m-d', strtotime($to . ' -29 days'));
        return [$from, $to];
    }

    private function validDate(?string $value): ?string
    {
        if (!$value) {
            return null;
        }
        $d = \DateTime::createFromFormat('Y-m-d', $value);
        return ($d && $d->format('Y-m-d') === $value) ? $value : null;
    }

    private function patient(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            "SELECT id, name, email FROM users WHERE id = :id AND role = 'Patient' LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /** Shared SQL fragment that classifies each due dose. */
    private const STATUS_EXPR =
        "SUM(d.status = 'Taken')                                  AS taken,
         SUM(d.status = 'Skipped')                                AS skipped,
         SUM(d.status = 'Pending' AND d.scheduled_at < NOW())     AS missed";

    private function overall(int $patientId, string $from, string $to): array
    {
        $sql = "SELECT " . self::STATUS_EXPR . "
                FROM dose_log d
                JOIN prescription p ON p.id = d.prescription_id
                WHERE p.patient_id = :pid
                  AND DATE(d.scheduled_at) BETWEEN :from AND :to
                  AND d.scheduled_at < NOW()";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':pid' => $patientId, ':from' => $from, ':to' => $to]);
        $r = $stmt->fetch() ?: ['taken' => 0, 'skipped' => 0, 'missed' => 0];

        return $this->withAdherence($r);
    }

    private function perMedication(int $patientId, string $from, string $to): array
    {
        $sql = "SELECT m.name AS medication, m.strength, " . self::STATUS_EXPR . "
                FROM dose_log d
                JOIN prescription p ON p.id = d.prescription_id
                JOIN medication m   ON m.id = p.medication_id
                WHERE p.patient_id = :pid
                  AND DATE(d.scheduled_at) BETWEEN :from AND :to
                  AND d.scheduled_at < NOW()
                GROUP BY m.id, m.name, m.strength
                ORDER BY m.name";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':pid' => $patientId, ':from' => $from, ':to' => $to]);

        return array_map(fn ($r) => $this->withAdherence($r), $stmt->fetchAll());
    }

    private function dailyTrend(int $patientId, string $from, string $to): array
    {
        $sql = "SELECT DATE(d.scheduled_at) AS day,
                       SUM(d.status = 'Taken') AS taken,
                       COUNT(*)               AS due
                FROM dose_log d
                JOIN prescription p ON p.id = d.prescription_id
                WHERE p.patient_id = :pid
                  AND DATE(d.scheduled_at) BETWEEN :from AND :to
                  AND d.scheduled_at < NOW()
                GROUP BY DATE(d.scheduled_at)
                ORDER BY day";
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([':pid' => $patientId, ':from' => $from, ':to' => $to]);

        return array_map(function ($r) {
            $due = (int) $r['due'];
            return [
                'day'       => $r['day'],
                'adherence' => $due > 0 ? (int) round(100 * (int) $r['taken'] / $due) : 0,
            ];
        }, $stmt->fetchAll());
    }

    /** Adds an integer adherence percentage to a {taken,skipped,missed} row. */
    private function withAdherence(array $r): array
    {
        $taken   = (int) ($r['taken'] ?? 0);
        $skipped = (int) ($r['skipped'] ?? 0);
        $missed  = (int) ($r['missed'] ?? 0);
        $total   = $taken + $skipped + $missed;

        $r['taken']     = $taken;
        $r['skipped']   = $skipped;
        $r['missed']    = $missed;
        $r['due']       = $total;
        $r['adherence'] = $total > 0 ? (int) round(100 * $taken / $total) : 0;

        return $r;
    }
}
