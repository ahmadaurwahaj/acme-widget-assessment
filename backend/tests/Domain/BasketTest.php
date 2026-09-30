<?php

declare(strict_types=1);

namespace Acme\Tests\Domain;

use Acme\Domain\Basket;
use Acme\Domain\BasketLine;
use Acme\Domain\DeliveryRules;
use Acme\Domain\DeliveryTier;
use Acme\Domain\Offer\BuyOneGetSecondHalfPrice;
use Acme\Domain\Offer\Offer;
use Acme\Domain\Product;
use Acme\Domain\UnknownProductException;
use Acme\Repository\InMemoryProductRepository;
use Acme\StoreConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BasketTest extends TestCase
{
    private function newAcmeBasket(): Basket
    {
        $storeConfig = StoreConfig::fromFile(__DIR__ . '/../../config/store.php');

        return new Basket($storeConfig->productRepository, $storeConfig->deliveryRules, ...$storeConfig->offers);
    }

    private function acmeDeliveryRules(): DeliveryRules
    {
        return new DeliveryRules(
            new DeliveryTier(spendBelowInCents: 5000, chargeInCents: 495),
            new DeliveryTier(spendBelowInCents: 9000, chargeInCents: 295),
        );
    }

    /** @param list<string> $productCodes */
    private function addAll(Basket $basket, array $productCodes): void
    {
        foreach ($productCodes as $productCode) {
            $basket->add($productCode);
        }
    }

    /** @return iterable<string, array{list<string>, int}> */
    public static function specExamples(): iterable
    {
        yield 'B01, G01' => [['B01', 'G01'], 3785];
        yield 'R01, R01' => [['R01', 'R01'], 5437];
        yield 'R01, G01' => [['R01', 'G01'], 6085];
        yield 'B01, B01, R01, R01, R01' => [['B01', 'B01', 'R01', 'R01', 'R01'], 9827];
    }

    /** @param list<string> $productCodes */
    #[DataProvider('specExamples')]
    public function testSpecExampleTotals(array $productCodes, int $expectedTotal): void
    {
        $basket = $this->newAcmeBasket();
        $this->addAll($basket, $productCodes);

        self::assertSame($expectedTotal, $basket->total());
    }

    public function testEmptyBasketCostsNothing(): void
    {
        $basket = $this->newAcmeBasket();

        $priceBreakdown = $basket->priceBreakdown();

        self::assertSame(0, $priceBreakdown->deliveryInCents);
        self::assertSame(0, $priceBreakdown->totalInCents);
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

        $priceBreakdown = $basket->priceBreakdown();

        self::assertSame(6590, $priceBreakdown->subtotalInCents);
        self::assertSame(1648, $priceBreakdown->discountInCents);
        self::assertSame(495, $priceBreakdown->deliveryInCents);
        self::assertSame(5437, $priceBreakdown->totalInCents);
    }

    /** @return iterable<string, array{list<string>, int, int}> */
    public static function discountLandsOnDeliveryBoundary(): iterable
    {
        yield 'exactly $50 after the offer' => [['A01', 'A01', 'X50'], 5000, 295];
        yield 'one cent under $50 after the offer' => [['A01', 'A01', 'X49'], 4999, 495];
        yield 'exactly $90 after the offer' => [['C01', 'C01', 'Y15'], 9000, 0];
        yield 'one cent under $90 after the offer' => [['C01', 'C01', 'Y14'], 8999, 295];
    }

    /** @param list<string> $productCodes */
    #[DataProvider('discountLandsOnDeliveryBoundary')]
    public function testDiscountCanMoveTheDeliveryTier(array $productCodes, int $expectedSpend, int $expectedDelivery): void
    {
        $productA = new Product('A01', 'Widget A', 3000);
        $productC = new Product('C01', 'Widget C', 5000);
        $catalogue = new InMemoryProductRepository(
            $productA,
            $productC,
            new Product('X50', 'Widget X50', 500),
            new Product('X49', 'Widget X49', 499),
            new Product('Y15', 'Widget Y15', 1500),
            new Product('Y14', 'Widget Y14', 1499),
        );
        $basket = new Basket(
            $catalogue,
            $this->acmeDeliveryRules(),
            new BuyOneGetSecondHalfPrice($productA),
            new BuyOneGetSecondHalfPrice($productC),
        );
        $this->addAll($basket, $productCodes);

        $priceBreakdown = $basket->priceBreakdown();
        $spendAfterOffers = $priceBreakdown->subtotalInCents - $priceBreakdown->discountInCents;

        self::assertSame($expectedSpend, $spendAfterOffers);
        self::assertSame($expectedDelivery, $priceBreakdown->deliveryInCents);
        self::assertSame($expectedSpend + $expectedDelivery, $priceBreakdown->totalInCents);
    }

    public function testBasketWithoutOffersChargesFullPrice(): void
    {
        $storeConfig = StoreConfig::fromFile(__DIR__ . '/../../config/store.php');
        $basket = new Basket($storeConfig->productRepository, $storeConfig->deliveryRules);
        $this->addAll($basket, ['R01', 'R01']);

        self::assertSame(6590 + 295, $basket->total());
    }

    public function testSeveralOffersAllApply(): void
    {
        $storeConfig = StoreConfig::fromFile(__DIR__ . '/../../config/store.php');
        $catalogue = $storeConfig->productRepository;
        $basket = new Basket(
            $catalogue,
            $storeConfig->deliveryRules,
            new BuyOneGetSecondHalfPrice($catalogue->getByCode('R01')),
            new BuyOneGetSecondHalfPrice($catalogue->getByCode('G01')),
        );
        $this->addAll($basket, ['R01', 'R01', 'G01', 'G01']);

        $priceBreakdown = $basket->priceBreakdown();

        self::assertSame(1648 + 1248, $priceBreakdown->discountInCents);
        self::assertSame(8684 + 295, $priceBreakdown->totalInCents);
    }

    public function testDiscountNeverGoesAboveTheSubtotal(): void
    {
        $storeConfig = StoreConfig::fromFile(__DIR__ . '/../../config/store.php');
        $hugeOffer = new class implements Offer {
            public function code(): string
            {
                return 'huge';
            }

            public function description(): string
            {
                return 'Huge discount';
            }

            public function discount(BasketLine ...$lines): int
            {
                return 1_000_000;
            }
        };
        $basket = new Basket($storeConfig->productRepository, $storeConfig->deliveryRules, $hugeOffer);
        $basket->add('B01');

        $priceBreakdown = $basket->priceBreakdown();

        self::assertSame(795, $priceBreakdown->discountInCents);
        self::assertSame(495, $priceBreakdown->totalInCents);
    }

    public function testUnknownProductIsRejected(): void
    {
        $basket = $this->newAcmeBasket();

        $this->expectException(UnknownProductException::class);
        $basket->add('X99');
    }
}
