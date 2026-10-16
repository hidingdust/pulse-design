<?php

declare(strict_types=1);

namespace Pulse\Service;

use Pulse\Domain\PulseRules;
use Pulse\Repository\EventRepository;
use Pulse\Repository\MetricRepository;
use Pulse\Repository\PulseRepository;
use Pulse\Repository\VisitorRepository;
use Pulse\Support\ApiException;
use Pulse\Support\Http;

final class PulseService
{
    public function __construct(
        private readonly PulseRepository $pulseRepository,
        private readonly MetricRepository $metricRepository,
        private readonly VisitorRepository $visitorRepository,
        private readonly EventRepository $eventRepository,
        private readonly DemoService $demoService
    ) {
    }

    public function snapshot(string $sessionKey): array
    {
        $visitor = $this->visitorRepository->ensure($sessionKey);
        $this->pulseRepository->touchVisit();

        return $this->buildSnapshot($visitor);
    }

    public function interact(string $action, string $sessionKey): array
    {
        if (!in_array($action, PulseRules::ACTIONS, true)) {
            throw new ApiException(400, 'ACTION_INVALID', '这个动作它还看不懂。');
        }

        $visitor = $this->visitorRepository->ensure($sessionKey);
        $cooldownSeconds = 5;

        $pdo = \Pulse\Support\Database::pdo();
        $pdo->beginTransaction();

        try {
            $visitor = $this->visitorRepository->lock((int) $visitor['id']);
            $secondsSinceLast = $this->visitorRepository->secondsSinceLastInteraction((int) $visitor['id']);

            if ($secondsSinceLast < $cooldownSeconds) {
                $remaining = max(1, $cooldownSeconds - $secondsSinceLast);
                throw new ApiException(429, 'RATE_LIMITED', "它还在回味刚才的互动，{$remaining} 秒后再试。");
            }

            $before = $this->pulseRepository->lock();
            $after = PulseRules::applyAction($before, $action);
            $after['stage'] = PulseRules::stageFromInteractions($this->pulseRepository->totalInteractions() + 1);

            $this->pulseRepository->updateState($after, date('Y-m-d H:i:s'));
            $this->visitorRepository->recordInteraction(
                (int) $visitor['id'],
                $action,
                $before,
                $after
            );
            $this->eventRepository->insert(
                'interaction',
                $this->actionMessage($action),
                'warm',
                ['action' => $action, 'visitor_id' => (int) $visitor['id']]
            );

            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }

        return $this->buildSnapshot($visitor, $action);
    }

    public function timeline(int $hours = 24): array
    {
        return [
            'hours' => $hours,
            'points' => $this->metricRepository->timeline($hours),
        ];
    }

    public function report(string $sessionKey): array
    {
        $visitor = $this->visitorRepository->ensure($sessionKey);
        $state = $this->pulseRepository->get();
        $metrics = $this->metricRepository->latest() ?? [];
        $totalInteractions = $this->pulseRepository->totalInteractions();
        $stage = PulseRules::stageFromInteractions($totalInteractions);

        return [
            'score' => PulseRules::healthScore($state, $metrics),
            'status' => $state['status'],
            'stage' => $stage,
            'stage_label' => $this->stageLabel($stage),
            'summary' => PulseRules::reportText($state, $metrics),
            'total_interactions' => $totalInteractions,
            'visitor_interactions' => (int) $visitor['interaction_count'],
            'metrics' => $metrics,
            'state' => $state,
        ];
    }

    public function eventsSince(int $lastId): array
    {
        return [
            'events' => $this->eventRepository->since($lastId),
        ];
    }

    public function demoScenarios(): array
    {
        return [
            'active' => $this->demoService->active(),
            'scenarios' => $this->demoService->scenarios(),
        ];
    }

    public function setDemoScenario(string $scenario, int $ttlSeconds): array
    {
        $active = $this->demoService->set($scenario, $ttlSeconds);
        $this->eventRepository->insert(
            'demo',
            '演示模式切换为：' . $active['label'],
            'warning',
            ['scenario' => $scenario]
        );

        return [
            'active' => $active,
            'scenarios' => $this->demoService->scenarios(),
        ];
    }

    public function clearDemoScenario(): array
    {
        $this->demoService->clear();
        $this->eventRepository->insert('demo', '演示模式已结束。', 'info');

        return $this->demoScenarios();
    }

    private function buildSnapshot(array $visitor, ?string $animation = null): array
    {
        $state = $this->pulseRepository->get();
        $metrics = $this->metricRepository->latest();
        $onlineCount = $this->visitorRepository->countOnline(2);

        return [
            'state' => $state,
            'metrics' => $metrics,
            'online_count' => $onlineCount,
            'events' => $this->eventRepository->recent(12),
            'demo' => $this->demoService->active(),
            'csrf_token' => Http::csrfToken(),
            'server_time' => date(DATE_ATOM),
            'visitor' => [
                'id' => (int) $visitor['id'],
                'interaction_count' => (int) $visitor['interaction_count'] + ($animation === null ? 0 : 1),
            ],
            'animation' => $animation,
            'cooldown_seconds' => 5,
        ];
    }

    private function actionMessage(string $action): string
    {
        return match ($action) {
            PulseRules::ACTION_FEED => '有人给它送来一点能量，它安静地靠近了。',
            PulseRules::ACTION_PET => '有人轻轻摸了摸它，它记住了这份信任。',
            PulseRules::ACTION_COOL => '一阵凉意落下，它的呼吸慢慢平稳。',
            PulseRules::ACTION_PLAY => '有人逗了逗它，它亮了一瞬。',
            default => '它感受到了来访者的存在。',
        };
    }

    private function stageLabel(string $stage): string
    {
        return match ($stage) {
            'guardian' => '守护体',
            'bloom' => '成体',
            'sprout' => '幼体',
            default => '种子',
        };
    }
}

