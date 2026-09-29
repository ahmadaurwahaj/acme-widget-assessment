<?php

declare(strict_types=1);

namespace Acme\Http\Dto;

use Acme\Domain\Basket;

final readonly class BasketSummaryResponseDto
{
    public function __construct(
        public array $lines,
        public int $subtotalInCents,
        public int $discountInCents,
        public int $deliveryInCents,
        public int $totalInCents,
    ) {}

    public static function fromBasket(Basket $basket): self
    {
        $lineDtos = [];
        foreach ($basket->lines() as $line) {
            $lineDtos[] = BasketLineResponseDto::fromBasketLine($line);
        }

        $summaryDto = new self(
            lines: $lineDtos,
            subtotalInCents: $basket->subtotal(),
            discountInCents: $basket->discount(),
            deliveryInCents: $basket->delivery(),
            totalInCents: $basket->total(),
        );

        return $summaryDto;
    }
}
