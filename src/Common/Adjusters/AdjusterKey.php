<?php

namespace Thinktomorrow\Trader\Common\Adjusters;

use Thinktomorrow\Trader\Common\Contracts\HasParameters;
use Thinktomorrow\Trader\Common\Helpers\HandlesKeyToClassMapping;

class AdjusterKey
{
    use HandlesKeyToClassMapping;

    protected static $mapping = [
        'amount'     => Amount::class,
        'percentage' => Percentage::class,
    ];

    public static function fromAdjuster(Adjuster $adjuster)
    {
        return static::fromInstance($adjuster);
    }
}
