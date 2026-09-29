<?php

declare(strict_types=1);

namespace Acme\Domain\Offer;

final readonly class BuyOneGetSecondHalfPrice implements Offer
{
    public function __construct(private string $productCode) {}

    public function discount(array $lines): int
    {
        $discount = 0;

        foreach ($lines as $line) {
            if ($line->product->code !== $this->productCode) {
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
