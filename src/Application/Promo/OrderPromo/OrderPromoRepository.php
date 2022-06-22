<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\OrderPromo;

interface OrderPromoRepository
{
    /** @return OrderPromo[] */
    public function getAvailableOrderPromos(): array;

    public function findOrderPromoByCouponCode(string $couponCode): ?OrderPromo;
}
