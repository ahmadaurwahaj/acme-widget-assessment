<?php

declare(strict_types=1);

namespace Acme\Domain\Offer;

interface Offer
{
    public function description(): string;

    public function discount(array $lines): int;
}
