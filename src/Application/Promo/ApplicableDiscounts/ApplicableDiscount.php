<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\ApplicableDiscounts;

use Thinktomorrow\Trader\Application\Cart\Read\Cart;
use Thinktomorrow\Trader\Application\Cart\Read\CartPayment;
use Thinktomorrow\Trader\Application\Cart\Read\CartShipping;
use Thinktomorrow\Trader\Application\Promo\Preview\PromoPreviewProduct;
use Thinktomorrow\Trader\Application\Promo\Preview\PromoPreviewStatus;
use Thinktomorrow\Trader\Domain\Common\Map\Mappable;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\DiscountableType;

interface ApplicableDiscount extends Mappable
{
    public function getDiscountableType(): DiscountableType;

    /**
     * Whether this discount could affect any combination evaluated for the order.
     * Unlike isApplicable(), which checks one target in its current state, this
     * pre-check returns false only when no combination could make it apply.
     * When in doubt, keep the discount as a candidate.
     */
    public function couldApply(Order $order): bool;

    /**
     * Determine whether the promo is an incentive for this subject.
     * The subject prompted the suggestion; it need not receive the discount
     * itself (a product can prompt a suggestion for an order discount).
     *
     * AlreadyEligible means the cart met the conditions before adding the
     * product. It does not mean the discount was actually applied.
     */
    public function promoPreviewStatus(PromoPreviewProduct|Cart|CartShipping|CartPayment $subject, ?Cart $cart): PromoPreviewStatus;
}
