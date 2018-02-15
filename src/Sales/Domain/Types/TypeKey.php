<?php

namespace Thinktomorrow\Trader\Sales\Domain\Types;

use Thinktomorrow\Trader\Common\Helpers\HandlesKeyToClassMapping;
use Thinktomorrow\Trader\Sales\Domain\Sale;

final class TypeKey
{
    use HandlesKeyToClassMapping;

    protected static $mapping = [
        'percentage_off'      => PercentageOffSale::class,
        'fixed_amount_off'    => FixedAmountOffSale::class,
        'fixed_amount'        => FixedAmountSale::class,
        'fixed_custom_amount' => FixedCustomAmountSale::class, // Saleprice as set per item
    ];

    public static function fromSale(Sale $sale)
    {
        return static::fromInstance($sale);
    }
}
