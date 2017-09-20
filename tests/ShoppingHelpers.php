<?php

namespace Thinktomorrow\Trader\Tests;

use Thinktomorrow\Trader\Common\Domain\Price\Cash;
use Thinktomorrow\Trader\Common\Domain\Price\Percentage;
use Thinktomorrow\Trader\Discounts\Domain\DiscountFactory;
use Thinktomorrow\Trader\Orders\Domain\CustomerId;
use Thinktomorrow\Trader\Orders\Domain\Item;
use Thinktomorrow\Trader\Orders\Domain\Order;
use Thinktomorrow\Trader\Orders\Domain\OrderId;
use Thinktomorrow\Trader\Tests\Stubs\PurchasableStub;

trait ShoppingHelpers
{
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
}