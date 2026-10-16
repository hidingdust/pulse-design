<?php

declare(strict_types=1);

namespace Pulse\Repository;

use PDO;
use Pulse\Support\Database;

final class VisitorRepository
{
    public function __construct(private readonly ?PDO $pdo = null)
    {
    }

    public function ensure(string $sessionKey): array
    {
        $statement = $this->pdo()->prepare(
            'INSERT INTO visitors (session_key, first_seen_at, last_seen_at)
             VALUES (:session_key, NOW(), NOW())
             ON DUPLICATE KEY UPDATE last_seen_at = NOW()'
        );
        $statement->execute([':session_key' => $sessionKey]);

        $select = $this->pdo()->prepare(
            'SELECT id, session_key, first_seen_at, last_seen_at, interaction_count
             FROM visitors
             WHERE session_key = :session_key
             LIMIT 1'
        );
        $select->execute([':session_key' => $sessionKey]);
        $row = $select->fetch();

        if ($row === false) {
            throw new \RuntimeException('Visitor row is missing.');
        }

        return [
            'id' => (int) $row['id'],
            'session_key' => (string) $row['session_key'],
            'first_seen_at' => (string) $row['first_seen_at'],
            'last_seen_at' => (string) $row['last_seen_at'],
            'interaction_count' => (int) $row['interaction_count'],
        ];
    }

    public function secondsSinceLastInteraction(int $visitorId): int
    {
        $statement = $this->pdo()->prepare(
            'SELECT COALESCE(TIMESTAMPDIFF(SECOND, MAX(created_at), NOW()), 999999)
             FROM interactions
             WHERE visitor_id = :visitor_id'
        );
        $statement->execute([':visitor_id' => $visitorId]);

        return max(0, (int) $statement->fetchColumn());
    }

    public function lock(int $visitorId): array
    {
        $statement = $this->pdo()->prepare(
            'SELECT id, session_key, first_seen_at, last_seen_at, interaction_count
             FROM visitors
             WHERE id = :visitor_id
             FOR UPDATE'
        );
        $statement->execute([':visitor_id' => $visitorId]);
        $row = $statement->fetch();

        if ($row === false) {
            throw new \RuntimeException('Visitor row is missing.');
        }

        return [
            'id' => (int) $row['id'],
            'session_key' => (string) $row['session_key'],
            'first_seen_at' => (string) $row['first_seen_at'],
            'last_seen_at' => (string) $row['last_seen_at'],
            'interaction_count' => (int) $row['interaction_count'],
        ];
    }

    public function recordInteraction(
        int $visitorId,
        string $action,
        array $beforeState,
        array $afterState
    ): void {
        $statement = $this->pdo()->prepare(
            'INSERT INTO interactions
                (visitor_id, action, before_state, after_state, created_at)
             VALUES
                (:visitor_id, :action, :before_state, :after_state, NOW())'
        );

        $statement->execute([
            ':visitor_id' => $visitorId,
            ':action' => $action,
            ':before_state' => json_encode($beforeState, JSON_UNESCAPED_UNICODE),
            ':after_state' => json_encode($afterState, JSON_UNESCAPED_UNICODE),
        ]);

        $update = $this->pdo()->prepare(
            'UPDATE visitors
             SET last_seen_at = NOW(),
                 interaction_count = interaction_count + 1
             WHERE id = :visitor_id'
        );
        $update->execute([':visitor_id' => $visitorId]);
    }

    public function countOnline(int $minutes = 2): int
    {
        $minutes = max(1, min(60, $minutes));

        return (int) $this->pdo()->query(
            "SELECT COUNT(*)
             FROM visitors
             WHERE last_seen_at >= DATE_SUB(NOW(), INTERVAL {$minutes} MINUTE)"
        )->fetchColumn();
    }

    private function pdo(): PDO
    {
        return $this->pdo ?? Database::pdo();
    }
}

