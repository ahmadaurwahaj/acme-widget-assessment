<?php

declare(strict_types=1);

namespace Acme\Tests\Http;

use Acme\Http\Dto\ErrorResponseDto;
use Acme\Http\JsonResponse;
use JsonException;
use PHPUnit\Framework\TestCase;

final class JsonResponseTest extends TestCase
{
    public function testEncodesTheBodyWhenTheResponseIsCreated(): void
    {
        $response = new JsonResponse(200, new ErrorResponseDto('Not found.'));

        self::assertSame('{"error":"Not found."}', $response->json);
    }

    public function testAddsTheStandardHeadersAndKeepsItsOwn(): void
    {
        $response = JsonResponse::error(429, 'Too many requests.', ['Retry-After' => '30']);

        self::assertSame(
            [
                'Content-Type' => 'application/json',
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'no-store',
                'Retry-After' => '30',
            ],
            $response->headers,
        );
    }

    public function testBodyThatCannotBeEncodedFailsBeforeAnythingIsSent(): void
    {
        $this->expectException(JsonException::class);

        new JsonResponse(200, new ErrorResponseDto("\xB1\x31"));
    }
}
