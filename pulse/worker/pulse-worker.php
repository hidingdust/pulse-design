<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/autoload.php';

use Pulse\Domain\PulseRules;
use Pulse\Repository\EventRepository;
use Pulse\Repository\MetricRepository;
use Pulse\Repository\PulseRepository;
use Pulse\Repository\VisitorRepository;
use Pulse\Service\DemoService;
use Pulse\Service\MetricService;

$pulseRepository = new PulseRepository();
$metricRepository = new MetricRepository();
$visitorRepository = new VisitorRepository();
$eventRepository = new EventRepository();
$demoService = new DemoService();
$metricService = new MetricService(
    $metricRepository,
    $visitorRepository,
    $pulseRepository,
    $demoService
);

$lastStatus = null;
$lastMetricLog = time();

while (true) {
    try {
        $collected = $metricService->collect();
        $metrics = $collected['metrics'];
        $metricService->persist($metrics);

        $state = $pulseRepository->get();
        $decayed = PulseRules::applyDecay($state, $pulseRepository->secondsSinceInteraction());
        $status = PulseRules::statusFromMetrics($metrics, (int) $collected['seconds_since_visit']);
        $decayed['status'] = $status;
        $decayed['stage'] = PulseRules::stageFromInteractions($pulseRepository->totalInteractions());

        $pulseRepository->updateState($decayed);

        if ($lastStatus !== null && $lastStatus !== $status) {
            $eventRepository->insert(
                'status',
                'PULSE 的状态变为：' . $status,
                $status === PulseRules::STATUS_CALM ? 'info' : 'warning',
                ['status' => $status, 'source' => $collected['source']]
            );
        }

        $lastStatus = $status;

        if (time() - $lastMetricLog >= 60) {
            $eventRepository->insert(
                'metric',
                '心跳采样完成，来源：' . $collected['source'],
                'info',
                $metrics
            );
            $lastMetricLog = time();
        }
    } catch (Throwable $exception) {
        error_log('PULSE worker error: ' . $exception->getMessage());
    }

    sleep(5);
}

