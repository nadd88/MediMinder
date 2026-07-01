<?php

use Slim\Factory\AppFactory;
use App\Controllers\AuthController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;

require __DIR__ . '/../vendor/autoload.php';

// Load .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$app = AppFactory::create();

$app->addBodyParsingMiddleware();
$app->addErrorMiddleware(true, true, true);

// --- CORS middleware ---
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', 'http://localhost:5173')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
});

// Handle CORS preflight (OPTIONS) requests
$app->options('/{routes:.+}', function ($request, $response) {
    return $response;
});

// Test route
$app->get('/', function ($request, $response) {
    $response->getBody()->write(json_encode(['message' => 'MediMinder API is running']));
    return $response->withHeader('Content-Type', 'application/json');
});

// Auth routes
$app->post('/auth/register', [AuthController::class, 'register']);
$app->post('/auth/login', [AuthController::class, 'login']);

// Example protected routes (any logged-in user)
$app->get('/me', function ($request, $response) {
    $userId = $request->getAttribute('user_id');
    $role = $request->getAttribute('user_role');
    $response->getBody()->write(json_encode([
        'message' => 'You are authenticated',
        'user_id' => $userId,
        'role' => $role,
    ]));
    return $response->withHeader('Content-Type', 'application/json');
})->add(new AuthMiddleware());

// Example admin-only route
$app->get('/admin/test', function ($request, $response) {
    $response->getBody()->write(json_encode(['message' => 'Welcome, Admin']));
    return $response->withHeader('Content-Type', 'application/json');
})->add(new RoleMiddleware(['Admin']))
  ->add(new AuthMiddleware());

$app->run();