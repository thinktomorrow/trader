<?php

namespace Thinktomorrow\Trader\Discounts\Domain\Conditions;

use Thinktomorrow\Trader\Common\Contracts\HasParameters;
use Thinktomorrow\Trader\Common\Helpers\HandlesKeyToClassMapping;

class ConditionKey
{
    use HandlesKeyToClassMapping;

    protected static $mapping = [
        'minimum_amount'        => MinimumAmount::class,
        'item_whitelist'        => ItemWhitelist::class,
        'item_blacklist'        => ItemBlacklist::class,
        'minimum_item_quantity' => MinimumItemQuantity::class,
        'start_at'              => Period::class,
        'end_at'                => Period::class, // TODO avoid duplicate conditional loading!!
    ];

    public static function fromCondition(HasParameters $condition)
    {
        return static::fromInstance($condition);
    }
}
