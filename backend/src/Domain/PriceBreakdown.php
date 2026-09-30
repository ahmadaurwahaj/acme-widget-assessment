<?php

declare(strict_types=1);

namespace Acme\Domain;

use InvalidArgumentException;

final readonly class PriceBreakdown
{
    public function __construct(
        public int $subtotalInCents,
        public int $discountInCents,
        public int $deliveryInCents,
        public int $totalInCents,
    ) {
        if ($subtotalInCents < 0 || $discountInCents < 0 || $deliveryInCents < 0 || $totalInCents < 0) {
            throw new InvalidArgumentException('Prices in a breakdown cannot be negative.');
        }
        if ($discountInCents > $subtotalInCents) {
            throw new InvalidArgumentException('Discount cannot be more than the subtotal.');
        }
        if ($totalInCents !== $subtotalInCents - $discountInCents + $deliveryInCents) {
            throw new InvalidArgumentException('Total must equal subtotal minus discount plus delivery.');
        }
    }
}
