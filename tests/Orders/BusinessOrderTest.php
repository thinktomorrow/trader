<?php

namespace Thinktomorrow\Trader\Tests;

use Thinktomorrow\Trader\Orders\Domain\Order;
use Thinktomorrow\Trader\Orders\Domain\OrderId;
use Thinktomorrow\Trader\Orders\Domain\Read\CartFactory;

class BusinessOrderTest extends FeatureTestCase
{
    /** @test */
    public function order_is_not_a_business_one_by_default()
    {
        $this->assertFalse((new Order(OrderId::fromInteger(1)))->isBusiness());
    }

    /** @test */
    public function order_can_be_flagged_as_a_business_order()
    {
        $order = new Order(OrderId::fromInteger(1));
        $order->setBusiness();

        $this->assertTrue($order->isBusiness());
    }

    /** @test */
    public function merchantorder_and_cart_reflect_the_business_flag()
    {
        $order = new Order(OrderId::fromInteger(1));
        $this->assertFalse($this->cart($order)->isBusiness());
        $this->assertFalse($this->merchantOrder($order)->isBusiness());

        $order->setBusiness();
        $this->assertTrue($this->cart($order)->isBusiness());
        $this->assertTrue($this->merchantOrder($order)->isBusiness());
    }
}
