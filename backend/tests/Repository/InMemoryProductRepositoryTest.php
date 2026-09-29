<?php

declare(strict_types=1);

namespace Acme\Tests\Repository;

use Acme\Domain\Product;
use Acme\Domain\UnknownProductException;
use Acme\Repository\InMemoryProductRepository;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class InMemoryProductRepositoryTest extends TestCase
{
    public function testFindsProductByCode(): void
    {
        $blueWidget = new Product('B01', 'Blue Widget', 795);
        $repository = new InMemoryProductRepository($blueWidget);

        self::assertSame($blueWidget, $repository->getByCode('B01'));
        self::assertSame([$blueWidget], $repository->findAll());
    }

    public function testUnknownCodeThrows(): void
    {
        $repository = new InMemoryProductRepository();

        $this->expectException(UnknownProductException::class);
        $repository->getByCode('B01');
    }

    public function testDuplicateCodesAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new InMemoryProductRepository(
            new Product('B01', 'Blue Widget', 795),
            new Product('B01', 'Another Blue Widget', 100),
        );
    }
}
