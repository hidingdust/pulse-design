<?php

declare(strict_types=1);

namespace Pulse\Service;

use Pulse\Config\Env;

final class DemoService
{
    private const SCENARIOS = [
        'calm' => [
            'label' => '平静模式',
            'description' => '恢复常规指标。',
            'metrics' => [],
            'seconds_since_visit' => 0,
        ],
        'cpu-high' => [
            'label' => 'CPU 高负载',
            'description' => '强制负载升高，展示暴躁状态。',
            'metrics' => ['cpu_load' => 12.8],
            'seconds_since_visit' => 0,
        ],
        'memory-high' => [
            'label' => '内存高占用',
            'description' => '强制内存占用升高，展示迟钝状态。',
            'metrics' => ['memory_used_pct' => 94.0],
            'seconds_since_visit' => 0,
        ],
        'disk-full' => [
            'label' => '磁盘接近满载',
            'description' => '强制磁盘占用升高，展示焦虑状态。',
            'metrics' => ['disk_used_pct' => 96.0],
            'seconds_since_visit' => 0,
        ],
        'crowded' => [
            'label' => '访客激增',
            'description' => '强制在线人数升高，展示兴奋状态。',
            'metrics' => ['online_count' => 42],
            'seconds_since_visit' => 0,
        ],
        'lonely' => [
            'label' => '长时间无人',
            'description' => '强制最后访问时间提前，展示睡眠状态。',
            'metrics' => ['cpu_load' => 0.2, 'memory_used_pct' => 36.0, 'disk_used_pct' => 28.0, 'online_count' => 0],
            'seconds_since_visit' => 3600,
        ],
    ];

    public function scenarios(): array
    {
        return array_map(
            static fn (string $key, array $scenario): array => [
                'key' => $key,
                'label' => $scenario['label'],
                'description' => $scenario['description'],
            ],
            array_keys(self::SCENARIOS),
            self::SCENARIOS
        );
    }

    public function set(string $scenario, int $ttlSeconds = 600): array
    {
        if (!isset(self::SCENARIOS[$scenario])) {
            throw new \InvalidArgumentException('Unknown demo scenario.');
        }

        $ttlSeconds = max(30, min(3600, $ttlSeconds));
        $payload = [
            'scenario' => $scenario,
            'started_at' => time(),
            'expires_at' => time() + $ttlSeconds,
        ];

        $this->write($payload);

        return $this->active() ?? $payload;
    }

    public function clear(): void
    {
        $path = $this->path();

        if (is_file($path)) {
            @unlink($path);
        }
    }

    public function active(): ?array
    {
        $payload = $this->read();

        if ($payload === null) {
            return null;
        }

        if (($payload['expires_at'] ?? 0) <= time()) {
            $this->clear();
            return null;
        }

        $scenario = (string) ($payload['scenario'] ?? '');

        if (!isset(self::SCENARIOS[$scenario])) {
            $this->clear();
            return null;
        }

        return [
            'scenario' => $scenario,
            'label' => self::SCENARIOS[$scenario]['label'],
            'description' => self::SCENARIOS[$scenario]['description'],
            'started_at' => (int) ($payload['started_at'] ?? 0),
            'expires_at' => (int) ($payload['expires_at'] ?? 0),
        ];
    }

    public function apply(array $metrics, int $secondsSinceVisit): array
    {
        $active = $this->active();

        if ($active === null) {
            return [
                'metrics' => $metrics,
                'seconds_since_visit' => $secondsSinceVisit,
                'demo' => null,
            ];
        }

        $scenario = $active['scenario'];
        $overrides = self::SCENARIOS[$scenario]['metrics'];

        return [
            'metrics' => array_merge($metrics, $overrides),
            'seconds_since_visit' => (int) self::SCENARIOS[$scenario]['seconds_since_visit'],
            'demo' => $active,
        ];
    }

    private function read(): ?array
    {
        $path = $this->path();

        if (!is_file($path)) {
            return null;
        }

        $raw = file_get_contents($path);

        if ($raw === false || $raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function write(array $payload): void
    {
        $directory = dirname($this->path());

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $handle = fopen($this->path(), 'c+');

        if ($handle === false) {
            throw new \RuntimeException('Unable to open demo storage.');
        }

        flock($handle, LOCK_EX);
        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        fflush($handle);
        flock($handle, LOCK_UN);
        fclose($handle);
    }

    private function path(): string
    {
        return Env::get('DEMO_STORAGE_PATH', dirname(__DIR__, 2) . '/storage/demo.json');
    }
}

