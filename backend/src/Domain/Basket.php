<?php

declare(strict_types=1);

namespace Acme\Domain;

use Acme\Domain\Offer\Offer;
use Acme\Repository\ProductRepository;

final class Basket
{
    /** @var array<string, BasketLine> */
    private array $lines = [];

    /** @var list<Offer> */
    private readonly array $offers;

    public function __construct(
        private readonly ProductRepository $catalogue,
        private readonly DeliveryRules $deliveryRules,
        Offer ...$offers,
    ) {
        $this->offers = array_values($offers);
    }

    public function add(string $productCode): void
    {
        $product = $this->catalogue->getByCode($productCode);
        $currentQuantity = $this->lines[$product->code]->quantity ?? 0;

        $this->lines[$product->code] = new BasketLine($product, $currentQuantity + 1);
    }

    /** @return list<BasketLine> */
    public function lines(): array
    {
        return array_values($this->lines);
    }

    public function priceBreakdown(): PriceBreakdown
    {
        $subtotal = $this->subtotal();
        $discount = min($this->discount(), $subtotal);
        $spendAfterOffers = $subtotal - $discount;

        $delivery = 0;
        if (!$this->isEmpty()) {
            $delivery = $this->deliveryRules->chargeFor($spendAfterOffers);
        }

        return new PriceBreakdown(
            subtotalInCents: $subtotal,
            discountInCents: $discount,
            deliveryInCents: $delivery,
            totalInCents: $spendAfterOffers + $delivery,
        );
    }

    public function total(): int
    {
        return $this->priceBreakdown()->totalInCents;
    }

    private function isEmpty(): bool
    {
        return $this->lines === [];
    }

    private function subtotal(): int
    {
        $subtotal = 0;
        foreach ($this->lines as $line) {
            $subtotal += $line->totalInCents();
        }

        return $subtotal;
    }

    private function discount(): int
    {
        $lines = $this->lines();

        $discount = 0;
        foreach ($this->offers as $offer) {
            $discount += $offer->discount(...$lines);
        }

        return $discount;
    }
}
