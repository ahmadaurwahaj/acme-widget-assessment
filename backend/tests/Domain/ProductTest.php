<?php

declare(strict_types=1);

namespace Acme\Tests\Domain;

use Acme\Domain\Product;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ProductTest extends TestCase
{
    public static function invalidProducts(): iterable
    {
        yield 'empty code' => ['', 'Red Widget', 3295];
        yield 'empty name' => ['R01', '', 3295];
        yield 'blank name' => ['R01', '   ', 3295];
        yield 'negative price' => ['R01', 'Red Widget', -1];
    }

    #[DataProvider('invalidProducts')]
    public function testInvalidProductIsRejected(string $code, string $name, int $priceInCents): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Product($code, $name, $priceInCents);
    }
}
