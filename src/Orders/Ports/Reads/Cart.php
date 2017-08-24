<?php

namespace Thinktomorrow\Trader\Orders\Ports\Reads;

use Thinktomorrow\Trader\Common\Domain\Price\Cash;
use Thinktomorrow\Trader\Discounts\Domain\AppliedDiscountCollection;
use Thinktomorrow\Trader\Orders\Application\Reads\Cart\Cart as CartContract;
use Thinktomorrow\Trader\Orders\Domain\Order;

/**
 * Cart data object for read-only usage in views
 * Order presenter for shopper.
 */
class Cart implements CartContract
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
        return Cash::from($this->order->subtotal())->locale();
    }

    public function total(): string
    {
        return Cash::from($this->order->total())->locale();
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

    public function freeShipment(): bool
    {
        // TODO
        return false;
    }

    public function shippingMethodId(): int
    {
        return $this->order->shippingMethodId();
    }

    public function shippingRuleId(): int
    {
        return $this->order->shippingRuleId();
    }

    public function tax(): string
    {
        return Cash::from($this->order->tax())->locale();
    }

    public function taxRates(): array
    {
        // TODO: Implement taxRates() method.
    }

    public function discountTotal(): string
    {
        return Cash::from($this->order->discountTotal())->locale();
    }

    public function shippingTotal(): string
    {
        return Cash::from($this->order->shippingTotal())->locale();
    }

    public function paymentTotal(): string
    {
        return Cash::from($this->order->paymentTotal())->locale();
    }
}
