<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Psr7\Response as SlimResponse;
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

        $jwtSecret = getenv('JWT_SECRET') ?: ($_ENV['JWT_SECRET'] ?? 'dev-secret');

        try {
            $decoded = $this->decodeToken($token, $jwtSecret);
        } catch (Exception $e) {
            return $this->unauthorized('Invalid token');
        }

        if (!isset($decoded['sub'], $decoded['role']) || !is_int($decoded['sub'])) {
            return $this->unauthorized('Invalid token');
        }

        // Attach decoded user info to the request so controllers can read it
        $request = $request->withAttribute('user_id', $decoded['sub']);
        $request = $request->withAttribute('user_role', $decoded['role']);

        return $handler->handle($request);
    }

    private function unauthorized(string $message): Response
    {
        $response = new SlimResponse();
        $response->getBody()->write(json_encode(['error' => $message]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
    }

    private function decodeToken(string $token, string $secret): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new Exception('Invalid token format');
        }

        [$header, $payload, $signature] = $parts;
        $expectedSignature = base64_encode(hash_hmac('sha256', $header . '.' . $payload, $secret, true));

        if (!hash_equals($expectedSignature, $signature)) {
            throw new Exception('Invalid token signature');
        }

        $decodedPayload = json_decode(base64_decode($payload), true);
        if (!is_array($decodedPayload)) {
            throw new Exception('Invalid token payload');
        }

        if (isset($decodedPayload['exp']) && $decodedPayload['exp'] < time()) {
            throw new Exception('Token has expired');
        }

        return $decodedPayload;
    }
}