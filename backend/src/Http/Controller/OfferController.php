<?php

declare(strict_types=1);

namespace Acme\Http\Controller;

use Acme\Http\Dto\OfferResponseDto;
use Acme\Http\JsonResponse;
use Acme\Service\OfferService;

final readonly class OfferController
{
    public function __construct(private OfferService $offerService) {}

    public function list(): JsonResponse
    {
        $offers = $this->offerService->listOffers();
        $offerDtos = [];
        foreach ($offers as $offer) {
            $offerDtos[] = OfferResponseDto::fromOffer($offer);
        }

        $response = new JsonResponse(200, $offerDtos);

        return $response;
    }
}
