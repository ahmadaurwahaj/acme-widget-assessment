<?php

declare(strict_types=1);

namespace Acme\Http\Dto;

use Acme\Domain\BasketLine;

final readonly class BasketLineResponseDto
{
    public function __construct(
        public string $code,
        public string $name,
        public int $quantity,
        public int $unitPriceInCents,
        public int $lineTotalInCents,
    ) {}

    public static function fromBasketLine(BasketLine $line): self
    {
        $lineDto = new self(
            code: $line->product->code,
            name: $line->product->name,
            quantity: $line->quantity,
            unitPriceInCents: $line->product->priceInCents,
            lineTotalInCents: $line->totalInCents(),
        );

        return $lineDto;
    }
}
