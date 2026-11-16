<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\SelectBestPromoCombination;

use Thinktomorrow\Trader\Application\Promo\ApplicableDiscounts\ProductDiscount;
use Thinktomorrow\Trader\Domain\Model\Promo\Promo;

final readonly class PromoCombination
{
    /**
     * @param list<Promo> $promos
     * @param array<string, list<ProductDiscount>> $productDiscounts
     */
    public function __construct(public array $promos, public array $productDiscounts) {}
}
