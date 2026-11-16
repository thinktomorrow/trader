<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\SelectBestPromoCombination;

use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters\AdjustOrderPricingSnapshot;
use Thinktomorrow\Trader\Application\Promo\ApplicableDiscounts\OrderSubtotalDiscount;
use Thinktomorrow\Trader\Application\Promo\ApplicableDiscounts\SalePriceProductDiscount;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Promo\Promo;

/** Searches compatible subsets and compares their definitive totals, including VAT. */
final class FindBestCombination
{
    private ?EvaluatedCombination $best = null;

    private ?SelectedServiceProfiles $profiles = null;

    public function __construct(
        private ApplyPromoCombination $applicator,
        private AdjustOrderPricingSnapshot $adjustOrderPricingSnapshot,
    ) {}

    public function find(Order $order, PromoCandidates $candidates, ?SelectedServiceProfiles $profiles): PromoCombination
    {
        $this->best = null;
        $this->profiles = $profiles;

        try {
            $promos = $candidates->promos();

            if ($promos === []) {
                return new PromoCombination([], []);
            }

            // All unconditional, combinable order discounts can be applied without trials.
            if ($this->allUnconditionalOrderPromosCanCombine($promos, $candidates)) {
                return new PromoCombination($promos, []);
            }

            $candidates->indexProductDiscounts($order);

            // With one sale-price promo and mutually combinable unconditional order
            // promos, only two selections need comparison (if it combines with all or none).
            $selections = $this->selectionsForSalePriceShortcut($promos, $candidates);

            if ($selections !== null) {
                foreach ($selections as $selection) {
                    $this->evaluateSelection($order, $selection, $candidates);
                }

                return $this->best->combination;
            }

            // Reject incompatible subsets before trying their product discounts.
            $this->searchCompatibleCombinations($order, $promos, $candidates, 0, []);

            return $this->best->combination;
        } finally {
            $this->best = null;
            $this->profiles = null;
        }
    }

    /**
     * Evaluate complete selections only after considering every promo.
     * Reject incompatible selections before exploring their inclusion branch.
     *
     * @param  list<Promo>  $promos
     * @param  list<Promo>  $selected
     */
    private function searchCompatibleCombinations(Order $order, array $promos, PromoCandidates $candidates, int $index, array $selected): void
    {
        // This combination is now complete: evaluate it against the best so far.
        if ($index === count($promos)) {
            $this->evaluateSelection($order, $selected, $candidates);

            return;
        }

        // Explore combinations without this promo first: for A followed by B: [] and [B].
        $this->searchCompatibleCombinations($order, $promos, $candidates, $index + 1, $selected);

        $promo = $promos[$index];

        // Skip exploring combinations with this promo if it conflicts with those already selected.
        if (! $this->canCombineWithSelected($promo, $selected) || ! $this->canStackProductsWithSelected($promo, $selected, $candidates)) {
            return;
        }

        // Then explore combinations with this promo: [A] and, if compatible, [A, B].
        $this->searchCompatibleCombinations($order, $promos, $candidates, $index + 1, [...$selected, $promo]);
    }

    /** @param list<Promo> $selected */
    private function evaluateSelection(Order $order, array $selected, PromoCandidates $candidates): void
    {
        $candidate = $this->evaluateCombination($order, $selected, $candidates);

        if ($this->isBetterCombination($candidate, $this->best)) {
            $this->best = $candidate;
        }
    }

    /** @param list<Promo> $selected */
    private function evaluateCombination(Order $order, array $selected, PromoCandidates $prepared): EvaluatedCombination
    {
        $combination = new PromoCombination($selected, $prepared->productDiscountsForSelection($order, $selected));

        $trial = unserialize(serialize($order));

        $this->applicator->apply($trial, $combination, $prepared, true, $this->profiles);

        $total = $this->adjustOrderPricingSnapshot->calculate($trial)->getTotalIncl();

        $appliedDiscountCount = array_sum(array_map(fn ($discountable): int => count($discountable->getDiscounts()), [$trial, ...$trial->getLines(), ...$trial->getShippings(), ...$trial->getPayments()]));

        return new EvaluatedCombination($combination, $total, $appliedDiscountCount);
    }

    /**
     * On equal totals prefer more applied discounts; an exact tie keeps the first
     * result from the deterministic search order.
     */
    private function isBetterCombination(EvaluatedCombination $candidate, ?EvaluatedCombination $best): bool
    {
        return $best === null
            || $candidate->total->lessThan($best->total)
            || ($candidate->total->equals($best->total) && $candidate->appliedDiscountCount > $best->appliedDiscountCount);
    }

    /** @param list<Promo> $selected */
    private function canCombineWithSelected(Promo $candidate, array $selected): bool
    {
        foreach ($selected as $existing) {
            if (! $this->canCombine($candidate, $existing)) {
                return false;
            }
        }

        return true;
    }

    /** @param list<Promo> $selected */
    private function canStackProductsWithSelected(Promo $candidate, array $selected, PromoCandidates $prepared): bool
    {
        $candidateLines = $prepared->productDiscountsFor($candidate);

        foreach ($selected as $existing) {
            $existingLines = $prepared->productDiscountsFor($existing);

            foreach ($candidateLines as $lineId => $discounts) {
                if ($discounts === [] || ($existingLines[$lineId] ?? []) === []) {
                    continue;
                }

                if (! $candidate->getCombinationPolicy()->allowsProductStackingWith($existing->getCombinationPolicy())) {
                    return false;
                }
            }
        }

        return true;
    }

    private function canCombine(Promo $a, Promo $b): bool
    {
        return $a->canCombineWith($b);
    }

    /** @param list<Promo> $promos */
    private function allUnconditionalOrderPromosCanCombine(array $promos, PromoCandidates $prepared): bool
    {
        if ($promos === []) {
            return false;
        }

        foreach ($promos as $index => $promo) {
            $promoDiscounts = $promo->getDiscounts();
            if ($promoDiscounts === []) {
                return false;
            }

            $discounts = $prepared->discountsFor($promo);
            foreach ($promoDiscounts as $discountIndex => $promoDiscount) {
                if (! ($discounts[$discountIndex] ?? null) instanceof OrderSubtotalDiscount || $promoDiscount->getConditions() !== []) {
                    return false;
                }
            }

            foreach (array_slice($promos, $index + 1) as $other) {
                if (! $this->canCombine($promo, $other)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param  list<Promo>  $promos
     * @return array{list<Promo>, list<Promo>}|null
     */
    private function selectionsForSalePriceShortcut(array $promos, PromoCandidates $prepared): ?array
    {
        $orderOnly = array_values(array_filter($promos, fn (Promo $promo): bool => $this->allUnconditionalOrderPromosCanCombine([$promo], $prepared)));
        $otherPromos = array_values(array_filter($promos, fn (Promo $promo): bool => ! in_array($promo, $orderOnly, true)));

        if ($orderOnly === [] || count($otherPromos) !== 1 || ! $this->allUnconditionalOrderPromosCanCombine($orderOnly, $prepared)) {
            return null;
        }

        $other = $otherPromos[0];
        $compatibleOrderOnly = array_values(array_filter($orderOnly, fn (Promo $promo): bool => $this->canCombine($other, $promo)));
        $otherDiscounts = $prepared->discountsFor($other);

        if (count($otherDiscounts) !== 1 || ! $otherDiscounts[0] instanceof SalePriceProductDiscount
            || (count($compatibleOrderOnly) !== 0 && count($compatibleOrderOnly) !== count($orderOnly))) {
            return null;
        }

        return [$orderOnly, $compatibleOrderOnly === [] ? [$other] : [...$orderOnly, $other]];
    }
}
