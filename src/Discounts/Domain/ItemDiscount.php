<?php

namespace Thinktomorrow\Trader\Discounts\Domain;

use Thinktomorrow\Trader\Order\Domain\ItemId;
use Thinktomorrow\Trader\Order\Domain\Order;

interface ItemDiscount
{
    public function applicable(Order $order, ItemId $itemId): bool;
}
