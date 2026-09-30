<?php

declare(strict_types=1);

namespace Acme\Http\Dto;

use Acme\Domain\Offer\Offer;

final readonly class OfferResponseDto
{
    public function __construct(
        public string $code,
        public string $description,
    ) {}

    public static function fromOffer(Offer $offer): self
    {
        return new self($offer->code(), $offer->description());
    }
}
