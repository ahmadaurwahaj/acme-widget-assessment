<?php

declare(strict_types=1);

namespace Acme\Http\Controller;

use Acme\Domain\UnknownProductException;
use Acme\Http\Dto\BasketSummaryResponseDto;
use Acme\Http\Dto\PriceBasketRequestDto;
use Acme\Http\HttpException;
use Acme\Http\JsonResponse;
use Acme\Service\BasketService;

final readonly class BasketController
{
    public function __construct(private BasketService $basketService) {}

    public function total(string $requestBody): JsonResponse
    {
        $requestDto = PriceBasketRequestDto::fromJson($requestBody);

        try {
            $basket = $this->basketService->createBasket($requestDto->productCodes);
        } catch (UnknownProductException $e) {
            throw HttpException::unprocessable($e->getMessage());
        }

        $summaryDto = BasketSummaryResponseDto::fromBasket($basket);

        return new JsonResponse(200, $summaryDto);
    }
}
