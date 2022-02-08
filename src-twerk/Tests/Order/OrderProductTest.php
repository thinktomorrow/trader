<?php

namespace Thinktomorrow\Trader\Tests\Order;

use Thinktomorrow\Trader\Order\Ports\DefaultOrderProduct;
use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Common\Cash\Cash;
use Thinktomorrow\Trader\Common\Cash\Percentage;
use Thinktomorrow\Trader\Order\Domain\OrderReference;
use Thinktomorrow\Trader\Taxes\TaxRate;

class OrderProductTest extends TestCase
{
    /** @test */
    public function it_can_create_an_order_product()
    {
        $orderProduct = new \Thinktomorrow\Trader\Order\Ports\DefaultOrderProduct(
            "1",
            "2",
            OrderReference::fromString('xxx'),
            1,
            Cash::make(50),
            TaxRate::fromInteger(21),
            true,
            []
        );

        $this->assertSame("1", $orderProduct->getId());
        $this->assertSame("2", $orderProduct->getProductId());
        $this->assertEquals(OrderReference::fromString('xxx'), $orderProduct->getOrderReference());
        $this->assertSame(1, $orderProduct->getQuantity());
        $this->assertEquals(Cash::make(50), $orderProduct->getUnitPrice());
        $this->assertEquals(TaxRate::fromInteger(21), $orderProduct->getTaxRate());
        $this->assertTrue($orderProduct->isTaxApplicable());

        $this->assertEquals(Cash::make(50), $orderProduct->getTotal());
        $this->assertEquals(Cash::make(50), $orderProduct->getSubTotal());
        $this->assertEquals(Cash::make(50), $orderProduct->getSalesSubTotal());
        $this->assertEquals(
            Cash::make(50)->subtract(Cash::from(50)->subtractTaxPercentage(Percentage::fromInteger(21))),
            $orderProduct->getTaxTotal()
        );
        $this->assertEquals(Cash::zero(), $orderProduct->getDiscountTotal());
    }

    /** @test */
    public function it_can_change_quantity()
    {
        $orderProduct = new \Thinktomorrow\Trader\Order\Ports\DefaultOrderProduct(
            "1",
            "2",
            OrderReference::fromString('xxx'),
            1,
            Cash::make(50),
            TaxRate::fromInteger(21),
            true,
            []
        );

        $orderProduct->replaceQuantity(3);

        $this->assertEquals(Cash::make(150), $orderProduct->getTotal());
        $this->assertEquals(Cash::make(150), $orderProduct->getSubTotal());
        $this->assertEquals(Cash::make(150), $orderProduct->getSalesSubTotal());
        $this->assertEquals(
            Cash::make(150)->subtract(Cash::from(150)->subtractTaxPercentage(Percentage::fromInteger(21))),
            $orderProduct->getTaxTotal()
        );
    }

    /** @test */
    public function it_can_apply_a_discount()
    {
        $this->markTestIncomplete();

        $orderProduct = new DefaultOrderProduct(
            "1",
            "2",
            OrderReference::fromString('xxx'),
            1,
            Cash::make(50),
            TaxRate::fromInteger(21),
            true,
            []
        );

        $orderProduct->addDiscount();

        $this->assertEquals(Cash::make(150), $orderProduct->getTotal());
        $this->assertEquals(Cash::make(150), $orderProduct->getSubTotal());
        $this->assertEquals(Cash::make(150), $orderProduct->getSalesSubTotal());
        $this->assertEquals(
            Cash::make(150)->subtract(Cash::from(150)->subtractTaxPercentage(Percentage::fromInteger(21))),
            $orderProduct->getTaxTotal()
        );
    }
}
