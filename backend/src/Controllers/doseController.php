<?php

namespace App\Controllers;

use App\Models\DoseLog;
use App\Models\Prescription;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class DoseController
{
    public function getTodayDoses(Request $request, Response $response, array $args): Response
    {
        $patientId = (int) $args['patientId'];
        $userId = (int) $request->getAttribute('user_id');
        $userRole = $request->getAttribute('user_role');
        
        // Check permission
        if ($userRole !== 'Admin' && $userRole !== 'Caregiver' && $userId !== $patientId) {
            $response->getBody()->write(json_encode(['error' => 'Unauthorized']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }
        
        $doses = DoseLog::getTodayDoses($patientId);
        
        $response->getBody()->write(json_encode([
            'success' => true,
            'data' => $doses
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    public function markDose(Request $request, Response $response, array $args): Response
    {
        $doseLogId = (int) $args['id'];
        $data = $request->getParsedBody();
        $userId = (int) $request->getAttribute('user_id');
        $userRole = $request->getAttribute('user_role');
        
        if (empty($data['status']) || !in_array($data['status'], ['taken', 'skipped'])) {
            $response->getBody()->write(json_encode(['error' => 'Status must be "taken" or "skipped"']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
        
        // Get the dose log to check permission
        $db = \App\Models\Database::getConnection();
        $stmt = $db->prepare("
            SELECT dl.*, p.patient_id 
            FROM dose_logs dl
            JOIN prescriptions p ON dl.prescription_id = p.id
            WHERE dl.id = :id
        ");
        $stmt->execute(['id' => $doseLogId]);
        $dose = $stmt->fetch();
        
        if (!$dose) {
            $response->getBody()->write(json_encode(['error' => 'Dose log not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
        
        // Check permission: patient themselves, caregiver, or admin
        if ($userRole !== 'Admin' && $userRole !== 'Caregiver' && $userId !== (int) $dose['patient_id']) {
            $response->getBody()->write(json_encode(['error' => 'Unauthorized']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }
        
        $success = DoseLog::markDose($doseLogId, $data['status']);
        
        $response->getBody()->write(json_encode([
            'success' => $success,
            'message' => "Dose marked as {$data['status']}"
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }
}