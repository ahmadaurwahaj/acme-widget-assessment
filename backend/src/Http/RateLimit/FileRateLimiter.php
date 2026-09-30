<?php

declare(strict_types=1);

namespace Acme\Http\RateLimit;

use Closure;
use RuntimeException;
use SplFileObject;

final readonly class FileRateLimiter implements RateLimiter
{
    /** @var Closure(): int */
    private Closure $clock;

    /** @param (Closure(): int)|null $clock */
    public function __construct(
        private string $storageDirectory,
        private int $maxRequests,
        private int $windowSeconds,
        ?Closure $clock = null,
    ) {
        $this->clock = $clock ?? time(...);
    }

    public function hit(string $clientKey): int
    {
        $now = ($this->clock)();
        $counterFile = $this->openCounterFile($clientKey);

        $counterFile->flock(LOCK_EX);

        try {
            [$windowStartedAt, $requestCount] = $this->readCounter($counterFile);

            $windowHasExpired = $now - $windowStartedAt >= $this->windowSeconds;
            if ($windowHasExpired) {
                $windowStartedAt = $now;
                $requestCount = 0;
            }

            $requestCount++;
            $this->writeCounter($counterFile, $windowStartedAt, $requestCount);

            if ($requestCount <= $this->maxRequests) {
                return 0;
            }

            $windowEndsAt = $windowStartedAt + $this->windowSeconds;
            $retryAfterSeconds = $windowEndsAt - $now;

            return $retryAfterSeconds;
        } finally {
            $counterFile->flock(LOCK_UN);
        }
    }

    private function openCounterFile(string $clientKey): SplFileObject
    {
        if (!is_dir($this->storageDirectory)) {
            @mkdir($this->storageDirectory, 0o700, true);
        }

        if (!is_dir($this->storageDirectory)) {
            throw new RuntimeException("Cannot create rate limit directory {$this->storageDirectory}");
        }

        $path = $this->storageDirectory . '/' . hash('sha256', $clientKey);
        $counterFile = new SplFileObject($path, 'c+');

        return $counterFile;
    }

    /** @return array{int, int} */
    private function readCounter(SplFileObject $counterFile): array
    {
        $counterFile->rewind();
        $counterText = (string) $counterFile->fgets();
        $counterParts = explode(':', $counterText);

        $windowStartedAt = (int) $counterParts[0];
        $requestCount = (int) ($counterParts[1] ?? 0);
        $counter = [$windowStartedAt, $requestCount];

        return $counter;
    }

    private function writeCounter(SplFileObject $counterFile, int $windowStartedAt, int $requestCount): void
    {
        $counterFile->ftruncate(0);
        $counterFile->rewind();
        $counterFile->fwrite("{$windowStartedAt}:{$requestCount}");
    }
}
