<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\SelectBestPromoCombination;

use Money\Money;
use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters\AdjustOrderPricingSnapshot;
use Thinktomorrow\Trader\Application\Promo\ApplicableDiscounts\OrderSubtotalDiscount;
use Thinktomorrow\Trader\Application\Promo\ApplicableDiscounts\ProductDiscount;
use Thinktomorrow\Trader\Application\Promo\ApplicableDiscounts\SalePriceProductDiscount;
use Thinktomorrow\Trader\Domain\Model\Order\AppliedDiscount\AppliedDiscountId;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Line;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\DiscountableType;
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

            // Line-only promos can be selected together: competition is resolved on
            // each line, without multiplying the search by every product promo.
            if ($this->allProductPromosCanCombine($promos, $candidates)) {
                return new PromoCombination($promos, $this->bestProductDiscountsForSelection($order, $promos, $candidates));
            }

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
        if (! $this->canCombineWithSelected($promo, $selected)) {
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
        $combination = new PromoCombination($selected, $this->bestProductDiscountsForSelection($order, $selected, $prepared));

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

    /** @param list<Promo> $promos */
    private function allProductPromosCanCombine(array $promos, PromoCandidates $candidates): bool
    {
        foreach ($promos as $index => $promo) {
            foreach ($candidates->discountsFor($promo) as $discount) {
                if ($discount->getDiscountableType() !== DiscountableType::line) {
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
     * Choose the best eligible discount group on each line. A promo's discounts
     * stay together on a target, while different promos compete unless both
     * explicitly permit stacking on that same target.
     *
     * @param  list<Promo>  $selected
     * @return array<string, list<ProductDiscount>>
     */
    private function bestProductDiscountsForSelection(Order $order, array $selected, PromoCandidates $candidates): array
    {
        $choices = [];

        foreach ($order->getLines() as $line) {
            $lineId = $line->getDiscountableId()->get();
            $groups = [];

            foreach ($selected as $promo) {
                $discounts = $candidates->productDiscountsFor($promo)[$lineId] ?? [];

                if ($discounts !== []) {
                    $groups[] = [$promo, $discounts];
                }
            }

            $best = [];
            $bestTotal = $line->getTotal()->getIncludingVat();
            $this->searchLineDiscounts($order, $line, $groups, 0, [], [], $best, $bestTotal);
            $choices[$lineId] = $best;
        }

        return $choices;
    }

    /**
     * @param  list<array{Promo, list<ProductDiscount>}>  $groups
     * @param  list<Promo>  $chosenPromos
     * @param  list<ProductDiscount>  $chosenDiscounts
     * @param  list<ProductDiscount>  $best
     */
    private function searchLineDiscounts(Order $order, Line $line, array $groups, int $index, array $chosenPromos, array $chosenDiscounts, array &$best, Money &$bestTotal): void
    {
        if ($index === count($groups)) {
            if ($chosenDiscounts === []) {
                return;
            }

            $trialLine = unserialize(serialize($line));

            foreach ($chosenDiscounts as $sequence => $discount) {
                $discount->apply($order, $trialLine, AppliedDiscountId::fromString('simulated-line-discount-'.($sequence + 1)));
            }

            $total = $trialLine->getTotal()->getIncludingVat();

            if ($total->lessThan($bestTotal) || ($total->equals($bestTotal) && count($trialLine->getDiscounts()) > count($best))) {
                $best = $chosenDiscounts;
                $bestTotal = $total;
            }

            return;
        }

        $this->searchLineDiscounts($order, $line, $groups, $index + 1, $chosenPromos, $chosenDiscounts, $best, $bestTotal);

        [$promo, $discounts] = $groups[$index];

        foreach ($chosenPromos as $existing) {
            if (! $promo->getCombinationPolicy()->allowsProductStackingWith($existing->getCombinationPolicy())) {
                return;
            }
        }

        $this->searchLineDiscounts($order, $line, $groups, $index + 1, [...$chosenPromos, $promo], [...$chosenDiscounts, ...$discounts], $best, $bestTotal);
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
