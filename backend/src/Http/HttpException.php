<?php

declare(strict_types=1);

namespace Acme\Http;

use RuntimeException;

final class HttpException extends RuntimeException
{
    public function __construct(
        public readonly int $status,
        string $message,
        public readonly array $headers = [],
    ) {
        parent::__construct($message);
    }

    public static function badRequest(string $message): self
    {
        $exception = new self(400, $message);

        return $exception;
    }

    public static function notFound(): self
    {
        $exception = new self(404, 'Not found.');

        return $exception;
    }

    public static function methodNotAllowed(array $allowedMethods): self
    {
        $headers = ['Allow' => implode(', ', $allowedMethods)];
        $exception = new self(405, 'Method not allowed.', $headers);

        return $exception;
    }

    public static function tooManyRequests(int $retryAfterSeconds): self
    {
        $headers = ['Retry-After' => (string) $retryAfterSeconds];
        $exception = new self(429, 'Too many requests. Please slow down.', $headers);

        return $exception;
    }

    public static function unsupportedMediaType(): self
    {
        $exception = new self(415, 'Request body must be sent as application/json.');

        return $exception;
    }

    public static function unprocessable(string $message): self
    {
        $exception = new self(422, $message);

        return $exception;
    }
}
