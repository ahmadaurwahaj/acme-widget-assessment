<?php

declare(strict_types=1);

namespace Acme\Http;

use Acme\Http\Dto\ErrorResponseDto;

final readonly class JsonResponse
{
    private const array STANDARD_HEADERS = [
        'Content-Type' => 'application/json',
        'X-Content-Type-Options' => 'nosniff',
        'Cache-Control' => 'no-store',
    ];

    public string $json;

    /** @var array<string, string> */
    public array $headers;

    /**
     * @param object|list<object> $body
     * @param array<string, string> $headers
     */
    public function __construct(
        public int $status,
        object|array $body,
        array $headers = [],
    ) {
        $this->json = json_encode($body, JSON_THROW_ON_ERROR);
        $this->headers = [...self::STANDARD_HEADERS, ...$headers];
    }

    /** @param array<string, string> $headers */
    public static function error(int $status, string $message, array $headers = []): self
    {
        $errorDto = new ErrorResponseDto($message);

        return new self($status, $errorDto, $headers);
    }
}
