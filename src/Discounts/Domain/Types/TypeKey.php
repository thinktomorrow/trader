<?php

namespace Thinktomorrow\Trader\Discounts\Domain\Types;

use Thinktomorrow\Trader\Common\Helpers\HandlesKeyToClassMapping;
use Thinktomorrow\Trader\Discounts\Domain\Discount;

final class TypeKey
{
    use HandlesKeyToClassMapping;

    protected static $mapping = [
        'percentage_off'      => PercentageOffDiscount::class,
        'percentage_off_item' => PercentageOffItemDiscount::class,
        'free_item'           => FreeItemDiscount::class,
    ];

    public static function fromDiscount(Discount $discount)
    {
        return static::fromInstance($discount);
    }
}
