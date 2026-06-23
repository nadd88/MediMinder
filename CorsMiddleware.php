<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use App\Support\Http;
use App\Support\JwtService;
use App\Support\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class AuthController
{
    /**
     * POST /api/auth/login
     * Body: { email, password }
     * Returns: { token, user: { id, name, role } }
     */
    public function login(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();

        $v = new Validator($body);
        $v->required('email', 'Email')->email('email')->required('password', 'Password');
        if ($v->fails()) {
            return Http::json($response, ['errors' => $v->errors()], 422);
        }

        $stmt = Database::connection()->prepare(
            'SELECT id, name, role, password_hash FROM users WHERE email = :email LIMIT 1'
        );
        $stmt->execute([':email' => trim((string) $body['email'])]);
        $user = $stmt->fetch();

        // Same generic message whether the email or the password is wrong,
        // so we don't leak which accounts exist.
        if (!$user || !password_verify((string) $body['password'], $user['password_hash'])) {
            return Http::json($response, ['error' => 'Invalid email or password.'], 401);
        }

        $public = [
            'id'   => (int) $user['id'],
            'name' => $user['name'],
            'role' => $user['role'],
        ];

        $token = (new JwtService())->issue($public);

        return Http::json($response, ['token' => $token, 'user' => $public]);
    }

    /**
     * GET /api/me  (protected)
     * Echoes the authenticated user's claims.
     */
    public function me(Request $request, Response $response): Response
    {
        return Http::json($response, ['user' => $request->getAttribute('auth')]);
    }
}
