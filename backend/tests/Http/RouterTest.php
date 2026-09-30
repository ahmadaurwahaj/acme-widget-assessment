<?php

declare(strict_types=1);

namespace Acme\Tests\Http;

use Acme\Application;
use Acme\Http\JsonResponse;
use Acme\Http\RateLimit\FileRateLimiter;
use Acme\Http\Request;
use Acme\Http\Router;
use Acme\StoreConfig;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $storeConfig = StoreConfig::fromFile(__DIR__ . '/../../config/store.php');
        $rateLimiter = new FileRateLimiter(sys_get_temp_dir() . '/acme-rate-limit-test-' . bin2hex(random_bytes(4)), 1000, 60);
        $this->router = Application::createRouter($storeConfig, $rateLimiter, new NullLogger());
    }

    private function send(string $method, string $path, string $requestBody = ''): JsonResponse
    {
        $request = new Request($method, $path, $requestBody, clientIp: '203.0.113.7');

        $response = $this->router->handle($request);

        return $response;
    }

    private function responseBodyOf(JsonResponse $response): array
    {
        $responseJson = json_encode($response->body, JSON_THROW_ON_ERROR);
        $responseBody = json_decode($responseJson, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($responseBody);

        return $responseBody;
    }

    public function testListsProducts(): void
    {
        $response = $this->send('GET', '/api/v1/products');

        self::assertSame(200, $response->status);
        self::assertSame(
            ['code' => 'R01', 'name' => 'Red Widget', 'priceInCents' => 3295],
            $this->responseBodyOf($response)[0],
        );
    }

    public function testListsOffers(): void
    {
        $response = $this->send('GET', '/api/v1/offers');

        self::assertSame(200, $response->status);
        self::assertSame(
            [['description' => 'Buy one Red Widget, get the second one half price']],
            $this->responseBodyOf($response),
        );
    }

    public function testPricesBasketFromProductCodes(): void
    {
        $response = $this->send('POST', '/api/v1/basket/total', '{"productCodes":["R01","R01"]}');
        $responseBody = $this->responseBodyOf($response);

        self::assertSame(200, $response->status);
        self::assertSame(6590, $responseBody['subtotalInCents']);
        self::assertSame(1648, $responseBody['discountInCents']);
        self::assertSame(495, $responseBody['deliveryInCents']);
        self::assertSame(5437, $responseBody['totalInCents']);
    }

    public function testClientSuppliedAmountsAreIgnored(): void
    {
        $tamperedRequest = '{"productCodes":["R01"],"totalInCents":1,"priceInCents":0,"discountInCents":99999}';

        $response = $this->send('POST', '/api/v1/basket/total', $tamperedRequest);

        self::assertSame(200, $response->status);
        self::assertSame(3790, $this->responseBodyOf($response)['totalInCents']);
    }

    public static function invalidBasketRequests(): iterable
    {
        yield 'malformed JSON' => ['{"productCodes":', 400];
        yield 'empty body' => ['', 400];
        yield 'missing productCodes' => ['{}', 422];
        yield 'productCodes is a string' => ['{"productCodes":"R01"}', 422];
        yield 'productCodes is an object' => ['{"productCodes":{"a":"R01"}}', 422];
        yield 'non-string code' => ['{"productCodes":[1]}', 422];
        yield 'nested object as code' => ['{"productCodes":[{"code":"R01"}]}', 422];
        yield 'unknown product code' => ['{"productCodes":["X99"]}', 422];
        yield 'oversized product code' => [json_encode(['productCodes' => [str_repeat('A', 10_000)]], JSON_THROW_ON_ERROR), 422];
        yield 'code with symbols' => ['{"productCodes":["R01; DROP TABLE"]}', 422];
        yield 'too many items' => [json_encode(['productCodes' => array_fill(0, 101, 'B01')], JSON_THROW_ON_ERROR), 422];
    }

    #[DataProvider('invalidBasketRequests')]
    public function testRejectsInvalidBasketRequests(string $requestBody, int $expectedStatus): void
    {
        $response = $this->send('POST', '/api/v1/basket/total', $requestBody);

        self::assertSame($expectedStatus, $response->status);
        self::assertArrayHasKey('error', $this->responseBodyOf($response));
    }

    public static function unroutableRequests(): iterable
    {
        yield 'wrong method' => ['GET', '/api/v1/basket/total', 405];
        yield 'unknown route' => ['GET', '/api/v1/orders', 404];
        yield 'unversioned path' => ['GET', '/api/products', 404];
    }

    #[DataProvider('unroutableRequests')]
    public function testUnroutableRequests(string $method, string $path, int $expectedStatus): void
    {
        $response = $this->send($method, $path);

        self::assertSame($expectedStatus, $response->status);
    }

    public function testMethodNotAllowedListsAllowedMethods(): void
    {
        $response = $this->send('GET', '/api/v1/basket/total');

        self::assertSame(405, $response->status);
        self::assertSame('POST', $response->headers['Allow'] ?? null);
    }

    public function testRateLimitedClientGets429WithRetryAfter(): void
    {
        $storeConfig = StoreConfig::fromFile(__DIR__ . '/../../config/store.php');
        $rateLimiter = new FileRateLimiter(sys_get_temp_dir() . '/acme-rate-limit-test-' . bin2hex(random_bytes(4)), 1, 60);
        $logRecords = new TestHandler();
        $router = Application::createRouter($storeConfig, $rateLimiter, new Logger('test', [$logRecords]));
        $request = new Request('GET', '/api/v1/products', '', clientIp: '203.0.113.9');

        $router->handle($request);
        $response = $router->handle($request);

        self::assertSame(429, $response->status);
        self::assertArrayHasKey('Retry-After', $response->headers);
        self::assertTrue($logRecords->hasWarningThatContains('Rejected {method} {path} with {status}'));
    }
}
