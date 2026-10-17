<?php

declare(strict_types=1);

namespace Pulse\Controller;

use Pulse\Config\Env;
use Pulse\Service\DemoService;
use Pulse\Service\PulseService;
use Pulse\Support\ApiException;
use Pulse\Support\Http;
use Throwable;

final class ApiController
{
    public function __construct(
        private readonly PulseService $pulseService,
        private readonly DemoService $demoService
    ) {
    }

    public function handle(string $method, string $path): never
    {
        try {
            $sessionKey = Http::sessionKey();

            if ($method === 'GET' && $path === '/api/health') {
                Http::success([
                    'status' => 'ok',
                    'time' => date(DATE_ATOM),
                ]);
            }

            if ($method === 'GET' && $path === '/api/state') {
                Http::success($this->pulseService->snapshot($sessionKey));
            }

            if ($method === 'POST' && $path === '/api/interactions') {
                Http::requireCsrf();
                $input = Http::input();
                $action = (string) ($input['action'] ?? '');
                Http::success($this->pulseService->interact($action, $sessionKey));
            }

            if ($method === 'GET' && $path === '/api/timeline') {
                $hours = max(1, min(168, (int) ($_GET['hours'] ?? 24)));
                Http::success($this->pulseService->timeline($hours));
            }

            if ($method === 'GET' && $path === '/api/report') {
                Http::success($this->pulseService->report($sessionKey));
            }

            if ($method === 'GET' && $path === '/api/events') {
                $lastId = max(0, (int) ($_GET['since_id'] ?? 0));
                Http::success($this->pulseService->eventsSince($lastId));
            }

            if ($method === 'GET' && $path === '/api/demo/scenarios') {
                $this->requireDemoToken(Http::input());
                Http::success($this->pulseService->demoScenarios());
            }

            if ($method === 'POST' && $path === '/api/demo/scenario') {
                Http::requireCsrf();
                $input = Http::input();
                $this->requireDemoToken($input);
                $scenario = (string) ($input['scenario'] ?? '');
                $ttlSeconds = (int) ($input['ttl_seconds'] ?? 600);

                if ($scenario === 'none') {
                    Http::success($this->pulseService->clearDemoScenario());
                }

                Http::success($this->pulseService->setDemoScenario($scenario, $ttlSeconds));
            }

            if ($method === 'POST' && $path === '/api/demo/public-scenario') {
                Http::requireCsrf();
                $input = Http::input();
                $scenario = (string) ($input['scenario'] ?? '');
                $ttlSeconds = (int) ($input['ttl_seconds'] ?? 600);

                if ($scenario === 'none') {
                    Http::success($this->pulseService->clearDemoScenario());
                }

                Http::success($this->pulseService->setDemoScenario($scenario, $ttlSeconds));
            }

            throw new ApiException(404, 'NOT_FOUND', '接口不存在。');
        } catch (ApiException $exception) {
            Http::error($exception->errorCode, $exception->getMessage(), $exception->status);
        } catch (Throwable $exception) {
            error_log('PULSE API error: ' . $exception->getMessage());
            Http::error('SERVER_ERROR', 'PULSE 暂时失去响应，稍后再试。', 500);
        }
    }

    private function requireDemoToken(array $input): void
    {
        $provided = $_SERVER['HTTP_X_DEMO_TOKEN'] ?? ($input['token'] ?? '');
        $expected = Env::get('DEMO_TOKEN', 'pulse_demo_token');

        if (!is_string($provided) || $provided === '' || !hash_equals((string) $expected, $provided)) {
            throw new ApiException(403, 'DEMO_DENIED', '演示令牌无效。');
        }
    }
}

