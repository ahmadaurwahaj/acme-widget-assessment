<?php

declare(strict_types=1);

namespace Acme\Http;

use Acme\Http\Dto\ErrorResponseDto;

final readonly class JsonResponse
{
    public function __construct(
        public int $status,
        public object|array $body,
        public array $headers = [],
    ) {}

    public static function error(int $status, string $message, array $headers = []): self
    {
        $errorDto = new ErrorResponseDto($message);

        $response = new self($status, $errorDto, $headers);

        return $response;
    }
}
