<?php

namespace Thinktomorrow\Trader\Sales\Domain\Conditions;

use Thinktomorrow\Trader\Common\Domain\Conditions\Condition;
use Thinktomorrow\Trader\Common\Helpers\HandlesKeyToClassMapping;

class ConditionKey
{
    use HandlesKeyToClassMapping;

    protected static $mapping = [
        //
    ];

    public static function fromCondition(Condition $condition)
    {
        return static::fromInstance($condition);
    }
}
