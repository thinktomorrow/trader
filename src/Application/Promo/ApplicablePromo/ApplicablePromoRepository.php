<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Application\Promo\ApplicablePromo;

interface ApplicablePromoRepository
{
    public function getActivePromos(): array;

    public function findActivePromoByCouponCode(string $couponCode): ?ApplicablePromo;
}
