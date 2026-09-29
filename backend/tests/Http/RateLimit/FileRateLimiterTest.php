<?php

declare(strict_types=1);

namespace Acme\Tests\Http\RateLimit;

use Acme\Http\RateLimit\FileRateLimiter;
use PHPUnit\Framework\TestCase;

final class FileRateLimiterTest extends TestCase
{
    private string $storageDirectory;

    private int $now = 1_000_000;

    protected function setUp(): void
    {
        $this->storageDirectory = sys_get_temp_dir() . '/acme-rate-limiter-test-' . bin2hex(random_bytes(4));
    }

    private function limiterAllowing(int $maxRequests): FileRateLimiter
    {
        return new FileRateLimiter($this->storageDirectory, $maxRequests, windowSeconds: 60, clock: fn(): int => $this->now);
    }

    public function testAllowsRequestsUpToTheLimitThenAsksClientToWait(): void
    {
        $limiter = $this->limiterAllowing(2);

        $firstRetryAfter = $limiter->hit('client');
        $secondRetryAfter = $limiter->hit('client');
        $this->now += 15;
        $thirdRetryAfter = $limiter->hit('client');

        self::assertSame(0, $firstRetryAfter);
        self::assertSame(0, $secondRetryAfter);
        self::assertSame(45, $thirdRetryAfter, 'Over the limit 15s into a 60s window means waiting 45s.');
    }

    public function testNewWindowResetsTheCount(): void
    {
        $limiter = $this->limiterAllowing(1);
        $limiter->hit('client');
        $this->now += 60;

        $retryAfter = $limiter->hit('client');

        self::assertSame(0, $retryAfter);
    }

    public function testClientsAreLimitedIndependently(): void
    {
        $limiter = $this->limiterAllowing(1);
        $limiter->hit('first client');

        $retryAfter = $limiter->hit('second client');

        self::assertSame(0, $retryAfter);
    }
}
