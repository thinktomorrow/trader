<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo;

use Thinktomorrow\Trader\Domain\Model\Order\Order;

class GetApplicablePromos
{
    public function __construct()
    {
    }

    public function get(Order $order): array
    {
        // PROMO:
            // - conditions
            // - applicable (Order)
            // - discountTotal
        // - ApplyPromo (orderId, promoId)

        // Get all active promos (based on status active which is based on the period)
        // For each promo we check its conditions and see if they match

        // based on the total discount amount of each promo - we select the highest promo
        // if the promo is set via coupon - this has precedence over the ranking
        // If the promo can be combined with others - we allow the next in line as well (as long as the total discount does not bring us below zero)
    }
}
