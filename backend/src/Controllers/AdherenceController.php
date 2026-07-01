<?php

namespace App\Controllers;

use App\Models\DoseLog;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class AdherenceController
{
    public function getStats(Request $request, Response $response, array $args): Response
    {
        $patientId = (int) $args['patientId'];
        $userId = (int) $request->getAttribute('user_id');
        $userRole = $request->getAttribute('user_role');
        
        // Check permission
        if ($userRole !== 'Admin' && $userRole !== 'Caregiver' && $userId !== $patientId) {
            $response->getBody()->write(json_encode(['error' => 'Unauthorized']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }
        
        $days = (int) ($request->getQueryParams()['days'] ?? 30);
        $stats = DoseLog::getAdherenceStats($patientId, $days);
        
        $response->getBody()->write(json_encode([
            'success' => true,
            'data' => $stats
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }
}