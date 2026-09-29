<?php

declare(strict_types=1);

namespace Acme\Http\RateLimit;

interface RateLimiter
{
    public function hit(string $clientKey): int;
}
