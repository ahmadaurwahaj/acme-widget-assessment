<?php

declare(strict_types=1);

namespace Acme\Service;

use Acme\Domain\Basket;
use Acme\Domain\DeliveryRules;
use Acme\Repository\ProductRepository;

final readonly class BasketService
{
    public function __construct(
        private ProductRepository $productRepository,
        private DeliveryRules $deliveryRules,
        private array $offers,
    ) {}

    public function createBasket(array $productCodes): Basket
    {
        $basket = new Basket($this->productRepository, $this->deliveryRules, $this->offers);

        foreach ($productCodes as $productCode) {
            $basket->add($productCode);
        }

        return $basket;
    }
}
