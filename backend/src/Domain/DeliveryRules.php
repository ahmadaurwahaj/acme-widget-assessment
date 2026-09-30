<?php

declare(strict_types=1);

namespace Acme\Domain;

use InvalidArgumentException;

final readonly class DeliveryRules
{
    private const int FREE_DELIVERY = 0;

    private array $tiers;

    public function __construct(DeliveryTier ...$tiers)
    {
        $thresholds = [];
        foreach ($tiers as $tier) {
            if (isset($thresholds[$tier->spendBelowInCents])) {
                throw new InvalidArgumentException("Two delivery tiers share the threshold {$tier->spendBelowInCents}.");
            }
            $thresholds[$tier->spendBelowInCents] = true;
        }

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
