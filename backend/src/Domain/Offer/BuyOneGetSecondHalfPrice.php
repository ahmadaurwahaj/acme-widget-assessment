<?php

declare(strict_types=1);

namespace Acme\Domain\Offer;

use Acme\Domain\BasketLine;
use Acme\Domain\Product;

final readonly class BuyOneGetSecondHalfPrice implements Offer
{
    public function __construct(private Product $product) {}

    public function code(): string
    {
        $offerCode = strtolower($this->product->code) . '-second-half-price';

        return $offerCode;
    }

    public function description(): string
    {
        $description = "Buy one {$this->product->name}, get the second one half price";

        return $description;
    }

    public function discount(BasketLine ...$lines): int
    {
        $discount = 0;

        foreach ($lines as $line) {
            if ($line->product->code !== $this->product->code) {
                continue;
            }

            $pairs = intdiv($line->quantity, 2);
            $fullPrice = $line->product->priceInCents;

            $halfPrice = intdiv($fullPrice, 2);
            $savingPerPair = $fullPrice - $halfPrice;

            $discount += $pairs * $savingPerPair;
        }

        return $discount;
    }
}
