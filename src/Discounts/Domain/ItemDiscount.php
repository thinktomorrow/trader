<?php

namespace Thinktomorrow\Trader\Discounts\Domain;

use Thinktomorrow\Trader\EligibleForDiscount;
use Thinktomorrow\Trader\Orders\Domain\Order;

interface ItemDiscountOLD
{
    public function applicable(Order $order, EligibleForDiscount $eligibleForDiscount): bool;
}
