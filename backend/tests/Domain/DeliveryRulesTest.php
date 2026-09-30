<?php

declare(strict_types=1);

namespace Acme\Tests\Domain;

use Acme\Domain\DeliveryRules;
use Acme\Domain\DeliveryTier;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DeliveryRulesTest extends TestCase
{
    public static function bandBoundaries(): iterable
    {
        yield 'just under $50' => [4999, 495];
        yield 'exactly $50' => [5000, 295];
        yield 'just under $90' => [8999, 295];
        yield 'exactly $90' => [9000, 0];
    }

    #[DataProvider('bandBoundaries')]
    public function testChargeAtBandBoundaries(int $spendInCents, int $expectedCharge): void
    {
        $rules = new DeliveryRules(
            new DeliveryTier(spendBelowInCents: 5000, chargeInCents: 495),
            new DeliveryTier(spendBelowInCents: 9000, chargeInCents: 295),
        );

        self::assertSame($expectedCharge, $rules->chargeFor($spendInCents));
    }

    public function testTierOrderInConfigDoesNotMatter(): void
    {
        $rules = new DeliveryRules(
            new DeliveryTier(spendBelowInCents: 9000, chargeInCents: 295),
            new DeliveryTier(spendBelowInCents: 5000, chargeInCents: 495),
        );

        self::assertSame(495, $rules->chargeFor(1000));
    }

    public function testDuplicateThresholdsAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new DeliveryRules(
            new DeliveryTier(spendBelowInCents: 5000, chargeInCents: 495),
            new DeliveryTier(spendBelowInCents: 5000, chargeInCents: 295),
        );
    }

    public function testNegativeChargeIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new DeliveryTier(spendBelowInCents: 5000, chargeInCents: -1);
    }
}
