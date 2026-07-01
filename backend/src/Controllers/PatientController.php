<?php

namespace App\Controllers;

use App\Models\Database;
use App\Models\DoseLog;
use App\Models\Medication;
use App\Models\PatientCaregiver;
use App\Models\User;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class PatientController
{
    public function getProfile(Request $request, Response $response): Response
    {
        $userId = (int) $request->getAttribute('user_id');
        $user = User::findById($userId);

        if (!$user) {
            $response->getBody()->write(json_encode(['error' => 'User not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $response->getBody()->write(json_encode([
            'success' => true,
            'data' => $user,
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    public function getMyCaregivers(Request $request, Response $response): Response
    {
        $patientId = (int) $request->getAttribute('user_id');
        $caregivers = PatientCaregiver::getCaregiversForPatient($patientId);

        $response->getBody()->write(json_encode([
            'success' => true,
            'data' => $caregivers,
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    public function linkCaregiver(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        if (empty($data['patient_id']) || empty($data['caregiver_id'])) {
            $response->getBody()->write(json_encode(['error' => 'patient_id and caregiver_id are required']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $id = PatientCaregiver::linkPatient(
            (int) $data['patient_id'],
            (int) $data['caregiver_id']
        );

        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'Caregiver linked successfully',
            'link_id' => $id,
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    }

    public function dashboard(Request $request, Response $response): Response
    {
        $patientId = (int) $request->getAttribute('user_id');
        $medications = Medication::findByPatient($patientId);
        $formatted = [];
        $dueToday = 0;
        $missedToday = 0;

        foreach ($medications as $medication) {
            $status = strtolower((string) ($medication['status'] ?? 'Pending'));
            $mappedStatus = $status === 'taken' ? 'taken' : ($status === 'missed' ? 'missed' : 'pending');

            if ($mappedStatus === 'missed') {
                $missedToday++;
            } elseif ($mappedStatus === 'pending') {
                $dueToday++;
            }

            $formatted[] = [
                'id' => (int) $medication['id'],
                'name' => $medication['medicine_name'],
                'dose' => $medication['dosage'],
                'time' => substr((string) $medication['schedule_time'], 0, 5),
                'status' => $mappedStatus,
            ];
        }

        $adherence = $this->calculateAdherence($patientId, 7);

        $payload = [
            'success' => true,
            'data' => [
                'summary' => [
                    'adherence7day' => $adherence['overall'],
                    'dueToday' => $dueToday,
                    'missedToday' => $missedToday,
                ],
                'medications' => $formatted,
            ],
        ];

        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    public function doses(Request $request, Response $response): Response
    {
        $patientId = (int) $request->getAttribute('user_id');
        $medications = Medication::findByPatient($patientId);
        $doseLogs = $this->getDoseLogs($patientId);
        $formatted = [];

        foreach ($medications as $medication) {
            $status = strtolower((string) ($medication['status'] ?? 'Pending'));
            $mappedStatus = $status === 'taken' ? 'taken' : ($status === 'missed' ? 'missed' : 'pending');
            $takenAt = null;

            foreach ($doseLogs as $log) {
                if ((int) $log['medication_id'] === (int) $medication['id']) {
                    $takenAt = $log['taken_at'] ?? $log['created_at'] ?? null;
                    break;
                }
            }

            $formatted[] = [
                'id' => (int) $medication['id'],
                'name' => $medication['medicine_name'],
                'dose' => $medication['dosage'],
                'time' => substr((string) $medication['schedule_time'], 0, 5),
                'status' => $mappedStatus,
                'takenAt' => $takenAt ? date('g:i A', strtotime((string) $takenAt)) : null,
            ];
        }

        $payload = [
            'success' => true,
            'data' => [
                'medications' => $formatted,
            ],
        ];

        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    public function markDose(Request $request, Response $response, array $args): Response
    {
        $patientId = (int) $request->getAttribute('user_id');
        $medicationId = (int) ($args['id'] ?? 0);
        $body = $request->getParsedBody() ?? [];
        $status = strtolower((string) ($body['status'] ?? 'taken'));

        if ($medicationId <= 0) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Invalid medication id']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $mappedStatus = match ($status) {
            'taken' => 'Taken',
            'skipped' => 'Missed',
            'missed' => 'Missed',
            default => 'Pending',
        };

        Medication::updateStatus($medicationId, $mappedStatus);
        DoseLog::create($medicationId, $patientId, $mappedStatus, date('Y-m-d H:i:s'));

        $payload = [
            'success' => true,
            'data' => [
                'medicationId' => $medicationId,
                'status' => $mappedStatus,
                'takenAt' => $mappedStatus === 'Taken' ? date('g:i A') : null,
            ],
        ];

        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    public function adherence(Request $request, Response $response): Response
    {
        $patientId = (int) $request->getAttribute('user_id');
        $days = (int) ($request->getQueryParams()['range'] ?? 7);
        $days = $days > 0 ? $days : 7;
        $adherence = $this->calculateAdherence($patientId, $days);

        $payload = [
            'success' => true,
            'data' => [
                'overall' => $adherence['overall'],
                'labels' => $adherence['labels'],
                'values' => $adherence['values'],
                'byMedication' => $adherence['byMedication'],
            ],
        ];

        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    public function supply(Request $request, Response $response): Response
    {
        $patientId = (int) $request->getAttribute('user_id');
        $medications = Medication::findByPatient($patientId);
        $formatted = [];

        foreach ($medications as $medication) {
            $remaining = (int) ($medication['remaining_quantity'] ?? 0);
            $dailyDose = $this->estimateDailyDose((string) ($medication['frequency'] ?? 'Once Daily'));
            $formatted[] = [
                'id' => (int) $medication['id'],
                'name' => $medication['medicine_name'],
                'dose' => $medication['dosage'],
                'remaining' => $remaining,
                'totalPack' => max($remaining, 30),
                'lastRefill' => date('M d'),
                'dailyDose' => $dailyDose,
            ];
        }

        $payload = [
            'success' => true,
            'data' => $formatted,
        ];

        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    public function refillSupply(Request $request, Response $response, array $args): Response
    {
        $medicationId = (int) ($args['id'] ?? 0);
        $body = $request->getParsedBody() ?? [];
        $amount = (int) ($body['amount'] ?? 0);

        if ($medicationId <= 0 || $amount <= 0) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Invalid refill request']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $db = Database::getConnection();
        $stmt = $db->prepare('UPDATE medications SET remaining_quantity = remaining_quantity + :amount WHERE id = :id');
        $stmt->execute(['amount' => $amount, 'id' => $medicationId]);

        $payload = [
            'success' => true,
            'message' => 'Medication refill recorded',
        ];

        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    private function calculateAdherence(int $patientId, int $days): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT medication_id, status, created_at FROM dose_logs WHERE patient_id = :patient_id ORDER BY created_at ASC');
        $stmt->execute(['patient_id' => $patientId]);
        $logs = $stmt->fetchAll();

        $labels = [];
        $values = [];
        $byMedication = [];
        $medications = Medication::findByPatient($patientId);

        for ($i = $days - 1; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-{$i} days"));
            $dayLogs = array_values(array_filter($logs, function ($log) use ($day): bool {
                return date('Y-m-d', strtotime((string) ($log['created_at'] ?? date('Y-m-d')))) === $day;
            }));

            $total = count($dayLogs);
            $taken = count(array_values(array_filter($dayLogs, function ($log): bool {
                return strtolower((string) ($log['status'] ?? '')) === 'taken';
            })));

            $labels[] = date('M d', strtotime($day));
            $values[] = $total > 0 ? round(($taken / $total) * 100) : 0;
        }

        foreach ($medications as $medication) {
            $medLogs = array_values(array_filter($logs, function ($log) use ($medication): bool {
                return (int) ($log['medication_id'] ?? 0) === (int) $medication['id'];
            }));
            $total = count($medLogs);
            $taken = count(array_values(array_filter($medLogs, function ($log): bool {
                return strtolower((string) ($log['status'] ?? '')) === 'taken';
            })));

            $byMedication[] = [
                'name' => $medication['medicine_name'],
                'percent' => $total > 0 ? round(($taken / $total) * 100) : 0,
            ];
        }

        return [
            'overall' => count($values) > 0 ? (int) round(array_sum($values) / count($values)) : 0,
            'labels' => $labels,
            'values' => $values,
            'byMedication' => $byMedication,
        ];
    }

    private function getDoseLogs(int $patientId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT medication_id, status, taken_at, created_at FROM dose_logs WHERE patient_id = :patient_id ORDER BY created_at DESC');
        $stmt->execute(['patient_id' => $patientId]);
        return $stmt->fetchAll();
    }

    private function estimateDailyDose(string $frequency): int
    {
        $normalized = strtolower($frequency);

        if (str_contains($normalized, 'twice')) {
            return 2;
        }

        if (str_contains($normalized, 'three')) {
            return 3;
        }

        if (str_contains($normalized, 'once')) {
            return 1;
        }

        if (preg_match('/(\d+)/', $frequency, $matches)) {
            return max(1, (int) $matches[1]);
        }

        return 1;
    }
}