<?php

namespace Thinktomorrow\Trader\Tests;

use Money\Money;
use Thinktomorrow\Trader\Common\Domain\Price\Cash;
use Thinktomorrow\Trader\Common\Domain\Price\Percentage;
use Thinktomorrow\Trader\Discounts\Domain\DiscountFactory;
use Thinktomorrow\Trader\Orders\Domain\CustomerId;
use Thinktomorrow\Trader\Orders\Domain\Item;
use Thinktomorrow\Trader\Orders\Domain\Order;
use Thinktomorrow\Trader\Orders\Domain\OrderId;
use Thinktomorrow\Trader\Orders\Domain\Read\Cart;
use Thinktomorrow\Trader\Orders\Domain\Read\CartFactory;
use Thinktomorrow\Trader\Orders\Domain\Read\MerchantOrder;
use Thinktomorrow\Trader\Tests\Stubs\InMemoryContainer;
use Thinktomorrow\Trader\Tests\Stubs\PurchasableStub;

trait ShoppingHelpers
{
    /**
     * Really? Yes really
     */
    protected function dd()
    {
        die(var_dump(func_get_args()));
    }

    protected function cart(Order $order = null): Cart
    {
        if (!$order) {
            $order = $this->purchase(1);
        }

        return (new CartFactory($this->container('orderRepository'), $this->container))->create($order);
    }

    protected function merchantOrder(Order $order = null): MerchantOrder
    {
        if (!$order) {
            $order = $this->purchase(1);
        }

        return new \Thinktomorrow\Trader\Orders\Ports\Read\MerchantOrder([
            'is_business' => $order->isBusiness(),
        ]);
    }

    protected function purchase($id)
    {
        $order = new Order(OrderId::fromInteger($id));
        $order->setReference('foobar');
        $order->setCustomerId(CustomerId::fromString(2));
        $order->items()->add(Item::fromPurchasable(new PurchasableStub(1, [], Cash::make(505), Percentage::fromPercent(10))));
        $order->items()->add(Item::fromPurchasable(new PurchasableStub(2, [], Cash::make(1000), Percentage::fromPercent(10), Cash::make(800))), 2);
        $order->setShippingTotal(Cash::make(15));
        $order->setPaymentTotal(Cash::make(10));

        $discount = (new DiscountFactory(new InMemoryContainer()))->create(1, 'percentage_off', [], ['percentage' => Percentage::fromPercent(30)]);
        $discount->apply($order);

        $order->setTaxPercentage(Percentage::fromPercent(21));

        $this->container('orderRepository')->add($order);

        return $order;
    }

    protected function makeOrder($subtotalAmount = 0, $id = 2)
    {
        $order = new Order(OrderId::fromInteger($id));
        $order->setCustomerId(CustomerId::fromString(22));

        if ($subtotalAmount > 0) {
            $order->items()->add(Item::fromPurchasable(new PurchasableStub(20, [], Money::EUR($subtotalAmount))));
        }

        return $order;
    }

    protected function makeDiscount($id = 1, $type = 'percentage_off', $conditions = [], $adjusters = [])
    {
        if (empty($adjusters) && $type == 'percentage_off') {
            $adjusters = [
                'percentage' => Percentage::fromPercent(10),
            ];
        }

        return (new DiscountFactory(new InMemoryContainer()))->create($id, $type, $conditions, $adjusters);
    }

    protected function makePercentageOffDiscount($percent = 10)
    {
        $adjusters = [
            'percentage' => Percentage::fromPercent($percent),
        ];

        return $this->makeDiscount(1, 'percentage_off', [], $adjusters);
    }
}
