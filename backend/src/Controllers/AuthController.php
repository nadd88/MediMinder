<?php

namespace App\Controllers;

use App\Models\User;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class AuthController
{
    public function register(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        // --- Basic input validation ---
        $required = ['name', 'email', 'password', 'role'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $response->getBody()->write(json_encode(['error' => "Field '$field' is required"]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
        }

        $name = trim($data['name']);
        $email = trim(strtolower($data['email']));
        $password = $data['password'];
        $role = $data['role'];
        $dob = $data['dob'] ?? null;

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response->getBody()->write(json_encode(['error' => 'Invalid email format']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Validate role matches your ENUM
        if (!in_array($role, ['Patient', 'Caregiver', 'Admin'])) {
            $response->getBody()->write(json_encode(['error' => 'Invalid role']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Minimum password length
        if (strlen($password) < 8) {
            $response->getBody()->write(json_encode(['error' => 'Password must be at least 8 characters']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        // Check if email already exists
        if (User::findByEmail($email)) {
            $response->getBody()->write(json_encode(['error' => 'Email already registered']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
        }

        // Hash password with bcrypt
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        // Insert user
        $userId = User::create($name, $email, $passwordHash, $role, $dob);

        $response->getBody()->write(json_encode([
            'message' => 'User registered successfully',
            'user_id' => $userId,
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    }

    public function login(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        if (empty($data['email']) || empty($data['password'])) {
            $response->getBody()->write(json_encode(['error' => 'Email and password are required']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $email = trim(strtolower($data['email']));
        $password = $data['password'];

        $user = User::findByEmail($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $response->getBody()->write(json_encode(['error' => 'Invalid email or password']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        // Build JWT payload
        $issuedAt = time();
        $expiresAt = $issuedAt + (60 * 60 * 24); // 24 hours
        $jwtSecret = getenv('JWT_SECRET') ?: ($_ENV['JWT_SECRET'] ?? 'dev-secret');

        $payload = [
            'iat' => $issuedAt,
            'exp' => $expiresAt,
            'sub' => (int) $user['id'],
            'role' => $user['role'],
        ];

        $jwt = self::createToken($payload, $jwtSecret);

        $response->getBody()->write(json_encode([
            'message' => 'Login successful',
            'token' => $jwt,
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
            ],
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }

    private static function createToken(array $payload, string $secret): string
    {
        $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $body = base64_encode(json_encode($payload));
        $signatureInput = $header . '.' . $body;
        $signature = hash_hmac('sha256', $signatureInput, $secret, true);
        return $header . '.' . $body . '.' . base64_encode($signature);
    }
}