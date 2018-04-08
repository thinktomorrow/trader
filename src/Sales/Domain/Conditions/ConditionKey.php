<?php

namespace Thinktomorrow\Trader\Sales\Domain\Conditions;

use Thinktomorrow\Trader\Common\Contracts\HasParameters;
use Thinktomorrow\Trader\Common\Helpers\HandlesKeyToClassMapping;

class ConditionKey
{
    use HandlesKeyToClassMapping;

    protected static $mapping = [
        'start_at'              => Period::class,
        'end_at'                => Period::class,
    ];

    public static function fromCondition(HasParameters $condition)
    {
        return static::fromInstance($condition);
    }
}
