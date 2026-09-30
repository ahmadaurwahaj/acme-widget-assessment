<?php

declare(strict_types=1);

namespace Acme\Http\Controller;

use Acme\Http\Dto\ProductResponseDto;
use Acme\Http\JsonResponse;
use Acme\Repository\ProductRepository;

final readonly class ProductController
{
    public function __construct(private ProductRepository $productRepository) {}

    public function list(): JsonResponse
    {
        $productDtos = [];
        foreach ($this->productRepository->findAll() as $product) {
            $productDtos[] = ProductResponseDto::fromProduct($product);
        }

        return new JsonResponse(200, $productDtos);
    }
}
