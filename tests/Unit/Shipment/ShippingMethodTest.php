<?php

namespace Thinktomorrow\Trader\Tests\Unit;

use InvalidArgumentException;
use Money\Money;
use Thinktomorrow\Trader\Order\Domain\Item;
use Thinktomorrow\Trader\Shipment\Domain\ShippingMethod;
use Thinktomorrow\Trader\Shipment\Domain\ShippingMethodId;
use Thinktomorrow\Trader\Shipment\Domain\ShippingRuleFactory;
use Thinktomorrow\Trader\Tests\InMemoryContainer;
use Thinktomorrow\Trader\Tests\Unit\Stubs\ConcretePurchasable;

class ShippingMethodTest extends UnitTestCase
{
    /** @test */
    function it_has_an_aggregate_id()
    {
        $id = ShippingMethodId::fromInteger(2);

        $this->assertEquals(2,$id->get());
        $this->assertEquals($id, ShippingMethodId::fromInteger(2));
    }

    /** @test */
    function without_rules_a_method_is_not_applicable_to_an_order()
    {
        $method = new ShippingMethod(ShippingMethodId::fromInteger(2));

        $this->assertFalse($method->applicable($this->makeOrder()));
    }

    /** @test */
    function it_only_accepts_collection_with_valid_shippingrule_instances()
    {
        $this->setExpectedException(InvalidArgumentException::class);

        new ShippingMethod(ShippingMethodId::fromInteger(2),['foobar']);
    }

    /** @test */
    function shipping_method_can_be_applied_if_one_of_the_rules_match_the_order()
    {
        $order = $this->makeOrder();
        $order->items()->add(Item::fromPurchasable(new ConcretePurchasable(1,[],Money::EUR(31))));


        $method = new ShippingMethod(ShippingMethodId::fromInteger(2),[
            (new ShippingRuleFactory(new InMemoryContainer))->create(1, [
                'minimum_amount' => Money::EUR(30)
            ],[
                'amount' => Money::EUR(0)
            ])
        ]);

        $this->assertTrue($method->applicable($order));
    }

    /** @test */
    function shipping_method_cannot_be_applied_if_none_of_the_rules_match_the_order()
    {
        $order = $this->makeOrder();
        $order->items()->add(Item::fromPurchasable(new ConcretePurchasable(1,[],Money::EUR(10))));

        $method = new ShippingMethod(ShippingMethodId::fromInteger(2),[
            (new ShippingRuleFactory(new InMemoryContainer))->create(1, [
                'minimum_amount' => Money::EUR(30)
            ],[
                'amount' => Money::EUR(0)
            ])
        ]);

        $this->assertFalse($method->applicable($order));
    }

}