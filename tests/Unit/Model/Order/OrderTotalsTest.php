<?php
declare(strict_types=1);

namespace Tests\Unit\Model\Order;

use Money\Money;
use Tests\Unit\TestCase;
use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters\AdjustOrderVatSnapshot;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Common\Vat\VatPercentage;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\OrderReference;
use Thinktomorrow\Trader\Domain\Model\Order\State\DefaultOrderState;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;

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
        $this->assertEquals(Cash::zero(), $order->getPaymentCostExcl());
        $this->assertEquals(Cash::zero(), $order->getDiscountTotalExcl());
        $this->assertEquals(Cash::zero(), $order->getTotalExcl());
    }

    public function test_it_can_get_calculated_totals()
    {
        $order = $this->orderContext->createDefaultOrder();

        $this->assertEquals(Money::EUR('166'), $order->getSubtotalExcl());
        $this->assertEquals(Money::EUR('200'), $order->getSubtotalIncl());
        $this->assertEquals(Money::EUR('50'), $order->getShippingCostExcl());
        $this->assertEquals(Money::EUR('50'), $order->getPaymentCostExcl());
        $this->assertEquals(Money::EUR('15'), $order->getDiscountTotalExcl());
        $this->assertEquals(Money::EUR('251'), $order->getTotalExcl());
    }

    public function test_it_can_get_totals_incl_from_snapshot()
    {
        $order = $this->orderContext->createDefaultOrder();

        (new TestContainer())->get(AdjustOrderVatSnapshot::class)->adjust($order);

        $this->assertEquals(Money::EUR('200'), $order->getSubtotalIncl());
        $this->assertEquals(Money::EUR('61'), $order->getShippingCostIncl());
        $this->assertEquals(Money::EUR('61'), $order->getPaymentCostIncl());
        $this->assertEquals(Money::EUR('18'), $order->getDiscountTotalIncl());
        $this->assertEquals(Money::EUR('54'), $order->getTotalVat());
        $this->assertEquals(Money::EUR('305'), $order->getTotalIncl());

        $vatLines = $order->getVatLines();

        $this->assertEquals(1, count($vatLines));

        $vatLine = $vatLines[21]; // Keys are vat percentages

        $this->assertEquals(Money::EUR(251), $vatLine->getTaxableBase());
        $this->assertEquals(Money::EUR(54), $vatLine->getVatAmount());
        $this->assertEquals(VatPercentage::fromString('21'), $vatLine->getVatPercentage());
    }
}
