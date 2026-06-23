<?php
declare(strict_types=1);

namespace App\Support;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Issues and verifies HS256 JSON Web Tokens.
 * Claims: sub (user id), name, role, iss, iat, exp.
 */
final class JwtService
{
    private string $secret;
    private string $issuer;
    private int $ttl;

    public function __construct()
    {
        $this->secret = $_ENV['JWT_SECRET'] ?? 'insecure-dev-secret';
        $this->issuer = $_ENV['JWT_ISSUER'] ?? 'mediminder';
        $this->ttl    = (int) ($_ENV['JWT_TTL'] ?? 86400);
    }

    /** @param array{id:int,name:string,role:string} $user */
    public function issue(array $user): string
    {
        $now = time();
        $payload = [
            'iss'  => $this->issuer,
            'iat'  => $now,
            'exp'  => $now + $this->ttl,
            'sub'  => $user['id'],
            'name' => $user['name'],
            'role' => $user['role'],
        ];

        return JWT::encode($payload, $this->secret, 'HS256');
    }

    /**
     * Returns the decoded claims as an array, or null if invalid/expired.
     * @return array<string,mixed>|null
     */
    public function verify(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, 'HS256'));
            return (array) $decoded;
        } catch (\Throwable $e) {
            return null;
        }
    }
}
