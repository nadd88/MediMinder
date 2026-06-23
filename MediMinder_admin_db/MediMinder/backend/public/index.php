<?php
declare(strict_types=1);

use App\Middleware\CorsMiddleware;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

// ---- Environment ----
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

// ---- App ----
$app = AppFactory::create();

// Parse JSON request bodies into getParsedBody()
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();

// CORS (outermost so it also wraps error responses)
$app->add(new CorsMiddleware());

// Pre-flight: answer OPTIONS for any path
$app->options('/{routes:.+}', function ($request, $response) {
    return $response;
});

// Error handling
$debug = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOL);
$errorMiddleware = $app->addErrorMiddleware($debug, true, true);
$errorMiddleware->getDefaultErrorHandler()->forceContentType('application/json');

// ---- Routes ----
(require __DIR__ . '/../routes/api.php')($app);

$app->run();
