<?php declare(strict_types=1);

namespace Thinktomorrow\Trader\Discounts\Domain;

use Thinktomorrow\Trader\Common\Domain\WithClassMapping;

class DiscountType
{
    use WithClassMapping;

    protected static function getMapping(): array
    {
        return app()->make('trader_config')->discountTypes();
    }
}
