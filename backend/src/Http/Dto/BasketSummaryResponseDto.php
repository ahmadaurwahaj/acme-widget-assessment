<?php

declare(strict_types=1);

namespace Acme\Http\Dto;

use Acme\Domain\Basket;

final readonly class BasketSummaryResponseDto
{
    /** @param list<BasketLineResponseDto> $lines */
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

        $priceBreakdown = $basket->priceBreakdown();

        return new self(
            lines: $lineDtos,
            subtotalInCents: $priceBreakdown->subtotalInCents,
            discountInCents: $priceBreakdown->discountInCents,
            deliveryInCents: $priceBreakdown->deliveryInCents,
            totalInCents: $priceBreakdown->totalInCents,
        );
    }
}
