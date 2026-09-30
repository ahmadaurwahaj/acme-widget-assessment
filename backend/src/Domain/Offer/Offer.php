<?php

declare(strict_types=1);

namespace Acme\Domain\Offer;

use Acme\Domain\BasketLine;

interface Offer
{
    public function code(): string;

    public function description(): string;

    public function discount(BasketLine ...$lines): int;
}
