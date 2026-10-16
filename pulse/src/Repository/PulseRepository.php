<?php

declare(strict_types=1);

namespace Pulse\Repository;

use PDO;
use Pulse\Support\Database;

final class PulseRepository
{
    public function __construct(private readonly ?PDO $pdo = null)
    {
    }

    public function get(): array
    {
        $row = $this->pdo()->query(
            'SELECT id, mood, energy, trust, status, stage, last_interaction_at, last_visit_at, updated_at
             FROM pulse_state
             WHERE id = 1
             LIMIT 1'
        )->fetch();

        if ($row === false) {
            $this->pdo()->exec(
                "INSERT INTO pulse_state
                    (id, mood, energy, trust, status, stage, last_interaction_at, last_visit_at)
                 VALUES
                    (1, 72, 68, 55, 'calm', 'sprout', NOW(), NOW())"
            );

            return $this->get();
        }

        return $this->normalize($row);
    }

    public function lock(): array
    {
        $row = $this->pdo()->query(
            'SELECT id, mood, energy, trust, status, stage, last_interaction_at, last_visit_at, updated_at
             FROM pulse_state
             WHERE id = 1
             FOR UPDATE'
        )->fetch();

        if ($row === false) {
            throw new \RuntimeException('Pulse state row is missing.');
        }

        return $this->normalize($row);
    }

    public function updateState(array $state, ?string $lastInteractionAt = null): void
    {
        $statement = $this->pdo()->prepare(
            'UPDATE pulse_state
             SET mood = :mood,
                 energy = :energy,
                 trust = :trust,
                 status = :status,
                 stage = :stage,
                 last_interaction_at = COALESCE(:last_interaction_at, last_interaction_at),
                 updated_at = NOW()
             WHERE id = 1'
        );

        $statement->execute([
            ':mood' => (int) $state['mood'],
            ':energy' => (int) $state['energy'],
            ':trust' => (int) $state['trust'],
            ':status' => (string) $state['status'],
            ':stage' => (string) $state['stage'],
            ':last_interaction_at' => $lastInteractionAt,
        ]);
    }

    public function touchVisit(): void
    {
        $this->pdo()->exec('UPDATE pulse_state SET last_visit_at = NOW() WHERE id = 1');
    }

    public function secondsSinceVisit(): int
    {
        $value = $this->pdo()->query(
            'SELECT COALESCE(TIMESTAMPDIFF(SECOND, last_visit_at, NOW()), 0)
             FROM pulse_state
             WHERE id = 1'
        )->fetchColumn();

        return max(0, (int) $value);
    }

    public function secondsSinceInteraction(): int
    {
        $value = $this->pdo()->query(
            'SELECT CASE
                        WHEN last_interaction_at IS NULL THEN 999999
                        ELSE COALESCE(TIMESTAMPDIFF(SECOND, last_interaction_at, NOW()), 0)
                    END
             FROM pulse_state
             WHERE id = 1'
        )->fetchColumn();

        return max(0, (int) $value);
    }

    public function totalInteractions(): int
    {
        return (int) $this->pdo()->query('SELECT COUNT(*) FROM interactions')->fetchColumn();
    }

    private function normalize(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'mood' => (int) $row['mood'],
            'energy' => (int) $row['energy'],
            'trust' => (int) $row['trust'],
            'status' => (string) $row['status'],
            'stage' => (string) $row['stage'],
            'last_interaction_at' => $row['last_interaction_at'],
            'last_visit_at' => $row['last_visit_at'],
            'updated_at' => $row['updated_at'],
        ];
    }

    private function pdo(): PDO
    {
        return $this->pdo ?? Database::pdo();
    }
}

