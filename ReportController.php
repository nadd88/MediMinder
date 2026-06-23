<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use App\Support\Http;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Read-only audit log for the admin "Audit log" panel.
 * Optional ?patient_id= filters to one patient.
 */
final class AuditController
{
    /** GET /api/admin/audit?patient_id=4&limit=50 */
    public function index(Request $request, Response $response): Response
    {
        $q         = $request->getQueryParams();
        $patientId = isset($q['patient_id']) ? (int) $q['patient_id'] : null;
        $limit     = min(200, max(1, (int) ($q['limit'] ?? 50)));

        $sql = "SELECT a.id, a.action, a.entity_type, a.entity_id, a.detail, a.created_at,
                       actor.name   AS actor_name,
                       patient.name AS patient_name
                FROM audit_log a
                LEFT JOIN users actor   ON actor.id   = a.actor_id
                LEFT JOIN users patient ON patient.id = a.patient_id";

        $params = [];
        if ($patientId !== null) {
            $sql .= ' WHERE a.patient_id = :pid';
            $params[':pid'] = $patientId;
        }
        $sql .= ' ORDER BY a.created_at DESC LIMIT ' . $limit;

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        return Http::json($response, ['audit' => $stmt->fetchAll()]);
    }
}
