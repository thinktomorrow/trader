<?php declare(strict_types=1);

namespace Thinktomorrow\Trader\Common\Domain\Taxes;

use Thinktomorrow\Trader\Common\Cash\Percentage;

class TaxRate extends Percentage
{
    /**
     * General tax percentage for cart elements that lack a tax rate.
     * Specifically used for global costs as shipping and payment
     *
     * @var int
     */
    private static $DEFAULT = 21;

    public static function default(): TaxRate
    {
        return static::fromPercent(static::$DEFAULT);
    }
}
