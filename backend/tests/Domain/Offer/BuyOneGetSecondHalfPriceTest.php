<?php

declare(strict_types=1);

namespace Acme\Tests\Domain\Offer;

use Acme\Domain\BasketLine;
use Acme\Domain\Offer\BuyOneGetSecondHalfPrice;
use Acme\Domain\Product;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BuyOneGetSecondHalfPriceTest extends TestCase
{
    public static function quantities(): iterable
    {
        yield 'one widget, no pair' => [1, 0];
        yield 'one pair' => [2, 1648];
        yield 'odd one out pays full price' => [3, 1648];
        yield 'offer repeats per pair' => [4, 3296];
    }

    #[DataProvider('quantities')]
    public function testDiscountPerPair(int $quantity, int $expectedDiscount): void
    {
        $offer = new BuyOneGetSecondHalfPrice('R01');
        $redWidget = new Product('R01', 'Red Widget', 3295);

        $discount = $offer->discount([new BasketLine($redWidget, $quantity)]);

        self::assertSame($expectedDiscount, $discount);
    }

    public function testOtherProductsAreIgnored(): void
    {
        $offer = new BuyOneGetSecondHalfPrice('R01');
        $greenWidget = new Product('G01', 'Green Widget', 2495);

        $discount = $offer->discount([new BasketLine($greenWidget, 2)]);

        self::assertSame(0, $discount);
    }
}
