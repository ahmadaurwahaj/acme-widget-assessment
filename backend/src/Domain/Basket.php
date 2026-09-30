<?php

declare(strict_types=1);

namespace Acme\Domain;

use Acme\Domain\Offer\Offer;
use Acme\Repository\ProductRepository;

final class Basket
{
    private array $lines = [];

    private readonly array $offers;

    public function __construct(
        private readonly ProductRepository $catalogue,
        private readonly DeliveryRules $deliveryRules,
        Offer ...$offers,
    ) {
        $this->offers = $offers;
    }

    public function add(string $productCode): void
    {
        $product = $this->catalogue->getByCode($productCode);
        $currentQuantity = $this->lines[$product->code]->quantity ?? 0;

        $this->lines[$product->code] = new BasketLine($product, $currentQuantity + 1);
    }

    public function lines(): array
    {
        $lines = array_values($this->lines);

        return $lines;
    }

    public function isEmpty(): bool
    {
        return $this->lines === [];
    }

    public function subtotal(): int
    {
        $subtotal = 0;
        foreach ($this->lines as $line) {
            $subtotal += $line->totalInCents();
        }

        return $subtotal;
    }

    public function discount(): int
    {
        $lines = $this->lines();

        $discount = 0;
        foreach ($this->offers as $offer) {
            $discount += $offer->discount($lines);
        }

        return $discount;
    }

    public function delivery(): int
    {
        if ($this->isEmpty()) {
            return 0;
        }

        $spendAfterOffers = $this->subtotal() - $this->discount();
        $deliveryCharge = $this->deliveryRules->chargeFor($spendAfterOffers);

        return $deliveryCharge;
    }

    public function total(): int
    {
        $spendAfterOffers = $this->subtotal() - $this->discount();
        $total = $spendAfterOffers + $this->delivery();

        return $total;
    }
}
