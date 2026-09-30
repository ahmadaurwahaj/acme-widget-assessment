<?php

declare(strict_types=1);

namespace Acme\Http\Controller;

use Acme\Domain\Offer\Offer;
use Acme\Http\Dto\OfferResponseDto;
use Acme\Http\JsonResponse;

final readonly class OfferController
{
    /** @var list<Offer> */
    private array $offers;

    public function __construct(Offer ...$offers)
    {
        $this->offers = array_values($offers);
    }

    public function list(): JsonResponse
    {
        $offerDtos = [];
        foreach ($this->offers as $offer) {
            $offerDtos[] = OfferResponseDto::fromOffer($offer);
        }

        return new JsonResponse(200, $offerDtos);
    }
}
