<?php

namespace Thinktomorrow\Trader\TestsOld;

use InvalidArgumentException;
use Money\Money;
use Thinktomorrow\Trader\Shipment\Domain\ShippingMethod;
use Thinktomorrow\Trader\Shipment\Domain\ShippingMethodId;
use Thinktomorrow\Trader\Shipment\Domain\ShippingRuleFactory;
use Thinktomorrow\Trader\TestsOld\Stubs\InMemoryContainer;
use Thinktomorrow\Trader\TestsOld\Stubs\PurchasableStub;

class ShippingMethodTest extends TestCase
{
    /** @test */
    public function it_has_an_aggregate_id()
    {
        $id = ShippingMethodId::fromInteger(2);

        $this->assertEquals(2, $id->get());
        $this->assertEquals($id, ShippingMethodId::fromInteger(2));
    }

    /** @test */
    public function it_has_a_code_reference()
    {
        $method = new ShippingMethod(ShippingMethodId::fromInteger(2), 'foobar');

        $this->assertEquals('foobar', $method->code());
    }

    /** @test */
    public function without_rules_a_method_is_not_applicable_to_an_order()
    {
        $method = new ShippingMethod(ShippingMethodId::fromInteger(2), 'foobar');

        $this->assertFalse($method->applicable($this->makeOrder()));
    }

    /** @test */
    public function it_only_accepts_collection_with_valid_shippingrule_instances()
    {
        $this->expectException(InvalidArgumentException::class);

        new ShippingMethod(ShippingMethodId::fromInteger(2), 'bazz', ['foobar']);
    }

    /** @test */
    public function shipping_method_can_be_applied_if_one_of_the_rules_match_the_order()
    {
        $order = $this->makeOrder();
        $order->items()->add($this->getItem(null, null, new PurchasableStub(1, [], Money::EUR(31))));

        $method = new ShippingMethod(ShippingMethodId::fromInteger(2), 'foobar', [
            (new ShippingRuleFactory(new InMemoryContainer()))->create(1, [
                'minimum_amount' => Money::EUR(30),
            ], [
                'amount' => Money::EUR(0),
            ]),
        ]);

        $this->assertTrue($method->applicable($order));
    }

    /** @test */
    public function shipping_method_cannot_be_applied_if_none_of_the_rules_match_the_order()
    {
        $order = $this->makeOrder();
        $order->items()->add($this->getItem(null, null, new PurchasableStub(1, [], Money::EUR(10))));

        $method = new ShippingMethod(ShippingMethodId::fromInteger(2), 'foobar', [
            (new ShippingRuleFactory(new InMemoryContainer()))->create(1, [
                'minimum_amount' => Money::EUR(30),
            ], [
                'amount' => Money::EUR(0),
            ]),
        ]);

        $this->assertFalse($method->applicable($order));
    }
}
