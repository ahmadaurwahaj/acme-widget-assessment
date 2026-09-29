<?php

declare(strict_types=1);

namespace Acme\Domain;

use InvalidArgumentException;

final class UnknownProductException extends InvalidArgumentException
{
    public function __construct(public readonly string $productCode)
    {
        parent::__construct("Unknown product code: {$productCode}");
    }
}
