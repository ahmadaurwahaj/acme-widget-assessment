<?php

declare(strict_types=1);

namespace Acme\Repository;

use Acme\Domain\Product;
use Acme\Domain\UnknownProductException;
use InvalidArgumentException;
use LogicException;

final class InMemoryProductRepository implements ProductRepository
{
    /** @var array<string, Product> */
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

    public static function fromFile(string $path): self
    {
        $productsFromFile = require $path;

        if (!is_array($productsFromFile)) {
            throw new LogicException("{$path} must return a list of products.");
        }

        $products = [];
        foreach ($productsFromFile as $product) {
            if (!$product instanceof Product) {
                throw new LogicException("{$path} must only contain Product objects.");
            }
            $products[] = $product;
        }

        $repository = new self(...$products);

        return $repository;
    }

    /** @return list<Product> */
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
