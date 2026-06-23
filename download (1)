<?php
declare(strict_types=1);

namespace App\Support;

use App\Database;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * Helpers shared across controllers:
 *  - Audit::record() writes an immutable audit_log row.
 *  - Http::json() writes a JSON body with the right header/status.
 */
final class Audit
{
    public static function record(
        ?int $actorId,
        ?int $patientId,
        string $action,
        string $entityType,
        ?int $entityId,
        ?string $detail
    ): void {
        $sql = 'INSERT INTO audit_log (actor_id, patient_id, action, entity_type, entity_id, detail)
                VALUES (:actor, :patient, :action, :etype, :eid, :detail)';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            ':actor'   => $actorId,
            ':patient' => $patientId,
            ':action'  => $action,
            ':etype'   => $entityType,
            ':eid'     => $entityId,
            ':detail'  => $detail,
        ]);
    }
}

final class Http
{
    /** @param mixed $data */
    public static function json(Response $response, $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_SLASHES));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
