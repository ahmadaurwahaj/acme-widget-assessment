<?php

declare(strict_types=1);

namespace Acme;

use Acme\Http\Controller\BasketController;
use Acme\Http\Controller\OfferController;
use Acme\Http\Controller\ProductController;
use Acme\Http\JsonResponse;
use Acme\Http\RateLimit\FileRateLimiter;
use Acme\Http\RateLimit\RateLimiter;
use Acme\Http\Request;
use Acme\Http\Router;
use Acme\Service\BasketService;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class Application
{
    private const int DEFAULT_REQUESTS_PER_MINUTE = 25;

    private const array STANDARD_HEADERS = [
        'Content-Type' => 'application/json',
        'X-Content-Type-Options' => 'nosniff',
        'Cache-Control' => 'no-store',
    ];

    public function __construct(
        private string $storeConfigPath,
        private string $rateLimitDirectory,
        private int $requestsPerMinute,
        private LoggerInterface $logger,
    ) {}

    /** @param array<string, string> $environment */
    public static function requestsPerMinute(array $environment): int
    {
        $requestsPerMinute = filter_var(
            $environment['RATE_LIMIT_PER_MINUTE'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]],
        );

        if ($requestsPerMinute === false) {
            return self::DEFAULT_REQUESTS_PER_MINUTE;
        }

        return $requestsPerMinute;
    }

    /** @param array<mixed> $server */
    public function handle(array $server, string $requestBody): JsonResponse
    {
        try {
            $storeConfig = StoreConfig::fromFile($this->storeConfigPath);
            $rateLimiter = new FileRateLimiter($this->rateLimitDirectory, $this->requestsPerMinute, windowSeconds: 60);
            $router = self::createRouter($storeConfig, $rateLimiter, $this->logger);

            $response = $router->handle(self::requestFrom($server, $requestBody));
        } catch (Throwable $e) {
            $this->logger->error('Unhandled exception while handling request', ['exception' => $e]);
            $response = JsonResponse::error(500, 'Internal server error.');
        }

        return new JsonResponse($response->status, $response->body, [...self::STANDARD_HEADERS, ...$response->headers]);
    }

    public static function emit(JsonResponse $response): void
    {
        http_response_code($response->status);
        foreach ($response->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        echo $response->json;
    }

    public static function createRouter(StoreConfig $storeConfig, RateLimiter $rateLimiter, LoggerInterface $logger): Router
    {
        $basketService = new BasketService(
            $storeConfig->productRepository,
            $storeConfig->deliveryRules,
            ...$storeConfig->offers,
        );

        return new Router(
            new ProductController($storeConfig->productRepository),
            new BasketController($basketService),
            new OfferController(...$storeConfig->offers),
            $rateLimiter,
            $logger,
        );
    }

    public static function createLogger(): LoggerInterface
    {
        $formatter = new JsonFormatter();
        $formatter->includeStacktraces();

        $stderrHandler = new StreamHandler('php://stderr', Level::Info);
        $stderrHandler->setFormatter($formatter);

        $logger = new Logger('acme-api');
        $logger->pushHandler($stderrHandler);
        $logger->pushProcessor(new PsrLogMessageProcessor());

        return $logger;
    }

    /** @param array<mixed> $server */
    private static function requestFrom(array $server, string $requestBody): Request
    {
        $method = $server['REQUEST_METHOD'] ?? 'GET';
        $uri = $server['REQUEST_URI'] ?? '/';
        $path = parse_url(is_string($uri) ? $uri : '/', PHP_URL_PATH);
        $clientIp = $server['REMOTE_ADDR'] ?? 'unknown';
        $contentType = $server['CONTENT_TYPE'] ?? '';

        return new Request(
            method: is_string($method) ? $method : 'GET',
            path: is_string($path) ? $path : '/',
            body: $requestBody,
            clientIp: is_string($clientIp) ? $clientIp : 'unknown',
            contentType: is_string($contentType) ? $contentType : '',
        );
    }
}
