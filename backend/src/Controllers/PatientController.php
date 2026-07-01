<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\PatientCaregiver;
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
            'data' => $user
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    public function getMyCaregivers(Request $request, Response $response): Response
    {
        $patientId = (int) $request->getAttribute('user_id');
        $caregivers = PatientCaregiver::getCaregiversForPatient($patientId);
        
        $response->getBody()->write(json_encode([
            'success' => true,
            'data' => $caregivers
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
            'link_id' => $id
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    }
}