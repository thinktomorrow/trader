<?php

namespace Thinktomorrow\Trader\Order\Domain;

interface OrderProductRepository
{
    public function getByOrder(OrderReference $orderReference): OrderProductCollection;
}
