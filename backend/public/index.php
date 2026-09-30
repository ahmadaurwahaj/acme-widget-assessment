<?php

declare(strict_types=1);

use Acme\Application;
use Acme\Http\JsonResponse;
use Acme\Http\RateLimit\FileRateLimiter;
use Acme\Http\Request;
use Acme\StoreConfig;

require dirname(__DIR__) . '/vendor/autoload.php';

const DEFAULT_REQUESTS_PER_MINUTE = 25;

$logger = Application::createLogger();

try {
    $storeConfig = StoreConfig::fromFile(dirname(__DIR__) . '/config/store.php');

    $requestsPerMinute = (int) getenv('RATE_LIMIT_PER_MINUTE');
    if ($requestsPerMinute <= 0) {
        $requestsPerMinute = DEFAULT_REQUESTS_PER_MINUTE;
    }

    $rateLimiter = new FileRateLimiter(
        storageDirectory: sys_get_temp_dir() . '/acme-rate-limit',
        maxRequests: $requestsPerMinute,
        windowSeconds: 60,
    );
    $router = Application::createRouter($storeConfig, $rateLimiter, $logger);

    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url(is_string($uri) ? $uri : '/', PHP_URL_PATH);
    $requestBody = file_get_contents('php://input');
    $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    $request = new Request(
        method: is_string($method) ? $method : 'GET',
        path: is_string($path) ? $path : '/',
        body: is_string($requestBody) ? $requestBody : '',
        clientIp: is_string($clientIp) ? $clientIp : 'unknown',
        contentType: is_string($contentType) ? $contentType : '',
    );
    $response = $router->handle($request);
    $responseJson = json_encode($response->body, JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    $logger->error('Unhandled exception while handling request', ['exception' => $e]);
    $response = JsonResponse::error(500, 'Internal server error.');
    $responseJson = '{"error":"Internal server error."}';
}

http_response_code($response->status);
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
foreach ($response->headers as $name => $value) {
    header("{$name}: {$value}");
}
echo $responseJson;
