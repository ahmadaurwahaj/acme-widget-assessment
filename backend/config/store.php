<?php

declare(strict_types=1);

use Acme\Domain\DeliveryRules;
use Acme\Domain\DeliveryTier;
use Acme\Domain\Offer\BuyOneGetSecondHalfPrice;
use Acme\Domain\Product;
use Acme\Repository\InMemoryProductRepository;
use Acme\StoreConfig;

$redWidget = new Product('R01', 'Red Widget', 3295);
$greenWidget = new Product('G01', 'Green Widget', 2495);
$blueWidget = new Product('B01', 'Blue Widget', 795);

$storeConfig = new StoreConfig(
    productRepository: new InMemoryProductRepository($redWidget, $greenWidget, $blueWidget),
    deliveryRules: new DeliveryRules(
        new DeliveryTier(spendBelowInCents: 5000, chargeInCents: 495),
        new DeliveryTier(spendBelowInCents: 9000, chargeInCents: 295),
    ),
    offers: [
        new BuyOneGetSecondHalfPrice($redWidget),
    ],
);

return $storeConfig;
