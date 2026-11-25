<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\ApplicableConditions;

use Thinktomorrow\Trader\Application\Cart\Read\Cart;
use Thinktomorrow\Trader\Application\Cart\PaymentMethod\PaymentMethodForCart;
use Thinktomorrow\Trader\Application\Cart\ShippingProfile\ShippingProfileForCart;
use Thinktomorrow\Trader\Application\Promo\Preview\PromoPreviewProduct;
use Thinktomorrow\Trader\Application\Promo\Preview\PromoPreviewStatus;
use Thinktomorrow\Trader\Domain\Common\Map\Mappable;
use Thinktomorrow\Trader\Domain\Model\Order\AppliedDiscount\DiscountableItem;
use Thinktomorrow\Trader\Domain\Model\Order\Order;

interface ApplicableCondition extends Mappable
{
    public static function fromMappedData(array $state, array $aggregateState): static;

    /** Check the real order and the actual discount target when applying a discount. */
    public function check(Order $order, DiscountableItem $discountable): bool;

    /**
     * Assess a suggestion, not an applied discount. The subject prompted the suggestion
     * and may differ from the actual discount target (e.g. a product for an order discount).
     * Distinguish already met, met after addition, closer, and unaffected.
     */
    public function evaluatePreview(?Cart $cart, PromoPreviewProduct|Cart|ShippingProfileForCart|PaymentMethodForCart $subject): PromoPreviewStatus;
}
