<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\Preview;

use Thinktomorrow\Trader\Application\Cart\Read\Cart;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\Discount;
use Thinktomorrow\Trader\Domain\Model\Promo\Promo;

/** Preview suitability is independent of winning the final discount selection. */
interface PromoPreviewEligibility
{
    public function supports(string $discountKey): bool;

    /** Quantity is zero when checking a product already present in the given cart. */
    public function assess(Promo $promo, Discount $discount, PromoPreviewProduct $product, int $quantity, ?Cart $cart): PromoPreviewStatus;
}
