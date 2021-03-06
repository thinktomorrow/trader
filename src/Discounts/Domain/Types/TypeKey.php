<?php

namespace Thinktomorrow\Trader\Discounts\Domain\Types;

use Thinktomorrow\Trader\Common\Helpers\HandlesKeyToClassMapping;
use Thinktomorrow\Trader\Discounts\Domain\Discount;

class TypeKey
{
    use HandlesKeyToClassMapping;

    protected static $mapping = [
        'percentage_off'      => PercentageOffDiscount::class,
        'fixed_amount_off'    => FixedAmountOffDiscount::class,
        'percentage_off_item' => PercentageOffItemDiscount::class,
        'free_item'           => FreeItemDiscount::class,
    ];

    public static function fromDiscount(Discount $discount)
    {
        return static::fromInstance($discount);
    }
}
