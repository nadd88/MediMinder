<?php

namespace App\Controllers;

use App\Models\Patient;
use App\Models\Medication;
use App\Models\Prescription;
use App\Models\DoseLog;
use App\Models\AuditLog;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class AdminController
{
    private function json(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }

    // GET /admin/patients
    public function listPatients(Request $request, Response $response): Response
    {
        return $this->json($response, ['patients' => Patient::all()]);
    }

    // GET /admin/medications?search=
    public function searchMedications(Request $request, Response $response): Response
    {
        $term = $request->getQueryParams()['search'] ?? '';
        return $this->json($response, ['medications' => Medication::search($term)]);
    }

    // GET /admin/patients/{id}/prescriptions
    public function listPrescriptions(Request $request, Response $response, array $args): Response
    {
        $patientId = (int) $args['id'];
        if (!Patient::exists($patientId)) {
            return $this->json($response, ['error' => 'Patient not found'], 404);
        }
        return $this->json($response, ['prescriptions' => Prescription::forPatient($patientId)]);
    }

    // POST /admin/patients/{id}/prescriptions
    public function createPrescription(Request $request, Response $response, array $args): Response
    {
        $patientId = (int) $args['id'];
        $data = $request->getParsedBody() ?? [];
        $errors = [];

        if (!Patient::exists($patientId)) {
            return $this->json($response, ['error' => 'Patient not found'], 404);
        }
        if (empty($data['medication_id']) || !Medication::exists((int) $data['medication_id'])) {
            $errors['medication_id'] = 'Please choose a valid medication';
        }
        if (empty($data['dose'])) {
            $errors['dose'] = 'Dose is required';
        }
        if (empty($data['frequency'])) {
            $errors['frequency'] = 'Frequency is required';
        }
        if (empty($data['start_date'])) {
            $errors['start_date'] = 'Start date is required';
        }
        if (!empty($data['end_date']) && !empty($data['start_date']) && $data['end_date'] < $data['start_date']) {
            $errors['end_date'] = 'End date cannot be before the start date';
        }

        if (!empty($errors)) {
            return $this->json($response, ['errors' => $errors], 422);
        }

        $adminId = (int) $request->getAttribute('user_id');

        $prescriptionId = Prescription::create([
            'patient_id'    => $patientId,
            'medication_id' => (int) $data['medication_id'],
            'prescribed_by' => $adminId,
            'dose'          => $data['dose'],
            'frequency'     => $data['frequency'],
            'start_date'    => $data['start_date'],
            'end_date'      => $data['end_date'] ?? null,
            'notes'         => $data['notes'] ?? null,
        ]);

        $medication = Medication::find((int) $data['medication_id']);
        AuditLog::record(
            $adminId,
            $patientId,
            'prescription.created',
            "Prescribed {$medication['name']} ({$data['dose']}, {$data['frequency']})"
        );

        return $this->json($response, [
            'message' => 'Prescription created',
            'prescription_id' => $prescriptionId,
        ], 201);
    }

    // GET /admin/patients/{id}/interactions?medication_id=
    public function checkInteractions(Request $request, Response $response, array $args): Response
    {
        $patientId = (int) $args['id'];
        $medicationId = (int) ($request->getQueryParams()['medication_id'] ?? 0);

        if (!Patient::exists($patientId)) {
            return $this->json($response, ['error' => 'Patient not found'], 404);
        }
        if (!$medicationId || !Medication::exists($medicationId)) {
            return $this->json($response, ['error' => 'Valid medication_id is required'], 400);
        }

        $interactions = Medication::checkInteractionsForPatient($patientId, $medicationId);
        return $this->json($response, ['interactions' => $interactions]);
    }

    // GET /admin/patients/{id}/report?from=&to=
    public function getReport(Request $request, Response $response, array $args): Response
    {
        $patientId = (int) $args['id'];
        [$from, $to] = $this->resolveRange($request);

        if (!Patient::exists($patientId)) {
            return $this->json($response, ['error' => 'Patient not found'], 404);
        }

        return $this->json($response, [
            'summary'        => DoseLog::summary($patientId, $from, $to),
            'per_medication' => DoseLog::perMedication($patientId, $from, $to),
            'daily_trend'    => DoseLog::dailyTrend($patientId, $from, $to),
        ]);
    }

    // GET /admin/patients/{id}/audit?limit=
    public function listAudit(Request $request, Response $response, array $args): Response
    {
        $patientId = (int) $args['id'];
        $limit = (int) ($request->getQueryParams()['limit'] ?? 20);

        if (!Patient::exists($patientId)) {
            return $this->json($response, ['error' => 'Patient not found'], 404);
        }

        return $this->json($response, ['audit' => AuditLog::forPatient($patientId, $limit)]);
    }

    // GET /admin/patients/{id}/report/csv?from=&to=
    public function exportReportCsv(Request $request, Response $response, array $args): Response
    {
        $patientId = (int) $args['id'];
        [$from, $to] = $this->resolveRange($request);

        if (!Patient::exists($patientId)) {
            return $this->json($response, ['error' => 'Patient not found'], 404);
        }

        $rows = DoseLog::rowsForExport($patientId, $from, $to);

        $csv = "Date,Medication,Status,Logged At\n";
        foreach ($rows as $r) {
            $csv .= sprintf(
                "%s,%s,%s,%s\n",
                $r['scheduled_date'],
                str_replace(',', ' ', $r['medication']),
                $r['status'],
                $r['logged_at']
            );
        }

        $response->getBody()->write($csv);
        return $response
            ->withHeader('Content-Type', 'text/csv')
            ->withHeader('Content-Disposition', "attachment; filename=\"adherence_{$patientId}_{$from}_{$to}.csv\"");
    }

    /** Shared date-range resolver: defaults to the last 30 days. */
    private function resolveRange(Request $request): array
    {
        $q = $request->getQueryParams();
        $to = $q['to'] ?? date('Y-m-d');
        $from = $q['from'] ?? date('Y-m-d', strtotime('-29 days'));
        return [$from, $to];
    }
}
