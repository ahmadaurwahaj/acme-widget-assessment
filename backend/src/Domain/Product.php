<?php

declare(strict_types=1);

namespace Acme\Domain;

use InvalidArgumentException;

final readonly class Product
{
    public function __construct(
        public string $code,
        public string $name,
        public int $priceInCents,
    ) {
        if ($code === '') {
            throw new InvalidArgumentException('Product code cannot be empty.');
        }
        if ($priceInCents < 0) {
            throw new InvalidArgumentException("Product {$code} cannot have a negative price.");
        }
    }
}
