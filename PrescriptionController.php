<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use App\Support\Http;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Drug catalogue. Powers the "Search drug..." autocomplete in the
 * admin Add-Prescription form.
 */
final class MedicationController
{
    /**
     * GET /api/admin/medications?q=met
     * Optional ?q= filters by name (server-side prepared LIKE).
     */
    public function index(Request $request, Response $response): Response
    {
        $q = trim((string) ($request->getQueryParams()['q'] ?? ''));
        $pdo = Database::connection();

        if ($q !== '') {
            $stmt = $pdo->prepare(
                'SELECT id, name, form, strength, default_unit
                 FROM medication
                 WHERE name LIKE :q
                 ORDER BY name LIMIT 20'
            );
            $stmt->execute([':q' => '%' . $q . '%']);
            $rows = $stmt->fetchAll();
        } else {
            $rows = $pdo->query(
                'SELECT id, name, form, strength, default_unit FROM medication ORDER BY name'
            )->fetchAll();
        }

        return Http::json($response, ['medications' => $rows]);
    }
}
