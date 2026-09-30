<?php

declare(strict_types=1);

use Acme\Domain\DeliveryRules;
use Acme\Domain\DeliveryTier;
use Acme\Domain\Offer\BuyOneGetSecondHalfPrice;
use Acme\Repository\InMemoryProductRepository;
use Acme\StoreConfig;

$productRepository = InMemoryProductRepository::fromFile(__DIR__ . '/products.php');

$deliveryRules = new DeliveryRules(
    new DeliveryTier(spendBelowInCents: 5000, chargeInCents: 495),
    new DeliveryTier(spendBelowInCents: 9000, chargeInCents: 295),
);

return new StoreConfig(
    $productRepository,
    $deliveryRules,
    new BuyOneGetSecondHalfPrice($productRepository->getByCode('R01')),
);
