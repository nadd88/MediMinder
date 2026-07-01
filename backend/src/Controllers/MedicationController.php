<?php

namespace App\Controllers;

use App\Models\Medication;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class MedicationController
{
    public function index(Request $request, Response $response): Response
    {
        $userId = (int) $request->getAttribute('user_id');
        $userRole = $request->getAttribute('user_role');
        
        // Patients see their own medications, Caregivers see linked patients, Admin sees all
        if ($userRole === 'Patient') {
            $medications = Medication::findByPatient($userId);
        } elseif ($userRole === 'Caregiver') {
            // Get all patients linked to this caregiver
            $patients = \App\Models\PatientCaregiver::getPatientsForCaregiver($userId);
            $medications = [];
            foreach ($patients as $patient) {
                $patientMeds = Medication::findByPatient($patient['id']);
                foreach ($patientMeds as $med) {
                    $med['patient_name'] = $patient['name'];
                    $medications[] = $med;
                }
            }
        } else {
            // Admin - get all medications across all patients
            $db = \App\Models\Database::getConnection();
            $stmt = $db->query("
                SELECT m.*, u.name as patient_name 
                FROM medications m
                JOIN users u ON m.patient_id = u.id
                ORDER BY m.schedule_time ASC
            ");
            $medications = $stmt->fetchAll();
        }
        
        $response->getBody()->write(json_encode([
            'success' => true,
            'data' => $medications
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $userId = (int) $request->getAttribute('user_id');
        $userRole = $request->getAttribute('user_role');
        
        $medication = Medication::findById($id);
        
        if (!$medication) {
            $response->getBody()->write(json_encode(['error' => 'Medication not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
        
        // Check permission
        if ($userRole === 'Patient' && $medication['patient_id'] !== $userId) {
            $response->getBody()->write(json_encode(['error' => 'Unauthorized']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }
        
        $response->getBody()->write(json_encode([
            'success' => true,
            'data' => $medication
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }
}