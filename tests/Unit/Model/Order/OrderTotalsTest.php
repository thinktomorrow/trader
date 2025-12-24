<?php
declare(strict_types=1);

namespace Tests\Unit\Model\Order;

use Money\Money;
use Tests\Unit\TestCase;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\OrderReference;
use Thinktomorrow\Trader\Domain\Model\Order\State\DefaultOrderState;

class OrderTotalsTest extends TestCase
{
    public function test_it_can_get_default_totals()
    {
        $order = Order::create(
            OrderId::fromString('order-aaa'),
            OrderReference::fromString('ORDER-0001'),
            DefaultOrderState::confirmed,
        );

        $this->assertEquals(Cash::zero(), $order->getSubtotalExcl());
        $this->assertEquals(Cash::zero(), $order->getSubtotalIncl());
        $this->assertEquals(Cash::zero(), $order->getShippingCostExcl());
        $this->assertEquals(Cash::zero(), $order->getShippingCostIncl());
        $this->assertEquals(Cash::zero(), $order->getPaymentCostExcl());
        $this->assertEquals(Cash::zero(), $order->getPaymentCostIncl());
        $this->assertEquals(Cash::zero(), $order->getDiscountTotalExcl());
        $this->assertEquals(Cash::zero(), $order->getDiscountTotalIncl());
        $this->assertEquals(Cash::zero(), $order->getTotalExcl());
        $this->assertEquals(Cash::zero(), $order->getTotalVat());
        $this->assertEquals(Cash::zero(), $order->getTotalIncl());
    }

    public function test_it_can_get_persisted_totals()
    {
        $order = $this->orderContext->createDefaultOrder();

        $this->assertEquals(Money::EUR('82500'), $order->getSubtotalExcl());
        $this->assertEquals(Money::EUR('100000'), $order->getSubtotalIncl());
        $this->assertEquals(Money::EUR('4132'), $order->getShippingCostExcl());
        $this->assertEquals(Money::EUR('5000'), $order->getShippingCostIncl());
        $this->assertEquals(Money::EUR('1653'), $order->getPaymentCostExcl());
        $this->assertEquals(Money::EUR('2000'), $order->getPaymentCostIncl());
        $this->assertEquals(Money::EUR('5785'), $order->getDiscountTotalExcl());
        $this->assertEquals(Money::EUR('7000'), $order->getDiscountTotalIncl());
        $this->assertEquals(Money::EUR('82500'), $order->getTotalExcl());
        $this->assertEquals(Money::EUR('17500'), $order->getTotalVat());
        $this->assertEquals(Money::EUR('100000'), $order->getTotalIncl());
    }
}
