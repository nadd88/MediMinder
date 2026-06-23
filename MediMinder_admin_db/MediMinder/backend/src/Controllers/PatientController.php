<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use App\Support\Audit;
use App\Support\Http;
use App\Support\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Admin patient management.
 * All routes here are protected by AuthMiddleware + RoleMiddleware(['Admin']).
 */
final class PatientController
{
    /** GET /api/admin/patients */
    public function index(Request $request, Response $response): Response
    {
        $sql = "SELECT u.id, u.name, u.email, u.dob,
                       TIMESTAMPDIFF(YEAR, u.dob, CURDATE()) AS age,
                       (SELECT COUNT(*) FROM prescription p WHERE p.patient_id = u.id) AS prescription_count
                FROM users u
                WHERE u.role = 'Patient'
                ORDER BY u.name";
        $rows = Database::connection()->query($sql)->fetchAll();

        return Http::json($response, ['patients' => $rows]);
    }

    /** GET /api/admin/patients/{id} */
    public function show(Request $request, Response $response, array $args): Response
    {
        $id = (int) $args['id'];

        $stmt = Database::connection()->prepare(
            "SELECT id, name, email, dob, TIMESTAMPDIFF(YEAR, dob, CURDATE()) AS age
             FROM users WHERE id = :id AND role = 'Patient' LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        $patient = $stmt->fetch();

        if (!$patient) {
            return Http::json($response, ['error' => 'Patient not found.'], 404);
        }

        return Http::json($response, ['patient' => $patient]);
    }

    /**
     * POST /api/admin/patients
     * Body: { name, email, password, dob? }
     * Creates a new Patient user.
     */
    public function store(Request $request, Response $response): Response
    {
        $auth = $request->getAttribute('auth');
        $body = (array) $request->getParsedBody();

        $v = new Validator($body);
        $v->required('name', 'Name')
          ->required('email', 'Email')->email('email')
          ->required('password', 'Password')
          ->date('dob');
        if ($v->fails()) {
            return Http::json($response, ['errors' => $v->errors()], 422);
        }

        $pdo = Database::connection();

        // Reject duplicate emails up front for a clean message.
        $check = $pdo->prepare('SELECT id FROM users WHERE email = :e LIMIT 1');
        $check->execute([':e' => trim((string) $body['email'])]);
        if ($check->fetch()) {
            return Http::json($response, ['errors' => ['email' => 'That email is already registered.']], 422);
        }

        $hash = password_hash((string) $body['password'], PASSWORD_BCRYPT);

        $stmt = $pdo->prepare(
            "INSERT INTO users (name, email, password_hash, role, dob)
             VALUES (:name, :email, :hash, 'Patient', :dob)"
        );
        $stmt->execute([
            ':name'  => trim((string) $body['name']),
            ':email' => trim((string) $body['email']),
            ':hash'  => $hash,
            ':dob'   => !empty($body['dob']) ? $body['dob'] : null,
        ]);

        $newId = (int) $pdo->lastInsertId();

        Audit::record((int) $auth['id'], $newId, 'patient_created', 'users', $newId,
            'New patient account created: ' . trim((string) $body['name']));

        return Http::json($response, [
            'patient' => ['id' => $newId, 'name' => $body['name'], 'email' => $body['email']],
        ], 201);
    }
}
