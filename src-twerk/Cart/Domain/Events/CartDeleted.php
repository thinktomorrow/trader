<?php

namespace Thinktomorrow\Trader\Cart\Domain\Events;

use Thinktomorrow\Trader\Order\Domain\OrderReference;

class CartDeleted
{
    public OrderReference $orderReference;

    public function __construct(OrderReference $orderReference)
    {
        $this->orderReference = $orderReference;
    }
}
