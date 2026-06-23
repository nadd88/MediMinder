<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Support\Http;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Psr7\Response as SlimResponse;

/**
 * Restricts a route to one or more roles. Must run AFTER AuthMiddleware
 * (it reads the "auth" attribute that AuthMiddleware sets).
 *
 * Usage:
 *   $group->...->add(new RoleMiddleware(['Admin']));
 */
final class RoleMiddleware implements MiddlewareInterface
{
    /** @param string[] $allowedRoles */
    public function __construct(private array $allowedRoles)
    {
    }

    public function process(Request $request, Handler $handler): Response
    {
        $auth = $request->getAttribute('auth');

        if (!is_array($auth) || !in_array($auth['role'] ?? '', $this->allowedRoles, true)) {
            $response = new SlimResponse();
            return Http::json($response, [
                'error' => 'Forbidden: your role does not have access to this resource.',
            ], 403);
        }

        return $handler->handle($request);
    }
}
