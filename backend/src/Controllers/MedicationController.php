<?php

namespace App\Controllers;

use App\Models\Medication;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class MedicationController
{
    public function index(Request $request, Response $response): Response
    {
        $medications = Medication::findAll();
        
        $response->getBody()->write(json_encode([
            'success' => true,
            'data' => $medications
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];
        $medication = Medication::findById($id);
        
        if (!$medication) {
            $response->getBody()->write(json_encode(['error' => 'Medication not found']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
        
        $response->getBody()->write(json_encode([
            'success' => true,
            'data' => $medication
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }
}