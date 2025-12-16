<?php

namespace Thinktomorrow\Trader\Domain\Model\Order\Discount;

use Thinktomorrow\Trader\Domain\Common\Price\DefaultDiscountPrice;
use Thinktomorrow\Trader\Domain\Common\Price\DiscountPrice;
use Thinktomorrow\Trader\Domain\Common\Price\Price;

class GetValidatedTotalDiscountPrice
{
    public static function get(Price $price, DiscountableItem $item): DiscountPrice
    {
        $discountPrice = $item->getSumOfDiscountPrices();

        if ($discountPrice->getExcludingVat()->greaterThanOrEqual($price->getExcludingVat())) {
            return DefaultDiscountPrice::fromExcludingVat($price->getExcludingVat());
        }

        return $discountPrice;
    }
}
