<?php

declare(strict_types=1);

namespace Acme\Service;

use Acme\Domain\Product;
use Acme\Repository\ProductRepository;

final readonly class ProductService
{
    public function __construct(private ProductRepository $productRepository) {}

    /** @return list<Product> */
    public function listProducts(): array
    {
        $products = $this->productRepository->findAll();

        return $products;
    }
}
