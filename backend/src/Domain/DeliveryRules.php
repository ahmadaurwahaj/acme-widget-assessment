<?php

declare(strict_types=1);

namespace Acme\Domain;

final class DeliveryRules
{
    private const int FREE_DELIVERY = 0;

    private array $tiers;

    public function __construct(DeliveryTier ...$tiers)
    {
        usort($tiers, static fn(DeliveryTier $a, DeliveryTier $b): int => $a->spendBelowInCents <=> $b->spendBelowInCents);
        $this->tiers = $tiers;
    }

    public function chargeFor(int $spendInCents): int
    {
        $matchingTier = array_find(
            $this->tiers,
            fn(DeliveryTier $tier): bool => $spendInCents < $tier->spendBelowInCents,
        );

        if ($matchingTier === null) {
            return self::FREE_DELIVERY;
        }

        $deliveryCharge = $matchingTier->chargeInCents;

        return $deliveryCharge;
    }
}
