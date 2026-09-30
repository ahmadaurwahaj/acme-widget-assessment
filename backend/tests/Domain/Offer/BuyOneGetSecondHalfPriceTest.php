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
    /** @return iterable<string, array{int, int}> */
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
        $redWidget = new Product('R01', 'Red Widget', 3295);
        $offer = new BuyOneGetSecondHalfPrice($redWidget);

        $discount = $offer->discount(new BasketLine($redWidget, $quantity));

        self::assertSame($expectedDiscount, $discount);
    }

    public function testOtherProductsAreIgnored(): void
    {
        $offer = new BuyOneGetSecondHalfPrice(new Product('R01', 'Red Widget', 3295));
        $greenWidget = new Product('G01', 'Green Widget', 2495);

        $discount = $offer->discount(new BasketLine($greenWidget, 2));

        self::assertSame(0, $discount);
    }

    public function testCodeIsStableAndBasedOnTheProduct(): void
    {
        $offer = new BuyOneGetSecondHalfPrice(new Product('R01', 'Red Widget', 3295));

        self::assertSame('r01-second-half-price', $offer->code());
    }

    public function testDescriptionUsesTheProductName(): void
    {
        $offer = new BuyOneGetSecondHalfPrice(new Product('R01', 'Red Widget', 3295));

        $description = $offer->description();

        self::assertSame('Buy one Red Widget, get the second one half price', $description);
    }
}
