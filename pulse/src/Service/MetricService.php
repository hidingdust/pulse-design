<?php

declare(strict_types=1);

namespace Pulse\Service;

use Pulse\Config\Env;
use Pulse\Repository\MetricRepository;
use Pulse\Repository\PulseRepository;
use Pulse\Repository\VisitorRepository;
use Throwable;

final class MetricService
{
    public function __construct(
        private readonly MetricRepository $metricRepository,
        private readonly VisitorRepository $visitorRepository,
        private readonly PulseRepository $pulseRepository,
        private readonly DemoService $demoService
    ) {
    }

    public function collect(): array
    {
        $secondsSinceVisit = $this->pulseRepository->secondsSinceVisit();
        $mode = Env::get('PULSE_METRIC_MODE', 'auto');

        if ($mode === 'simulated') {
            $metrics = $this->simulatedMetrics();
            $source = 'demo';
        } else {
            try {
                $metrics = $this->realMetrics();
                $source = 'real';
            } catch (Throwable $exception) {
                error_log('PULSE metric collection fallback: ' . $exception->getMessage());
                $metrics = $this->simulatedMetrics();
                $source = 'demo';
            }
        }

        $applied = $this->demoService->apply($metrics, $secondsSinceVisit);
        $metrics = $applied['metrics'];
        $state = $this->pulseRepository->get();
        $metrics['mood'] = (int) $state['mood'];
        $metrics['energy'] = (int) $state['energy'];
        $metrics['trust'] = (int) $state['trust'];
        $metrics['source'] = $source;

        if ($applied['demo'] !== null) {
            $metrics['source'] = 'demo';
        }

        return [
            'metrics' => $metrics,
            'source' => $metrics['source'],
            'seconds_since_visit' => $applied['seconds_since_visit'],
            'demo' => $applied['demo'],
        ];
    }

    public function persist(array $metrics): void
    {
        $this->metricRepository->insert($metrics);
    }

    public function latest(): ?array
    {
        return $this->metricRepository->latest();
    }

    private function realMetrics(): array
    {
        $load = sys_getloadavg();
        $cpuLoad = is_array($load) && isset($load[0]) ? (float) $load[0] : 0.5;

        return [
            'cpu_load' => round($cpuLoad, 2),
            'memory_used_pct' => $this->memoryUsedPercent(),
            'disk_used_pct' => $this->diskUsedPercent(),
            'online_count' => $this->visitorRepository->countOnline(2),
        ];
    }

    private function simulatedMetrics(): array
    {
        return [
            'cpu_load' => 0.72,
            'memory_used_pct' => 46.5,
            'disk_used_pct' => 28.5,
            'online_count' => max(1, $this->visitorRepository->countOnline(2)),
        ];
    }

    private function memoryUsedPercent(): float
    {
        $path = '/proc/meminfo';

        if (!is_readable($path)) {
            return 42.0;
        }

        $content = file_get_contents($path);

        if ($content === false) {
            return 42.0;
        }

        preg_match('/MemTotal:\s+(\d+)/', $content, $totalMatch);
        preg_match('/MemAvailable:\s+(\d+)/', $content, $availableMatch);

        if (!isset($totalMatch[1], $availableMatch[1]) || (int) $totalMatch[1] === 0) {
            return 42.0;
        }

        $total = (float) $totalMatch[1];
        $available = (float) $availableMatch[1];

        return round((1 - ($available / $total)) * 100, 2);
    }

    private function diskUsedPercent(): float
    {
        $path = '/srv';
        $free = @disk_free_space($path);
        $total = @disk_total_space($path);

        if ($free === false || $total === false || $total <= 0) {
            return 28.0;
        }

        return round((1 - ($free / $total)) * 100, 2);
    }
}

