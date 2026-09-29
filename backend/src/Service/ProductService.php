<?php

declare(strict_types=1);

namespace Acme\Service;

use Acme\Repository\ProductRepository;

final readonly class ProductService
{
    public function __construct(private ProductRepository $productRepository) {}

    public function listProducts(): array
    {
        $products = $this->productRepository->findAll();

        return $products;
    }
}
