<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Discounts\Domain;

interface Couponable
{
    public function getCoupon(): ?string;

    public function enterCoupon(string $coupon): void;

    public function removeCoupon(): void;
}
