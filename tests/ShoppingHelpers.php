<?php

namespace Thinktomorrow\Trader\Tests;

use Money\Money;
use Thinktomorrow\Trader\Common\Adjusters\Amount;
use Thinktomorrow\Trader\Common\Adjusters\Percentage;
use Thinktomorrow\Trader\Common\Price\Percentage as PercentageValue;
use Thinktomorrow\Trader\Common\Price\Cash;
use Thinktomorrow\Trader\Discounts\Domain\Conditions\ConditionKey as DiscountConditionKey;
use Thinktomorrow\Trader\Sales\Domain\Conditions\ConditionKey as SaleConditionKey;
use Thinktomorrow\Trader\Discounts\Domain\DiscountFactory;
use Thinktomorrow\Trader\Discounts\Domain\DiscountId;
use Thinktomorrow\Trader\Discounts\Domain\Types\FixedAmountOffDiscount;
use Thinktomorrow\Trader\Discounts\Domain\Types\PercentageOffDiscount;
use Thinktomorrow\Trader\Orders\Domain\CustomerId;
use Thinktomorrow\Trader\Orders\Domain\Item;
use Thinktomorrow\Trader\Orders\Domain\ItemId;
use Thinktomorrow\Trader\Orders\Domain\Order;
use Thinktomorrow\Trader\Orders\Domain\OrderId;
use Thinktomorrow\Trader\Sales\Domain\SaleId;
use Thinktomorrow\Trader\Sales\Domain\Types\FixedAmountOffSale;
use Thinktomorrow\Trader\Sales\Domain\Types\FixedAmountSale;
use Thinktomorrow\Trader\Sales\Domain\Types\FixedCustomAmountSale;
use Thinktomorrow\Trader\Sales\Domain\Types\PercentageOffSale;
use Thinktomorrow\Trader\Tests\Stubs\EligibleForSaleStub;
use Thinktomorrow\Trader\Tests\Stubs\InMemoryContainer;
use Thinktomorrow\Trader\Tests\Stubs\PurchasableStub;

trait ShoppingHelpers
{
    /**
     * Really? Yes really.
     */
    protected function dd()
    {
        die(var_dump(func_get_args()));
    }

    protected function getItem($itemId = null, $taxRate = null, $purchasable = null): Item
    {
        if(!$itemId) $itemId = ItemId::fromInteger(1);
        if(!$taxRate) $taxRate = PercentageValue::fromPercent(21);
        if(!$purchasable) $purchasable = new PurchasableStub(20, [], Money::EUR(110));

        return new Item($itemId, $taxRate, $purchasable);
    }

    protected function getOrder(Order $order = null): Order
    {
        if (!$order) {
            $order = $this->purchase(1);
        }

        return $order;
    }

    protected function getCleanOrder(): Order
    {
        return new Order(OrderId::fromInteger(20));
    }

    protected function purchase($id)
    {
        $order = new Order(OrderId::fromInteger($id));
        $order->setReference('foobar');
        $order->setCustomerId(CustomerId::fromString(2));
        $order->items()->add($this->getItem(null, null, new PurchasableStub(1, [], Cash::make(505), PercentageValue::fromPercent(10))));
        $order->items()->add($this->getItem(null, null, new PurchasableStub(2, [], Cash::make(1000), PercentageValue::fromPercent(10), Cash::make(800))), 2);
        $order->setShippingTotal(Cash::make(15));
        $order->setPaymentTotal(Cash::make(10));

        $discount = (new DiscountFactory(new InMemoryContainer()))->create(1, 'percentage_off', [], ['percentage' => PercentageValue::fromPercent(30)]);
        $discount->apply($order);

        $order->setTaxPercentage(PercentageValue::fromPercent(21));

        $this->container('orderRepository')->add($order);

        return $order;
    }

    protected function makeOrder($subtotalAmount = 0, $id = 2)
    {
        $order = new Order(OrderId::fromInteger($id));
        $order->setCustomerId(CustomerId::fromString(22));

        if ($subtotalAmount > 0) {
            $order->items()->add($this->getItem(null, null, new PurchasableStub(20, [], Money::EUR($subtotalAmount))));
        }

        return $order;
    }

    /**
     * @return array
     */
    protected function prepOrderWithItem($itemPrice = 100, $salePrice = 0): array
    {
        $purchasable = new PurchasableStub(1, [], Money::EUR($itemPrice), PercentageValue::fromPercent(10), Money::EUR($salePrice));
        $item = $this->getItem(null, null, $purchasable);

        $order = $this->makeOrder();
        $order->items()->add($item);

        return array($order, $item);
    }

    protected function makeEligibleForSaleStub($amount = 100)
    {
        $price = Money::EUR($amount);

        return new EligibleForSaleStub(1, [], $price);
    }

    protected function makePercentageOffSale($percent, $conditions = [], array $data = [])
    {
        if(!empty($conditions))
        {
            foreach($conditions as $type => $parameters){
                $class = SaleConditionKey::fromString($type)->class();
                $conditions[$type] = (new $class())->setParameters([$type => $parameters]);
            };
        }

        return new PercentageOffSale(
            SaleId::fromInteger(1),
            $conditions,
            (new Percentage())->setParameters(PercentageValue::fromPercent($percent)),
            $data
        );
    }

    protected function makeFixedAmountOffSale($amount, array $data = [])
    {
        return new FixedAmountOffSale(
            SaleId::fromInteger(1),
            [],
            (new Amount())->setParameters(Money::EUR($amount)),
            $data
        );
    }

    protected function makeFixedAmountSale($amount, array $data = [])
    {
        return new FixedAmountSale(
            SaleId::fromInteger(1),
            [],
            (new Amount())->setParameters(Money::EUR($amount)),
            $data
        );
    }

    protected function makeFixedCustomAmountSale(array $data = [])
    {
        return new FixedCustomAmountSale(SaleId::fromInteger(1), [], (new Amount())->setParameters(Money::EUR(10)), $data);
    }

    protected function makePercentageOffDiscount($percent = 10, $conditions = [], $data = [], $id = null)
    {
        if(!empty($conditions))
        {
            foreach($conditions as $type => $parameters){
                $class = DiscountConditionKey::fromString($type)->class();
                $conditions[$type] = (new $class())->setParameters([$type => $parameters]);
            };
        }

        return new PercentageOffDiscount(
            DiscountId::fromInteger($id ?? rand(1,99)),
            $conditions,
            (new Percentage())->setRawParameters($percent),
            $data
        );
    }

    protected function makeFixedAmountOffDiscount($amount, $conditions = [], $data = [], $id = null)
    {
        if(!empty($conditions))
        {
            foreach($conditions as $type => $parameters){
                $class = DiscountConditionKey::fromString($type)->class();
                $conditions[$type] = (new $class())->setParameters([$type => $parameters]);
            };
        }

        return new FixedAmountOffDiscount(
            DiscountId::fromInteger($id ?? rand(1,99)),
            $conditions,
            (new Amount())->setParameters(Money::EUR($amount)),
            $data
        );
    }
}
