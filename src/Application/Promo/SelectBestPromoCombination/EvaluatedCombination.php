<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\SelectBestPromoCombination;

use Money\Money;

/** A trial combination scored against the final total, including VAT. */
final readonly class EvaluatedCombination
{
    public function __construct(
        public PromoCombination $combination,
        public Money $total,
        public int $appliedDiscountCount,
    ) {}
}
