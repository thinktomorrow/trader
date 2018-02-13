<?php

namespace Thinktomorrow\Trader\Discounts\Domain\Conditions;

use Thinktomorrow\Trader\Common\Domain\HandlesKeyToClassMapping;

final class ConditionKey
{
    use HandlesKeyToClassMapping;

    protected static $mapping = [
        'minimum_amount'        => MinimumAmount::class,
        'purchasable_ids'       => ItemWhitelist::class,
        'minimum_item_quantity' => MinimumItemQuantity::class,
        'start_at'              => Period::class,
        'end_at'                => Period::class, // TODO avoid duplicate conditional loading!!
    ];

    public static function fromCondition(Condition $condition)
    {
        return static::fromInstance($condition);
    }
}
