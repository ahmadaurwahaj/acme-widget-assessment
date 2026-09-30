<?php

declare(strict_types=1);

namespace Acme\Http;

use RuntimeException;

final class HttpException extends RuntimeException
{
    /** @param array<string, string> $headers */
    public function __construct(
        public readonly int $status,
        string $message,
        public readonly array $headers = [],
    ) {
        parent::__construct($message);
    }

    public static function badRequest(string $message): self
    {
        return new self(400, $message);
    }

    public static function notFound(): self
    {
        return new self(404, 'Not found.');
    }

    /** @param list<string> $allowedMethods */
    public static function methodNotAllowed(array $allowedMethods): self
    {
        $headers = ['Allow' => implode(', ', $allowedMethods)];

        return new self(405, 'Method not allowed.', $headers);
    }

    public static function tooManyRequests(int $retryAfterSeconds): self
    {
        $headers = ['Retry-After' => (string) $retryAfterSeconds];

        return new self(429, 'Too many requests. Please slow down.', $headers);
    }

    public static function unsupportedMediaType(): self
    {
        return new self(415, 'Request body must be sent as application/json.');
    }

    public static function unprocessable(string $message): self
    {
        return new self(422, $message);
    }
}
