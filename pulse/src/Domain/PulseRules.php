<?php

declare(strict_types=1);

namespace Pulse\Domain;

use InvalidArgumentException;

final class PulseRules
{
    public const ACTION_FEED = 'feed';
    public const ACTION_PET = 'pet';
    public const ACTION_COOL = 'cool';
    public const ACTION_PLAY = 'play';

    public const ACTIONS = [
        self::ACTION_FEED,
        self::ACTION_PET,
        self::ACTION_COOL,
        self::ACTION_PLAY,
    ];

    public const STATUS_CALM = 'calm';
    public const STATUS_IRRITATED = 'irritated';
    public const STATUS_SLUGGISH = 'sluggish';
    public const STATUS_ANXIOUS = 'anxious';
    public const STATUS_EXCITED = 'excited';
    public const STATUS_SLEEPY = 'sleepy';

    public static function statusFromMetrics(array $metrics, int $secondsSinceVisit): string
    {
        $cpuLoad = (float) ($metrics['cpu_load'] ?? 0);
        $memoryUsed = (float) ($metrics['memory_used_pct'] ?? 0);
        $diskUsed = (float) ($metrics['disk_used_pct'] ?? 0);
        $onlineCount = (int) ($metrics['online_count'] ?? 0);

        if ($cpuLoad >= 4.0) {
            return self::STATUS_IRRITATED;
        }

        if ($memoryUsed >= 85.0) {
            return self::STATUS_SLUGGISH;
        }

        if ($diskUsed >= 90.0) {
            return self::STATUS_ANXIOUS;
        }

        if ($onlineCount >= 10) {
            return self::STATUS_EXCITED;
        }

        if ($secondsSinceVisit >= 900) {
            return self::STATUS_SLEEPY;
        }

        return self::STATUS_CALM;
    }

    public static function applyAction(array $state, string $action): array
    {
        if (!in_array($action, self::ACTIONS, true)) {
            throw new InvalidArgumentException('Unknown pulse action.');
        }

        $next = [
            'mood' => (int) ($state['mood'] ?? 70),
            'energy' => (int) ($state['energy'] ?? 70),
            'trust' => (int) ($state['trust'] ?? 50),
            'status' => (string) ($state['status'] ?? self::STATUS_CALM),
        ];

        switch ($action) {
            case self::ACTION_FEED:
                $next['energy'] += 14;
                $next['mood'] += 2;
                $next['trust'] += 1;
                break;

            case self::ACTION_PET:
                $next['trust'] += 12;
                $next['mood'] += 8;
                $next['energy'] -= 2;
                break;

            case self::ACTION_COOL:
                if ($next['status'] === self::STATUS_IRRITATED) {
                    $next['status'] = self::STATUS_CALM;
                }
                $next['mood'] += 4;
                $next['energy'] -= 3;
                break;

            case self::ACTION_PLAY:
                $next['mood'] += 12;
                $next['energy'] -= 8;
                $next['trust'] += 3;
                break;
        }

        $next['mood'] = self::clamp($next['mood']);
        $next['energy'] = self::clamp($next['energy']);
        $next['trust'] = self::clamp($next['trust']);

        return $next;
    }

    public static function applyDecay(array $state, int $secondsSinceInteraction): array
    {
        $next = $state;

        if ($secondsSinceInteraction < 300) {
            return $next;
        }

        $ticks = (int) floor(($secondsSinceInteraction - 300) / 300);

        if ($ticks <= 0) {
            return $next;
        }

        $next['energy'] = max(15, self::clamp((int) ($next['energy'] ?? 70) - $ticks));
        $next['mood'] = max(15, self::clamp((int) ($next['mood'] ?? 70) - $ticks));
        $next['trust'] = max(10, self::clamp((int) ($next['trust'] ?? 50) - max(0, $ticks - 5)));

        return $next;
    }

    public static function stageFromInteractions(int $interactionCount): string
    {
        if ($interactionCount >= 500) {
            return 'guardian';
        }

        if ($interactionCount >= 200) {
            return 'bloom';
        }

        if ($interactionCount >= 50) {
            return 'sprout';
        }

        return 'seed';
    }

    public static function healthScore(array $state, array $metrics): int
    {
        $mood = (int) ($state['mood'] ?? 0);
        $energy = (int) ($state['energy'] ?? 0);
        $trust = (int) ($state['trust'] ?? 0);
        $memoryPenalty = max(0, (int) round(((float) ($metrics['memory_used_pct'] ?? 0) - 70) * 0.35));
        $diskPenalty = max(0, (int) round(((float) ($metrics['disk_used_pct'] ?? 0) - 75) * 0.45));

        $score = (int) round(($mood * 0.4) + ($energy * 0.3) + ($trust * 0.3));
        $score -= $memoryPenalty + $diskPenalty;

        return self::clamp($score);
    }

    public static function reportText(array $state, array $metrics): string
    {
        $status = (string) ($state['status'] ?? self::STATUS_CALM);
        $diskUsed = (float) ($metrics['disk_used_pct'] ?? 0);
        $memoryUsed = (float) ($metrics['memory_used_pct'] ?? 0);

        return match ($status) {
            self::STATUS_IRRITATED => '它现在有点过热，需要降温。',
            self::STATUS_SLUGGISH => '它的动作变慢了，内存压力有些高。',
            self::STATUS_ANXIOUS => $diskUsed >= 90 ? '它担心磁盘快满了，需要整理空间。' : '它有些不安，正在等你陪它。',
            self::STATUS_EXCITED => '访客变多了，它比平时更亮、更活跃。',
            self::STATUS_SLEEPY => '它睡着了，下一次访问会把它轻轻唤醒。',
            default => $memoryUsed >= 70 ? '它状态平稳，但内存压力值得留意。' : '它现在很平静，呼吸节奏稳定。',
        };
    }

    private static function clamp(int $value): int
    {
        return max(0, min(100, $value));
    }
}

