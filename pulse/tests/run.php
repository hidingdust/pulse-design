<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/autoload.php';

use Pulse\Domain\PulseRules;

$passed = 0;
$failed = 0;

function check(string $label, bool $condition): void
{
    global $passed, $failed;

    if ($condition) {
        $passed++;
        echo "[PASS] {$label}\n";
        return;
    }

    $failed++;
    echo "[FAIL] {$label}\n";
}

$baseMetrics = [
    'cpu_load' => 0.4,
    'memory_used_pct' => 40.0,
    'disk_used_pct' => 30.0,
    'online_count' => 1,
];

check(
    'CPU high becomes irritated',
    PulseRules::statusFromMetrics(array_merge($baseMetrics, ['cpu_load' => 8.0]), 0) === PulseRules::STATUS_IRRITATED
);
check(
    'Memory high becomes sluggish',
    PulseRules::statusFromMetrics(array_merge($baseMetrics, ['memory_used_pct' => 91.0]), 0) === PulseRules::STATUS_SLUGGISH
);
check(
    'Disk high becomes anxious',
    PulseRules::statusFromMetrics(array_merge($baseMetrics, ['disk_used_pct' => 95.0]), 0) === PulseRules::STATUS_ANXIOUS
);
check(
    'Crowded becomes excited',
    PulseRules::statusFromMetrics(array_merge($baseMetrics, ['online_count' => 18]), 0) === PulseRules::STATUS_EXCITED
);
check(
    'Long idle becomes sleepy',
    PulseRules::statusFromMetrics($baseMetrics, 1200) === PulseRules::STATUS_SLEEPY
);
check(
    'Default state is calm',
    PulseRules::statusFromMetrics($baseMetrics, 10) === PulseRules::STATUS_CALM
);

$baseState = ['mood' => 50, 'energy' => 50, 'trust' => 50, 'status' => PulseRules::STATUS_CALM];

check(
    'Feed increases energy',
    PulseRules::applyAction($baseState, PulseRules::ACTION_FEED)['energy'] > $baseState['energy']
);
check(
    'Pet increases trust',
    PulseRules::applyAction($baseState, PulseRules::ACTION_PET)['trust'] > $baseState['trust']
);
check(
    'Cool calms irritated state',
    PulseRules::applyAction(
        array_merge($baseState, ['status' => PulseRules::STATUS_IRRITATED]),
        PulseRules::ACTION_COOL
    )['status'] === PulseRules::STATUS_CALM
);
check(
    'Play increases mood',
    PulseRules::applyAction($baseState, PulseRules::ACTION_PLAY)['mood'] > $baseState['mood']
);

check('Stage seed', PulseRules::stageFromInteractions(0) === 'seed');
check('Stage sprout', PulseRules::stageFromInteractions(50) === 'sprout');
check('Stage bloom', PulseRules::stageFromInteractions(200) === 'bloom');
check('Stage guardian', PulseRules::stageFromInteractions(500) === 'guardian');

$decayed = PulseRules::applyDecay($baseState, 900);
check('Decay reduces energy', $decayed['energy'] < $baseState['energy']);

echo "\n{$passed} passed, {$failed} failed.\n";

exit($failed === 0 ? 0 : 1);

