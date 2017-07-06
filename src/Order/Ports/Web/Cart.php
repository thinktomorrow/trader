<?php

namespace Thinktomorrow\Trader\Order\Ports\Web;

use Thinktomorrow\Trader\Discounts\Domain\AppliedDiscountCollection;
use Thinktomorrow\Trader\Order\Ports\Web\CartItem;
use Thinktomorrow\Trader\Order\Domain\Order;
use Thinktomorrow\Trader\Common\Domain\Price\MoneyRender;

/**
 * Cart data object for read-only usage in views
 */
class Cart
{
    /**
     * @var Order
     */
    private $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function subtotal(): string
    {
        return (new MoneyRender())->locale($this->order->subtotal());
    }

    public function total(): string
    {
        return (new MoneyRender())->locale($this->order->total());
    }

    public function items(): array
    {
        $collection = [];

        foreach($this->order->items() as $id => $item)
        {
            $collection[$id] = new CartItem($item);
        }

        return $collection;
    }

    public function discounts(): AppliedDiscountCollection
    {
        return $this->order->discounts();
    }

    public function shipment()
    {
        return $this->order->shipmentMethodId();
    }

    public function freeShipment(): bool
    {
        // TODO
        return false;
    }

    public function __call($method, $params)
    {
        return $this->order->items()->{$method}(...$params);
    }
}