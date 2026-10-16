<?php

declare(strict_types=1);

require dirname(__DIR__) . '/src/autoload.php';

use Pulse\Controller\ApiController;
use Pulse\Repository\EventRepository;
use Pulse\Repository\MetricRepository;
use Pulse\Repository\PulseRepository;
use Pulse\Repository\VisitorRepository;
use Pulse\Service\DemoService;
use Pulse\Service\PulseService;
use Pulse\Support\Http;

Http::startSession();

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if (!str_starts_with($path, '/api')) {
    Http::error('NOT_FOUND', 'Not found.', 404);
}

$pulseRepository = new PulseRepository();
$metricRepository = new MetricRepository();
$visitorRepository = new VisitorRepository();
$eventRepository = new EventRepository();
$demoService = new DemoService();

$pulseService = new PulseService(
    $pulseRepository,
    $metricRepository,
    $visitorRepository,
    $eventRepository,
    $demoService
);

$controller = new ApiController($pulseService, $demoService);
$controller->handle($method, $path);

