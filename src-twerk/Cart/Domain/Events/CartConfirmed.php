<?php

namespace Thinktomorrow\Trader\Cart\Domain\Events;

use Thinktomorrow\Trader\Order\Domain\OrderReference;

class CartConfirmed
{
    public OrderReference $orderReference;

    public function __construct(OrderReference $orderReference)
    {
        $this->orderReference = $orderReference;
    }
}
