<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Purchase\Discounts\Domain;

interface Couponable
{
    public function enteredCoupon(): ?string;

    public function enterCoupon(string $coupon): void;

    public function removeCoupon();
}
