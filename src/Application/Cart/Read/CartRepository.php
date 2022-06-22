<?php

namespace Thinktomorrow\Trader\Application\Cart\Read;

use Thinktomorrow\Trader\Domain\Model\Order\OrderId;

interface CartRepository
{
    public function findCart(OrderId $orderId): Cart;

    public function existsCart(OrderId $orderId): bool;
}
