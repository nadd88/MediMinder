<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Support\Http;
use App\Support\JwtService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Psr7\Response as SlimResponse;

/**
 * Verifies the Bearer JWT and attaches the auth claims to the request
 * as the "auth" attribute: ['id' => int, 'name' => string, 'role' => string].
 * Rejects with 401 if the token is missing or invalid.
 */
final class AuthMiddleware implements MiddlewareInterface
{
    public function process(Request $request, Handler $handler): Response
    {
        $header = $request->getHeaderLine('Authorization');

        if (!preg_match('/^Bearer\s+(.+)$/i', $header, $m)) {
            return $this->unauthorized('Missing or malformed Authorization header.');
        }

        $claims = (new JwtService())->verify($m[1]);
        if ($claims === null) {
            return $this->unauthorized('Invalid or expired token.');
        }

        $request = $request->withAttribute('auth', [
            'id'   => (int) $claims['sub'],
            'name' => (string) $claims['name'],
            'role' => (string) $claims['role'],
        ]);

        return $handler->handle($request);
    }

    private function unauthorized(string $message): Response
    {
        $response = new SlimResponse();
        return Http::json($response, ['error' => $message], 401);
    }
}
