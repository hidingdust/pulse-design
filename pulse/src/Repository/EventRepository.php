<?php

declare(strict_types=1);

namespace Pulse\Repository;

use PDO;
use Pulse\Support\Database;

final class EventRepository
{
    public function __construct(private readonly ?PDO $pdo = null)
    {
    }

    public function insert(
        string $type,
        string $message,
        string $severity = 'info',
        array $metadata = []
    ): int {
        $statement = $this->pdo()->prepare(
            'INSERT INTO events (type, message, severity, metadata, created_at)
             VALUES (:type, :message, :severity, :metadata, NOW())'
        );
        $statement->execute([
            ':type' => $type,
            ':message' => $message,
            ':severity' => $severity,
            ':metadata' => json_encode($metadata, JSON_UNESCAPED_UNICODE),
        ]);

        return (int) $this->pdo()->lastInsertId();
    }

    public function recent(int $limit = 12): array
    {
        $limit = max(1, min(50, $limit));
        $rows = $this->pdo()->query(
            "SELECT id, type, message, severity, metadata, created_at
             FROM events
             ORDER BY id DESC
             LIMIT {$limit}"
        )->fetchAll();

        return array_map(fn (array $row): array => $this->normalize($row), array_reverse($rows));
    }

    public function since(int $id, int $limit = 50): array
    {
        $limit = max(1, min(100, $limit));
        $statement = $this->pdo()->prepare(
            "SELECT id, type, message, severity, metadata, created_at
             FROM events
             WHERE id > :id
             ORDER BY id ASC
             LIMIT {$limit}"
        );
        $statement->execute([':id' => max(0, $id)]);

        return array_map(fn (array $row): array => $this->normalize($row), $statement->fetchAll());
    }

    private function normalize(array $row): array
    {
        $metadata = $row['metadata'];
        $decoded = is_string($metadata) ? json_decode($metadata, true) : null;

        return [
            'id' => (int) $row['id'],
            'type' => (string) $row['type'],
            'message' => (string) $row['message'],
            'severity' => (string) $row['severity'],
            'metadata' => is_array($decoded) ? $decoded : [],
            'created_at' => (string) $row['created_at'],
        ];
    }

    private function pdo(): PDO
    {
        return $this->pdo ?? Database::pdo();
    }
}

