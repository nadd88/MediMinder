<?php

use App\Controllers\AuthController;
use App\Controllers\MedicationController;
use App\Controllers\PrescriptionController;
use App\Controllers\DoseController;
use App\Controllers\AdherenceController;
use App\Controllers\PatientController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use Slim\App;

return function (App $app) {
    // Public routes
    $app->post('/auth/register', [AuthController::class, 'register']);
    $app->post('/auth/login', [AuthController::class, 'login']);

    // Protected routes (require authentication)
    $app->group('', function ($group) {
        // Patient profile
        $group->get('/patient/profile', [PatientController::class, 'getProfile']);
        $group->get('/patient/caregivers', [PatientController::class, 'getMyCaregivers']);

        // Medications (read-only)
        $group->get('/medications', [MedicationController::class, 'index']);
        $group->get('/medications/{id}', [MedicationController::class, 'show']);

        // Patient-specific routes
        $group->get('/patients/{patientId}/prescriptions', [PrescriptionController::class, 'getByPatient']);
        $group->get('/patients/{patientId}/doses/today', [DoseController::class, 'getTodayDoses']);
        $group->put('/doses/{id}', [DoseController::class, 'markDose']);
        $group->get('/patients/{patientId}/adherence', [AdherenceController::class, 'getStats']);

        // Admin-only routes
        $group->post('/prescriptions', [PrescriptionController::class, 'create'])
              ->add(new RoleMiddleware(['Admin']));
    })->add(new AuthMiddleware());
};