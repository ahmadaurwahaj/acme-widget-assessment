<?php

declare(strict_types=1);

use Acme\Domain\DeliveryRules;
use Acme\Domain\DeliveryTier;
use Acme\Domain\Offer\BuyOneGetSecondHalfPrice;
use Acme\Domain\Product;
use Acme\Repository\InMemoryProductRepository;
use Acme\StoreConfig;

$storeConfig = new StoreConfig(
    productRepository: new InMemoryProductRepository(
        new Product('R01', 'Red Widget', 3295),
        new Product('G01', 'Green Widget', 2495),
        new Product('B01', 'Blue Widget', 795),
    ),
    deliveryRules: new DeliveryRules(
        new DeliveryTier(spendBelowInCents: 5000, chargeInCents: 495),
        new DeliveryTier(spendBelowInCents: 9000, chargeInCents: 295),
    ),
    offers: [
        new BuyOneGetSecondHalfPrice('R01'),
    ],
);

return $storeConfig;
