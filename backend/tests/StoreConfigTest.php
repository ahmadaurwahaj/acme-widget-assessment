<?php

declare(strict_types=1);

namespace Acme\Tests;

use Acme\Domain\DeliveryRules;
use Acme\Domain\Offer\BuyOneGetSecondHalfPrice;
use Acme\Domain\Product;
use Acme\Repository\InMemoryProductRepository;
use Acme\StoreConfig;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class StoreConfigTest extends TestCase
{
    public function testRejectsTwoOffersWithTheSameCode(): void
    {
        $redWidget = new Product('R01', 'Red Widget', 3295);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Duplicate offer code: r01-second-half-price');

        new StoreConfig(
            new InMemoryProductRepository($redWidget),
            new DeliveryRules(),
            new BuyOneGetSecondHalfPrice($redWidget),
            new BuyOneGetSecondHalfPrice($redWidget),
        );
    }
}
