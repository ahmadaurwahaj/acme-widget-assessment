<?php

declare(strict_types=1);

namespace Acme\Domain;

final readonly class PriceBreakdown
{
    public function __construct(
        public int $subtotalInCents,
        public int $discountInCents,
        public int $deliveryInCents,
        public int $totalInCents,
    ) {}
}
