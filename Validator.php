<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use App\Support\Audit;
use App\Support\Http;
use App\Support\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Admin prescription management for a patient, including the static
 * drug-interaction lookup (PR1 Objective 7).
 */
final class PrescriptionController
{
    /** GET /api/admin/patients/{id}/prescriptions */
    public function index(Request $request, Response $response, array $args): Response
    {
        $patientId = (int) $args['id'];

        $stmt = Database::connection()->prepare(
            "SELECT p.id, p.dose, p.frequency, p.start_date, p.end_date, p.notes,
                    m.id AS medication_id, m.name AS medication_name,
                    m.strength, m.form,
                    CASE
                        WHEN p.end_date IS NOT NULL AND p.end_date < CURDATE() THEN 'Expired'
                        WHEN p.end_date IS NOT NULL AND p.end_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 'Ending'
                        ELSE 'Active'
                    END AS status
             FROM prescription p
             JOIN medication m ON m.id = p.medication_id
             WHERE p.patient_id = :pid
             ORDER BY p.start_date DESC"
        );
        $stmt->execute([':pid' => $patientId]);

        return Http::json($response, ['prescriptions' => $stmt->fetchAll()]);
    }

    /**
     * GET /api/admin/patients/{id}/interaction-check?medication_id=1
     * Returns any interactions between the candidate medication and the
     * patient's currently active prescriptions. Drives the form warning.
     */
    public function interactionCheck(Request $request, Response $response, array $args): Response
    {
        $patientId    = (int) $args['id'];
        $medicationId = (int) ($request->getQueryParams()['medication_id'] ?? 0);

        if ($medicationId <= 0) {
            return Http::json($response, ['interactions' => []]);
        }

        // di rows are stored normalised (a_id < b_id), so check both directions.
        $sql = "SELECT m.name AS other_medication, di.severity, di.warning
                FROM prescription p
                JOIN drug_interaction di
                     ON (di.medication_a_id = :mid AND di.medication_b_id = p.medication_id)
                     OR (di.medication_b_id = :mid2 AND di.medication_a_id = p.medication_id)
                JOIN medication m ON m.id = p.medication_id
                WHERE p.patient_id = :pid
                  AND p.medication_id <> :mid3
                  AND (p.end_date IS NULL OR p.end_date >= CURDATE())
                GROUP BY m.name, di.severity, di.warning";

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            ':mid'  => $medicationId,
            ':mid2' => $medicationId,
            ':mid3' => $medicationId,
            ':pid'  => $patientId,
        ]);

        return Http::json($response, ['interactions' => $stmt->fetchAll()]);
    }

    /**
     * POST /api/admin/patients/{id}/prescriptions
     * Body: { medication_id, dose, frequency, start_date, end_date?, notes? }
     */
    public function store(Request $request, Response $response, array $args): Response
    {
        $auth      = $request->getAttribute('auth');
        $patientId = (int) $args['id'];
        $body      = (array) $request->getParsedBody();

        $v = new Validator($body);
        $v->required('medication_id', 'Medication')->integer('medication_id')
          ->required('dose', 'Dose')
          ->required('frequency', 'Frequency')
          ->required('start_date', 'Start date')->date('start_date')
          ->date('end_date');
        if ($v->fails()) {
            return Http::json($response, ['errors' => $v->errors()], 422);
        }

        $pdo = Database::connection();

        // Confirm the patient and medication actually exist.
        $patient = $this->fetchPatient($pdo, $patientId);
        if (!$patient) {
            return Http::json($response, ['error' => 'Patient not found.'], 404);
        }
        $med = $this->fetchMedication($pdo, (int) $body['medication_id']);
        if (!$med) {
            return Http::json($response, ['errors' => ['medication_id' => 'Unknown medication.']], 422);
        }

        $stmt = $pdo->prepare(
            'INSERT INTO prescription (patient_id, medication_id, dose, frequency, start_date, end_date, notes)
             VALUES (:pid, :mid, :dose, :freq, :start, :end, :notes)'
        );
        $stmt->execute([
            ':pid'   => $patientId,
            ':mid'   => (int) $body['medication_id'],
            ':dose'  => trim((string) $body['dose']),
            ':freq'  => trim((string) $body['frequency']),
            ':start' => $body['start_date'],
            ':end'   => !empty($body['end_date']) ? $body['end_date'] : null,
            ':notes' => isset($body['notes']) && $body['notes'] !== '' ? trim((string) $body['notes']) : null,
        ]);

        $newId = (int) $pdo->lastInsertId();

        Audit::record((int) $auth['id'], $patientId, 'prescription_created', 'prescription', $newId,
            $med['name'] . ' ' . $med['strength'] . ' added for ' . $patient['name']);

        return Http::json($response, [
            'prescription' => ['id' => $newId, 'medication_name' => $med['name']],
        ], 201);
    }

    /**
     * PUT /api/admin/prescriptions/{id}
     * Body: { dose, frequency, start_date, end_date?, notes? }
     */
    public function update(Request $request, Response $response, array $args): Response
    {
        $auth = $request->getAttribute('auth');
        $id   = (int) $args['id'];
        $body = (array) $request->getParsedBody();

        $v = new Validator($body);
        $v->required('dose', 'Dose')
          ->required('frequency', 'Frequency')
          ->required('start_date', 'Start date')->date('start_date')
          ->date('end_date');
        if ($v->fails()) {
            return Http::json($response, ['errors' => $v->errors()], 422);
        }

        $pdo = Database::connection();

        $existing = $pdo->prepare(
            'SELECT p.id, p.patient_id, m.name, m.strength
             FROM prescription p JOIN medication m ON m.id = p.medication_id
             WHERE p.id = :id LIMIT 1'
        );
        $existing->execute([':id' => $id]);
        $row = $existing->fetch();
        if (!$row) {
            return Http::json($response, ['error' => 'Prescription not found.'], 404);
        }

        $stmt = $pdo->prepare(
            'UPDATE prescription
             SET dose = :dose, frequency = :freq, start_date = :start, end_date = :end, notes = :notes
             WHERE id = :id'
        );
        $stmt->execute([
            ':dose'  => trim((string) $body['dose']),
            ':freq'  => trim((string) $body['frequency']),
            ':start' => $body['start_date'],
            ':end'   => !empty($body['end_date']) ? $body['end_date'] : null,
            ':notes' => isset($body['notes']) && $body['notes'] !== '' ? trim((string) $body['notes']) : null,
            ':id'    => $id,
        ]);

        Audit::record((int) $auth['id'], (int) $row['patient_id'], 'prescription_updated', 'prescription', $id,
            $row['name'] . ' ' . $row['strength'] . ' prescription updated');

        return Http::json($response, ['prescription' => ['id' => $id]]);
    }

    private function fetchPatient(\PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare("SELECT id, name FROM users WHERE id = :id AND role = 'Patient' LIMIT 1");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    private function fetchMedication(\PDO $pdo, int $id): ?array
    {
        $stmt = $pdo->prepare('SELECT id, name, strength FROM medication WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }
}
