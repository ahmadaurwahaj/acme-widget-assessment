<?php

declare(strict_types=1);

namespace Acme\Http\Dto;

final readonly class ErrorResponseDto
{
    public function __construct(public string $error) {}
}
