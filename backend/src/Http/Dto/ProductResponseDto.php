<?php

declare(strict_types=1);

namespace Acme\Http\Dto;

use Acme\Domain\Product;

final readonly class ProductResponseDto
{
    public function __construct(
        public string $code,
        public string $name,
        public int $priceInCents,
    ) {}

    public static function fromProduct(Product $product): self
    {
        return new self(
            code: $product->code,
            name: $product->name,
            priceInCents: $product->priceInCents,
        );
    }
}
