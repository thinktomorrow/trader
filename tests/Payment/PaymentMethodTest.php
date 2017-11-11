<?php

namespace Thinktomorrow\Trader\Tests\Payment;

use InvalidArgumentException;
use Money\Money;
use Thinktomorrow\Trader\Orders\Domain\Item;
use Thinktomorrow\Trader\Payment\Domain\PaymentMethod;
use Thinktomorrow\Trader\Payment\Domain\PaymentMethodId;
use Thinktomorrow\Trader\Payment\Domain\PaymentRuleFactory;
use Thinktomorrow\Trader\Tests\Stubs\InMemoryContainer;
use Thinktomorrow\Trader\Tests\Stubs\PurchasableStub;
use Thinktomorrow\Trader\Tests\UnitTestCase;

class PaymentMethodTest extends UnitTestCase
{
    /** @test */
    public function it_has_an_aggregate_id()
    {
        $id = PaymentMethodId::fromInteger(2);

        $this->assertEquals(2, $id->get());
        $this->assertEquals($id, PaymentMethodId::fromInteger(2));
    }

    /** @test */
    public function it_has_a_code_reference()
    {
        $method = new PaymentMethod(PaymentMethodId::fromInteger(2), 'foobar');

        $this->assertEquals('foobar', $method->code());
    }

    /** @test */
    public function without_rules_a_method_is_always_applicable_to_an_order()
    {
        $method = new PaymentMethod(PaymentMethodId::fromInteger(2), 'foobar');

        $this->assertTrue($method->applicable($this->makeOrder()));
    }

    /** @test */
    public function it_only_accepts_collection_with_valid_paymentrule_instances()
    {
        $this->expectException(InvalidArgumentException::class);

        new PaymentMethod(PaymentMethodId::fromInteger(2), 'bazz', ['foobar']);
    }

    /** @test */
    public function payment_method_can_be_applied_if_one_of_the_rules_match_the_order()
    {
        $order = $this->makeOrder();
        $order->items()->add(Item::fromPurchasable(new PurchasableStub(1, [], Money::EUR(31))));

        $method = new PaymentMethod(PaymentMethodId::fromInteger(2), 'foobar', [
            (new PaymentRuleFactory(new InMemoryContainer()))->create(1, [
                'minimum_amount' => Money::EUR(30),
            ], [
                'amount' => Money::EUR(0),
            ]),
        ]);

        $this->assertTrue($method->applicable($order));
    }

    /** @test */
    public function payment_method_cannot_be_applied_if_none_of_the_rules_match_the_order()
    {
        $order = $this->makeOrder();
        $order->items()->add(Item::fromPurchasable(new PurchasableStub(1, [], Money::EUR(10))));

        $method = new PaymentMethod(PaymentMethodId::fromInteger(2), 'foobar', [
            (new PaymentRuleFactory(new InMemoryContainer()))->create(1, [
                'minimum_amount' => Money::EUR(30),
            ], [
                'amount' => Money::EUR(0),
            ]),
        ]);

        $this->assertFalse($method->applicable($order));
    }
}
