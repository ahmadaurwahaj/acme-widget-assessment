<?php

declare(strict_types=1);

namespace Acme;

use Acme\Domain\DeliveryRules;
use Acme\Domain\Offer\Offer;
use Acme\Repository\ProductRepository;
use InvalidArgumentException;
use LogicException;

final readonly class StoreConfig
{
    /** @var list<Offer> */
    public array $offers;

    public function __construct(
        public ProductRepository $productRepository,
        public DeliveryRules $deliveryRules,
        Offer ...$offers,
    ) {
        $offerCodes = [];
        foreach ($offers as $offer) {
            if (isset($offerCodes[$offer->code()])) {
                throw new InvalidArgumentException("Duplicate offer code: {$offer->code()}");
            }
            $offerCodes[$offer->code()] = true;
        }

        $this->offers = array_values($offers);
    }

    public static function fromFile(string $path): self
    {
        $storeConfig = require $path;

        if (!$storeConfig instanceof self) {
            throw new LogicException("{$path} must return a " . self::class . '.');
        }

        return $storeConfig;
    }
}
