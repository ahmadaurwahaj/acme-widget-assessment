<?php

declare(strict_types=1);

namespace Acme\Repository;

use Acme\Domain\Product;
use Acme\Domain\UnknownProductException;
use InvalidArgumentException;

final class InMemoryProductRepository implements ProductRepository
{
    private array $productsByCode = [];

    public function __construct(Product ...$products)
    {
        foreach ($products as $product) {
            if (isset($this->productsByCode[$product->code])) {
                throw new InvalidArgumentException("Duplicate product code: {$product->code}");
            }

            $this->productsByCode[$product->code] = $product;
        }
    }

    public function findAll(): array
    {
        $products = array_values($this->productsByCode);

        return $products;
    }

    public function getByCode(string $code): Product
    {
        if (!isset($this->productsByCode[$code])) {
            throw new UnknownProductException($code);
        }

        $product = $this->productsByCode[$code];

        return $product;
    }
}
