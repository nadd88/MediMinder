<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Psr7\Response as SlimResponse;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Exception;

class AuthMiddleware
{
    public function __invoke(Request $request, Handler $handler): Response
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return $this->unauthorized('Missing or invalid Authorization header');
        }

        $token = substr($authHeader, 7); // strip "Bearer "

        try {
            $decoded = JWT::decode($token, new Key($_ENV['JWT_SECRET'], 'HS256'));
        } catch (ExpiredException $e) {
            return $this->unauthorized('Token has expired');
        } catch (Exception $e) {
            return $this->unauthorized('Invalid token');
        }

        // Attach decoded user info to the request so controllers can read it
        $request = $request->withAttribute('user_id', $decoded->sub);
        $request = $request->withAttribute('user_role', $decoded->role);

        return $handler->handle($request);
    }

    private function unauthorized(string $message): Response
    {
        $response = new SlimResponse();
        $response->getBody()->write(json_encode(['error' => $message]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
    }
}