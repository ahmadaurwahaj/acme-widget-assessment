<?php

declare(strict_types=1);

namespace Acme\Domain;

final readonly class BasketLine
{
    public function __construct(
        public Product $product,
        public int $quantity,
    ) {}

    public function totalInCents(): int
    {
        return $this->product->priceInCents * $this->quantity;
    }
}
