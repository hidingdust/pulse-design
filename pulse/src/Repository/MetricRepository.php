<?php

declare(strict_types=1);

namespace Pulse\Repository;

use PDO;
use Pulse\Support\Database;

final class MetricRepository
{
    public function __construct(private readonly ?PDO $pdo = null)
    {
    }

    public function insert(array $metric): void
    {
        $statement = $this->pdo()->prepare(
            'INSERT INTO metric_samples
                (cpu_load, memory_used_pct, disk_used_pct, online_count, mood, energy, trust, source, created_at)
             VALUES
                (:cpu_load, :memory_used_pct, :disk_used_pct, :online_count, :mood, :energy, :trust, :source, NOW())'
        );

        $statement->execute([
            ':cpu_load' => (float) $metric['cpu_load'],
            ':memory_used_pct' => (float) $metric['memory_used_pct'],
            ':disk_used_pct' => (float) $metric['disk_used_pct'],
            ':online_count' => (int) $metric['online_count'],
            ':mood' => (int) ($metric['mood'] ?? 72),
            ':energy' => (int) ($metric['energy'] ?? 68),
            ':trust' => (int) ($metric['trust'] ?? 55),
            ':source' => (string) ($metric['source'] ?? 'real'),
        ]);
    }

    public function latest(): ?array
    {
        $row = $this->pdo()->query(
            'SELECT id, cpu_load, memory_used_pct, disk_used_pct, online_count, mood, energy, trust, source, created_at
             FROM metric_samples
             ORDER BY id DESC
             LIMIT 1'
        )->fetch();

        return $row === false ? null : $this->normalize($row);
    }

    public function timeline(int $hours = 24): array
    {
        $hours = max(1, min(168, $hours));

        $rows = $this->pdo()->query(
            "SELECT
                DATE_FORMAT(DATE_SUB(created_at, INTERVAL MOD(MINUTE(created_at), 5) MINUTE), '%Y-%m-%d %H:%i:00') AS bucket,
                AVG(cpu_load) AS cpu_load,
                AVG(memory_used_pct) AS memory_used_pct,
                AVG(disk_used_pct) AS disk_used_pct,
                AVG(online_count) AS online_count,
                AVG(mood) AS mood,
                AVG(energy) AS energy,
                AVG(trust) AS trust
             FROM metric_samples
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$hours} HOUR)
             GROUP BY bucket
             ORDER BY bucket ASC"
        )->fetchAll();

        return array_map(static fn (array $row): array => [
            'bucket' => (string) $row['bucket'],
            'cpu_load' => round((float) $row['cpu_load'], 2),
            'memory_used_pct' => round((float) $row['memory_used_pct'], 2),
            'disk_used_pct' => round((float) $row['disk_used_pct'], 2),
            'online_count' => (int) round((float) $row['online_count']),
            'mood' => (int) round((float) $row['mood']),
            'energy' => (int) round((float) $row['energy']),
            'trust' => (int) round((float) $row['trust']),
        ], $rows);
    }

    private function normalize(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'cpu_load' => round((float) $row['cpu_load'], 2),
            'memory_used_pct' => round((float) $row['memory_used_pct'], 2),
            'disk_used_pct' => round((float) $row['disk_used_pct'], 2),
            'online_count' => (int) $row['online_count'],
            'mood' => (int) $row['mood'],
            'energy' => (int) $row['energy'],
            'trust' => (int) $row['trust'],
            'source' => (string) $row['source'],
            'created_at' => (string) $row['created_at'],
        ];
    }

    private function pdo(): PDO
    {
        return $this->pdo ?? Database::pdo();
    }
}

