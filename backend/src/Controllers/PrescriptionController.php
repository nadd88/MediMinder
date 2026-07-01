<?php

namespace App\Controllers;

use App\Models\Prescription;
use App\Models\Medication;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class PrescriptionController
{
    public function getByPatient(Request $request, Response $response, array $args): Response
    {
        $patientId = (int) $args['patientId'];
        $userId = (int) $request->getAttribute('user_id');
        $userRole = $request->getAttribute('user_role');
        
        // Check permission: only patient themselves, their caregiver, or admin can view
        if ($userRole !== 'Admin' && $userRole !== 'Caregiver' && $userId !== $patientId) {
            $response->getBody()->write(json_encode(['error' => 'Unauthorized']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }
        
        $prescriptions = Prescription::findByPatient($patientId);
        
        $response->getBody()->write(json_encode([
            'success' => true,
            'data' => $prescriptions
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    public function create(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $userRole = $request->getAttribute('user_role');
        
        // Only Admin can create prescriptions
        if ($userRole !== 'Admin') {
            $response->getBody()->write(json_encode(['error' => 'Only admins can create prescriptions']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }
        
        $required = ['patient_id', 'medication_id', 'dose', 'frequency', 'start_date'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $response->getBody()->write(json_encode(['error' => "Field '$field' is required"]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
        }
        
        // Verify medication exists
        if (!Medication::findById((int) $data['medication_id'])) {
            $response->getBody()->write(json_encode(['error' => 'Medication not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
        
        $id = Prescription::create(
            (int) $data['patient_id'],
            (int) $data['medication_id'],
            $data['dose'],
            $data['frequency'],
            $data['start_date'],
            $data['end_date'] ?? null,
            $data['notes'] ?? null
        );
        
        $prescription = Prescription::findById($id);
        
        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'Prescription created successfully',
            'data' => $prescription
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    }
}