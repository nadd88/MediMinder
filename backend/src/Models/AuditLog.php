<?php

namespace App\Models;

use PDO;

class AuditLog
{
    public static function record(?int $actorId, ?int $patientId, string $action, ?string $detail = null): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            "INSERT INTO audit_log (actor_id, patient_id, action, detail)
             VALUES (:actor_id, :patient_id, :action, :detail)"
        );
        $stmt->execute([
            'actor_id'   => $actorId,
            'patient_id' => $patientId,
            'action'     => $action,
            'detail'     => $detail,
        ]);
    }

    public static function forPatient(int $patientId, int $limit = 20): array
    {
        $db = Database::getConnection();
        $limit = max(1, min($limit, 100)); // guard against a huge/negative LIMIT

        $stmt = $db->prepare(
            "SELECT al.id, al.action, al.detail, al.created_at,
                    u.name AS actor_name
             FROM audit_log al
             LEFT JOIN users u ON u.id = al.actor_id
             WHERE al.patient_id = :patient_id
             ORDER BY al.created_at DESC
             LIMIT $limit"
        );
        $stmt->execute(['patient_id' => $patientId]);
        return $stmt->fetchAll();
    }
}
