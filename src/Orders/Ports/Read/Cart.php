<?php

namespace Thinktomorrow\Trader\Orders\Ports\Read;

use Thinktomorrow\Trader\Common\Domain\Price\Cash;
use Thinktomorrow\Trader\Discounts\Domain\AppliedDiscountCollection;
use Thinktomorrow\Trader\Orders\Domain\Order;
use Thinktomorrow\Trader\Orders\Domain\Read\Cart as CartContract;

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

    public function id(): string
    {
        return $this->order->id()->get();
    }

    public function reference(): string
    {
        return $this->order->hasReference() ? $this->order->reference() : '';
    }

    public function isBusiness(): bool
    {
        return $this->order->isBusiness();
    }

    public function empty(): bool
    {
        return empty($this->items());
    }

    public function size(): int
    {
        return count($this->items());
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
        // TODO check if order applies for free shipment...
        return false;
    }

    public function hasShipping(): bool
    {
        return $this->order->shippingMethodId() && $this->order->shippingRuleId();
    }

    public function shippingMethodId(): int
    {
        return $this->order->shippingMethodId()->get();
    }

    public function shippingRuleId(): int
    {
        return $this->order->shippingRuleId()->get();
    }

    public function hasPayment(): bool
    {
        return $this->order->paymentMethodId() && $this->order->paymentRuleId();
    }

    public function paymentMethodId(): int
    {
        return $this->order->paymentMethodId()->get();
    }

    public function paymentRuleId(): int
    {
        return $this->order->paymentRuleId()->get();
    }

    public function shippingAddressId()
    {
        return $this->order->shippingAddressId();
    }

    public function billingAddressId()
    {
        return $this->order->billingAddressId();
    }

    public function subtotal(): string
    {
        return Cash::from($this->order->subtotal())->locale();
    }

    public function total(): string
    {
        return Cash::from($this->order->total())->locale();
    }

    public function tax(): string
    {
        return Cash::from($this->order->tax())->locale();
    }

    public function taxRates(): array
    {
        $taxRates = [];
        foreach ($this->order->taxRates() as $percent => $taxRate) {
            $taxRates[$percent] = [
                'percent' => $taxRate['percent']->asPercent(),
                'total'   => Cash::from($taxRate['total'])->locale(),
                'tax'     => Cash::from($taxRate['tax'])->locale(),
            ];
        }

        return $taxRates;
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
