<?php

namespace Thinktomorrow\Trader\Discounts\Domain;

use Thinktomorrow\Trader\Orders\Domain\ItemId;
use Thinktomorrow\Trader\Orders\Domain\Order;

interface ItemDiscount
{
    public function applicable(Order $order, ItemId $itemId): bool;
}
