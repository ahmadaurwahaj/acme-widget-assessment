<?php

declare(strict_types=1);

namespace Acme\Http;

use Acme\Http\Dto\ErrorResponseDto;

final readonly class JsonResponse
{
    /**
     * @param object|list<object> $body
     * @param array<string, string> $headers
     */
    public function __construct(
        public int $status,
        public object|array $body,
        public array $headers = [],
    ) {}

    /** @param array<string, string> $headers */
    public static function error(int $status, string $message, array $headers = []): self
    {
        $errorDto = new ErrorResponseDto($message);

        $response = new self($status, $errorDto, $headers);

        return $response;
    }
}
