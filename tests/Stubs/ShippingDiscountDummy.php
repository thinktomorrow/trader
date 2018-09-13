<?php

namespace Thinktomorrow\Trader\Tests\Stubs;

use Money\Money;
use Thinktomorrow\Trader\Discounts\Domain\EligibleForDiscount;
use Thinktomorrow\Trader\Discounts\Domain\Types\PercentageOffDiscount;
use Thinktomorrow\Trader\Orders\Domain\Order;
use Thinktomorrow\Trader\Tests\Discounts\ShippingDiscountTypeKey;

class ShippingDiscountDummy extends PercentageOffDiscount
{
    public function discountBasePrice(Order $order, EligibleForDiscount $eligibleForDiscount): Money
    {
        return $eligibleForDiscount->discountBasePrice();
   }

    public function getType(): string
    {
        return DiscountTypeKey::fromDiscount($this);
    }
}