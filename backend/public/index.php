<?php

use Slim\Factory\AppFactory;
use App\Controllers\AuthController;
use App\Controllers\PatientController;
use App\Models\Database;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;

require __DIR__ . '/../vendor/autoload.php';

if (class_exists('Dotenv\\Dotenv')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->safeLoad();
}

$app = AppFactory::create();

$app->addBodyParsingMiddleware();
$app->addErrorMiddleware(true, true, true);

// Add CORS Middleware LAST so it wraps everything (including error middleware)
$app->add(new App\Middleware\CorsMiddleware());

$app->get('/', function ($request, $response) {
    $response->getBody()->write(json_encode(['message' => 'MediMinder API is running']));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/auth/register', [AuthController::class, 'register']);
$app->post('/auth/login', [AuthController::class, 'login']);

$patientController = fn() => new PatientController(Database::getConnection());
$patientMiddleware = [new RoleMiddleware(['Patient']), new AuthMiddleware()];

$app->get('/patient/dashboard', function ($request, $response) use ($patientController) {
    return $patientController()->dashboard($request, $response);
})->add($patientMiddleware[0])->add($patientMiddleware[1]);

$app->get('/patient/doses', function ($request, $response) use ($patientController) {
    return $patientController()->doses($request, $response);
})->add($patientMiddleware[0])->add($patientMiddleware[1]);

$app->post('/patient/doses/{id}/status', function ($request, $response, array $args) use ($patientController) {
    return $patientController()->markDose($request, $response, $args);
})->add($patientMiddleware[0])->add($patientMiddleware[1]);

$app->get('/patient/adherence', function ($request, $response) use ($patientController) {
    return $patientController()->adherence($request, $response);
})->add($patientMiddleware[0])->add($patientMiddleware[1]);

$app->get('/patient/supply', function ($request, $response) use ($patientController) {
    return $patientController()->supply($request, $response);
})->add($patientMiddleware[0])->add($patientMiddleware[1]);

$app->post('/patient/supply/{id}/refill', function ($request, $response, array $args) use ($patientController) {
    return $patientController()->refillSupply($request, $response, $args);
})->add($patientMiddleware[0])->add($patientMiddleware[1]);

$app->get('/me', function ($request, $response) {
    $response->getBody()->write(json_encode([
        'message' => 'You are authenticated',
        'user_id' => $request->getAttribute('user_id'),
        'role' => $request->getAttribute('user_role'),
    ]));
    return $response->withHeader('Content-Type', 'application/json');
})->add(new AuthMiddleware());

$app->get('/admin/test', function ($request, $response) {
    $response->getBody()->write(json_encode(['message' => 'Welcome, Admin']));
    return $response->withHeader('Content-Type', 'application/json');
})->add(new RoleMiddleware(['Admin']))->add(new AuthMiddleware());

$app->run();
