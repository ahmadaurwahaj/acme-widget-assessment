<?php

declare(strict_types=1);

namespace Acme;

use Acme\Domain\DeliveryRules;
use Acme\Repository\ProductRepository;
use LogicException;

final readonly class StoreConfig
{
    public function __construct(
        public ProductRepository $productRepository,
        public DeliveryRules $deliveryRules,
        public array $offers,
    ) {}

    public static function fromFile(string $path): self
    {
        $storeConfig = require $path;

        if (!$storeConfig instanceof self) {
            throw new LogicException("{$path} must return a " . self::class . '.');
        }

        return $storeConfig;
    }
}
