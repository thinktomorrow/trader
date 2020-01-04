<?php

namespace Thinktomorrow\Trader\TestsOld\Stubs;

use Thinktomorrow\Trader\Discounts\Domain\Types\TypeKey;

class DiscountTypeKey extends TypeKey
{
    protected static $mapping = [
        'percentage_off_shipping' => ShippingDiscountDummy::class,
        'percentage_off_payment'  => PaymentDiscountDummy::class,
    ];
}
