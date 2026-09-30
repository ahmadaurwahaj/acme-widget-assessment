<?php

declare(strict_types=1);

namespace Acme\Http;

final readonly class Request
{
    public function __construct(
        public string $method,
        public string $path,
        public string $body,
        public string $clientIp,
        public string $contentType = '',
    ) {}
}
