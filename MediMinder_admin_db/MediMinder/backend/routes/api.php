<?php
declare(strict_types=1);

use App\Controllers\AuditController;
use App\Controllers\AuthController;
use App\Controllers\MedicationController;
use App\Controllers\PatientController;
use App\Controllers\PrescriptionController;
use App\Controllers\ReportController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app): void {

    // Health check
    $app->get('/', function ($req, $res) {
        $res->getBody()->write(json_encode(['service' => 'MediMinder API', 'status' => 'ok']));
        return $res->withHeader('Content-Type', 'application/json');
    });

    // ---- Public auth routes ----
    $app->post('/api/auth/login', [AuthController::class, 'login']);

    // ---- Authenticated routes (any logged-in role) ----
    $app->group('/api', function (RouteCollectorProxy $g) {
        $g->get('/me', [AuthController::class, 'me']);
    })->add(new AuthMiddleware());

    // ---- Admin-only routes (Clinic Administrator) ----
    $app->group('/api/admin', function (RouteCollectorProxy $g) {

        // Patients
        $g->get('/patients', [PatientController::class, 'index']);
        $g->post('/patients', [PatientController::class, 'store']);
        $g->get('/patients/{id:[0-9]+}', [PatientController::class, 'show']);

        // Drug catalogue
        $g->get('/medications', [MedicationController::class, 'index']);

        // Prescriptions (scoped to a patient)
        $g->get('/patients/{id:[0-9]+}/prescriptions', [PrescriptionController::class, 'index']);
        $g->post('/patients/{id:[0-9]+}/prescriptions', [PrescriptionController::class, 'store']);
        $g->get('/patients/{id:[0-9]+}/interaction-check', [PrescriptionController::class, 'interactionCheck']);
        $g->put('/prescriptions/{id:[0-9]+}', [PrescriptionController::class, 'update']);

        // Adherence reports
        $g->get('/patients/{id:[0-9]+}/report', [ReportController::class, 'summary']);
        $g->get('/patients/{id:[0-9]+}/report.csv', [ReportController::class, 'csv']);

        // Audit log
        $g->get('/audit', [AuditController::class, 'index']);
    })
        ->add(new RoleMiddleware(['Admin']))   // runs second: checks role
        ->add(new AuthMiddleware());           // runs first: verifies JWT
};
