<?php

declare(strict_types=1);

namespace Acme\Tests\Domain;

use Acme\Domain\Basket;
use Acme\Domain\UnknownProductException;
use Acme\StoreConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BasketTest extends TestCase
{
    private function newAcmeBasket(): Basket
    {
        $storeConfig = StoreConfig::fromFile(__DIR__ . '/../../config/store.php');

        $basket = new Basket($storeConfig->productRepository, $storeConfig->deliveryRules, $storeConfig->offers);

        return $basket;
    }

    public static function specExamples(): iterable
    {
        yield 'B01, G01' => [['B01', 'G01'], 3785];
        yield 'R01, R01' => [['R01', 'R01'], 5437];
        yield 'R01, G01' => [['R01', 'G01'], 6085];
        yield 'B01, B01, R01, R01, R01' => [['B01', 'B01', 'R01', 'R01', 'R01'], 9827];
    }

    #[DataProvider('specExamples')]
    public function testSpecExampleTotals(array $productCodes, int $expectedTotal): void
    {
        $basket = $this->newAcmeBasket();
        foreach ($productCodes as $productCode) {
            $basket->add($productCode);
        }

        self::assertSame($expectedTotal, $basket->total());
    }

    public function testEmptyBasketCostsNothing(): void
    {
        $basket = $this->newAcmeBasket();

        self::assertSame(0, $basket->delivery());
        self::assertSame(0, $basket->total());
    }

    public function testRepeatedAddsAccumulateOnOneLine(): void
    {
        $basket = $this->newAcmeBasket();
        $basket->add('G01');
        $basket->add('B01');
        $basket->add('G01');

        $lines = $basket->lines();

        self::assertCount(2, $lines);
        self::assertSame('G01', $lines[0]->product->code);
        self::assertSame(2, $lines[0]->quantity);
    }

    public function testDeliveryBandIsChosenFromSpendAfterOffers(): void
    {
        $basket = $this->newAcmeBasket();
        $basket->add('R01');
        $basket->add('R01');

        self::assertSame(6590, $basket->subtotal());
        self::assertSame(1648, $basket->discount());
        self::assertSame(495, $basket->delivery());
    }

    public function testUnknownProductIsRejected(): void
    {
        $basket = $this->newAcmeBasket();

        $this->expectException(UnknownProductException::class);
        $basket->add('X99');
    }
}
