<?php

declare(strict_types=1);

namespace Acme\Repository;

use Acme\Domain\Product;

interface ProductRepository
{
    public function findAll(): array;

    public function getByCode(string $code): Product;
}
