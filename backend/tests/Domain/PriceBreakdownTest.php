<?php

declare(strict_types=1);

namespace Acme\Tests\Domain;

use Acme\Domain\PriceBreakdown;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PriceBreakdownTest extends TestCase
{
    public function testAcceptsNumbersThatAddUp(): void
    {
        $priceBreakdown = new PriceBreakdown(subtotalInCents: 6590, discountInCents: 1648, deliveryInCents: 495, totalInCents: 5437);

        self::assertSame(5437, $priceBreakdown->totalInCents);
    }

    /** @return iterable<string, array{int, int, int, int}> */
    public static function invalidBreakdowns(): iterable
    {
        yield 'total does not add up' => [6590, 1648, 495, 5000];
        yield 'negative discount' => [1000, -100, 0, 1100];
        yield 'negative subtotal' => [-100, 0, 100, 0];
        yield 'discount larger than subtotal' => [100, 200, 495, 395];
    }

    #[DataProvider('invalidBreakdowns')]
    public function testRejectsNumbersThatDoNotAddUp(int $subtotal, int $discount, int $delivery, int $total): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PriceBreakdown($subtotal, $discount, $delivery, $total);
    }
}
