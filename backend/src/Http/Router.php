<?php

declare(strict_types=1);

namespace Acme\Http;

use Acme\Http\Controller\BasketController;
use Acme\Http\Controller\OfferController;
use Acme\Http\Controller\ProductController;
use Acme\Http\RateLimit\RateLimiter;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

final readonly class Router
{
    public const string PREFIX = '/api/v1';

    public function __construct(
        private ProductController $productController,
        private BasketController $basketController,
        private OfferController $offerController,
        private RateLimiter $rateLimiter,
        private LoggerInterface $logger,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        try {
            if ($request->method === 'POST') {
                $this->guardPostRequest($request);
            }

            $route = $this->routeOf($request->path);
            $routes = $this->routes();

            if (!isset($routes[$route])) {
                throw HttpException::notFound();
            }

            $handlersByMethod = $routes[$route];

            if (!isset($handlersByMethod[$request->method])) {
                $allowedMethods = array_keys($handlersByMethod);
                throw HttpException::methodNotAllowed($allowedMethods);
            }

            $handler = $handlersByMethod[$request->method];
            $response = $handler($request);
        } catch (HttpException $e) {
            $this->logRejectedRequest($request, $e);
            $response = JsonResponse::error($e->status, $e->getMessage(), $e->headers);
        }

        return $response;
    }

    private function guardPostRequest(Request $request): void
    {
        $retryAfterSeconds = $this->rateLimiter->hit($request->clientIp);
        if ($retryAfterSeconds > 0) {
            throw HttpException::tooManyRequests($retryAfterSeconds);
        }

        $mediaType = strtolower(trim(explode(';', $request->contentType)[0]));
        if ($mediaType !== 'application/json') {
            throw HttpException::unsupportedMediaType();
        }
    }

    private function logRejectedRequest(Request $request, HttpException $exception): void
    {
        $level = $exception->status === 429 ? LogLevel::WARNING : LogLevel::NOTICE;

        $this->logger->log($level, 'Rejected {method} {path} with {status}: {reason}', [
            'method' => $request->method,
            'path' => $request->path,
            'status' => $exception->status,
            'reason' => $exception->getMessage(),
            'clientHash' => substr(hash('sha256', $request->clientIp), 0, 12),
        ]);
    }

    private function routes(): array
    {
        $routes = [
            '/products' => [
                'GET' => fn(Request $request): JsonResponse => $this->productController->list(),
            ],
            '/offers' => [
                'GET' => fn(Request $request): JsonResponse => $this->offerController->list(),
            ],
            '/basket/total' => [
                'POST' => fn(Request $request): JsonResponse => $this->basketController->total($request->body),
            ],
        ];

        return $routes;
    }

    private function routeOf(string $path): string
    {
        if (!str_starts_with($path, self::PREFIX . '/')) {
            throw HttpException::notFound();
        }

        $route = substr($path, strlen(self::PREFIX));

        return $route;
    }
}
