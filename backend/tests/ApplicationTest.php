<?php

declare(strict_types=1);

namespace Acme\Tests;

use Acme\Application;
use Acme\Http\JsonResponse;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class ApplicationTest extends TestCase
{
    use UsesTemporaryDirectory;

    private function applicationWith(string $storeConfigPath, LoggerInterface $logger = new NullLogger()): Application
    {
        return new Application($storeConfigPath, $this->temporaryDirectory(), requestsPerMinute: 25, logger: $logger);
    }

    /** @return iterable<string, array{array<string, string>, int}> */
    public static function rateLimitSettings(): iterable
    {
        yield 'missing' => [[], 25];
        yield 'empty' => [['RATE_LIMIT_PER_MINUTE' => ''], 25];
        yield 'not a number' => [['RATE_LIMIT_PER_MINUTE' => 'lots'], 25];
        yield 'number with text' => [['RATE_LIMIT_PER_MINUTE' => '10abc'], 25];
        yield 'zero' => [['RATE_LIMIT_PER_MINUTE' => '0'], 25];
        yield 'negative' => [['RATE_LIMIT_PER_MINUTE' => '-5'], 25];
        yield 'valid' => [['RATE_LIMIT_PER_MINUTE' => '300'], 300];
    }

    /** @param array<string, string> $environment */
    #[DataProvider('rateLimitSettings')]
    public function testReadsRateLimitFromEnvironment(array $environment, int $expectedRequestsPerMinute): void
    {
        self::assertSame($expectedRequestsPerMinute, Application::requestsPerMinute($environment));
    }

    public function testHandlesRequestFromServerVariables(): void
    {
        $application = $this->applicationWith(__DIR__ . '/../config/store.php');
        $server = [
            'REQUEST_METHOD' => 'POST',
            'REQUEST_URI' => '/api/v1/basket/total?source=test',
            'REMOTE_ADDR' => '203.0.113.20',
            'CONTENT_TYPE' => 'application/json',
        ];

        $response = $application->handle($server, '{"productCodes":["R01","R01"]}');

        self::assertSame(200, $response->status);
        self::assertStringContainsString('"totalInCents":5437', $response->json);
        $this->assertStandardHeaders($response);
    }

    public function testErrorResponsesKeepTheirOwnHeaders(): void
    {
        $application = $this->applicationWith(__DIR__ . '/../config/store.php');

        $response = $application->handle(['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/api/v1/basket/total'], '');

        self::assertSame(405, $response->status);
        self::assertSame('POST', $response->headers['Allow'] ?? null);
        $this->assertStandardHeaders($response);
    }

    public function testUnexpectedFailureReturnsGeneric500(): void
    {
        $logRecords = new TestHandler();
        $application = $this->applicationWith(__DIR__ . '/fixtures/broken-store.php', new Logger('test', [$logRecords]));

        $response = $application->handle(['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/api/v1/products'], '');

        self::assertSame(500, $response->status);
        self::assertSame('{"error":"Internal server error."}', $response->json);
        self::assertStringNotContainsString('internal detail', $response->json);
        self::assertTrue($logRecords->hasErrorThatContains('Unhandled exception'));
        $this->assertStandardHeaders($response);
    }

    private function assertStandardHeaders(JsonResponse $response): void
    {
        self::assertSame('application/json', $response->headers['Content-Type'] ?? null);
        self::assertSame('nosniff', $response->headers['X-Content-Type-Options'] ?? null);
        self::assertSame('no-store', $response->headers['Cache-Control'] ?? null);
    }
}
