<?php

namespace Thinktomorrow\Trader\Discounts\Domain;

use Thinktomorrow\Trader\Orders\Domain\Order;
use Thinktomorrow\Trader\Orders\Domain\PurchasableId;

interface ItemDiscount
{
    public function applicable(Order $order, PurchasableId $purchasableId): bool;
}
