<?php

declare(strict_types=1);

namespace Acme\Http;

use Acme\Http\Controller\BasketController;
use Acme\Http\Controller\OfferController;
use Acme\Http\Controller\ProductController;
use Acme\Http\RateLimit\RateLimiter;
use Closure;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

final readonly class Router
{
    private const string PREFIX = '/api/v1';

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
            $handler = $this->handlerFor($request);

            if ($request->method === 'POST') {
                $this->guardPostRequest($request);
            }

            return $handler($request);
        } catch (HttpException $e) {
            $this->logRejectedRequest($request, $e);

            return JsonResponse::error($e->status, $e->getMessage(), $e->headers);
        }
    }

    /** @return Closure(Request): JsonResponse */
    private function handlerFor(Request $request): Closure
    {
        $route = $this->routeOf($request->path);
        $routes = $this->routes();

        if (!isset($routes[$route])) {
            throw HttpException::notFound();
        }

        $handlersByMethod = $routes[$route];

        if (!isset($handlersByMethod[$request->method])) {
            throw HttpException::methodNotAllowed(array_keys($handlersByMethod));
        }

        return $handlersByMethod[$request->method];
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

    /** @return array<string, array<string, Closure(Request): JsonResponse>> */
    private function routes(): array
    {
        return [
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
    }

    private function routeOf(string $path): string
    {
        if (!str_starts_with($path, self::PREFIX . '/')) {
            throw HttpException::notFound();
        }

        return substr($path, strlen(self::PREFIX));
    }
}
