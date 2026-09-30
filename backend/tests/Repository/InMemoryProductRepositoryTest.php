<?php

declare(strict_types=1);

namespace Acme\Tests\Repository;

use Acme\Domain\Product;
use Acme\Domain\UnknownProductException;
use Acme\Repository\InMemoryProductRepository;
use InvalidArgumentException;
use LogicException;
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

    public function testLoadsTheShopProductsFromFile(): void
    {
        $repository = InMemoryProductRepository::fromFile(__DIR__ . '/../../config/products.php');

        self::assertCount(3, $repository->findAll());
        self::assertSame(3295, $repository->getByCode('R01')->priceInCents);
    }

    public function testFileWithSomethingOtherThanProductsIsRejected(): void
    {
        $path = sys_get_temp_dir() . '/acme-products-' . bin2hex(random_bytes(4)) . '.php';
        file_put_contents($path, "<?php return ['R01'];");

        $this->expectException(LogicException::class);

        try {
            InMemoryProductRepository::fromFile($path);
        } finally {
            unlink($path);
        }
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
