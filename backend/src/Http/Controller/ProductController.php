<?php

declare(strict_types=1);

namespace Acme\Http\Controller;

use Acme\Http\Dto\ProductResponseDto;
use Acme\Http\JsonResponse;
use Acme\Service\ProductService;

final readonly class ProductController
{
    public function __construct(private ProductService $productService) {}

    public function list(): JsonResponse
    {
        $products = $this->productService->listProducts();
        $productDtos = [];
        foreach ($products as $product) {
            $productDtos[] = ProductResponseDto::fromProduct($product);
        }

        $response = new JsonResponse(200, $productDtos);

        return $response;
    }
}
