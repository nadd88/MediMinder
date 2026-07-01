<?php

use App\Controllers\AuthController;
use App\Controllers\MedicationController;
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

    // Protected routes
    $app->group('', function ($group) {
        // Patient profile
        $group->get('/patient/profile', [PatientController::class, 'getProfile']);
        $group->get('/patient/caregivers', [PatientController::class, 'getMyCaregivers']);
        
        // Medications
        $group->get('/medications', [MedicationController::class, 'index']);
        $group->get('/medications/{id}', [MedicationController::class, 'show']);
        
        // Dose tracking
        $group->post('/doses/mark', [DoseController::class, 'markDose']);
        $group->get('/patients/{patientId}/doses/today', [DoseController::class, 'getTodayDoses']);
        
        // Adherence
        $group->get('/patients/{patientId}/adherence', [AdherenceController::class, 'getStats']);
        $group->get('/patients/{patientId}/adherence/weekly', [AdherenceController::class, 'getWeekly']);
        
        // Patient management (Admin only)
        $group->post('/patients/caregivers', [PatientController::class, 'linkCaregiver'])
              ->add(new RoleMiddleware(['Admin']));
    })->add(new AuthMiddleware());
};