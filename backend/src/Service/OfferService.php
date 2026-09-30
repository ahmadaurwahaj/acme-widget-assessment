<?php

declare(strict_types=1);

namespace Acme\Service;

use Acme\Domain\Offer\Offer;

final readonly class OfferService
{
    private array $offers;

    public function __construct(Offer ...$offers)
    {
        $this->offers = $offers;
    }

    public function listOffers(): array
    {
        $offers = $this->offers;

        return $offers;
    }
}
