<?php

declare(strict_types=1);

namespace Acme\Domain;

use InvalidArgumentException;

final readonly class DeliveryTier
{
    public function __construct(
        public int $spendBelowInCents,
        public int $chargeInCents,
    ) {
        if ($spendBelowInCents <= 0 || $chargeInCents < 0) {
            throw new InvalidArgumentException('A delivery tier needs a positive threshold and a non-negative charge.');
        }
    }
}
