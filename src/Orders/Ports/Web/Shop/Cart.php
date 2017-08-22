<?php

namespace Thinktomorrow\Trader\Orders\Ports\Web\Shop;

use Thinktomorrow\Trader\Common\Domain\Price\Cash;
use Thinktomorrow\Trader\Discounts\Domain\AppliedDiscountCollection;
use Thinktomorrow\Trader\Orders\Domain\Order;

/**
 * Cart data object for read-only usage in views
 * Order presenter for shopper.
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
        return (new Cash())->locale($this->order->subtotal());
    }

    public function total(): string
    {
        return (new Cash())->locale($this->order->total());
    }

    public function items(): array
    {
        $collection = [];

        foreach ($this->order->items() as $id => $item) {
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
}
