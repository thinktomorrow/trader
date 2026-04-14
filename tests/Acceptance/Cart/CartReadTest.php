<?php

declare(strict_types=1);

namespace Tests\Acceptance\Cart;

use Money\Money;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Product\Personalisation\PersonalisationType;

class CartReadTest extends CartContext
{
    public function test_in_order_to_know_how_much_to_pay_as_a_visitor_i_need_to_be_able_to_see_the_cart_totals()
    {
        $this->givenThereIsAProductWhichCostsEur('aaa', 5);
        $this->whenIAddTheVariantToTheCart('aaa-variant-aaa', 2);

        $this->refreshCart();

        $cart = $this->orderContext->repos()->cartRepository()->findCart(OrderId::fromString('xxx'));

        $this->assertEquals(Money::EUR(1000), $cart->getTotalExcl());
        $this->assertEquals(Money::EUR(1200), $cart->getTotalIncl());
        $this->assertEquals(Money::EUR(1000), $cart->getSubtotalExcl());
        $this->assertEquals(Money::EUR(1200), $cart->getSubtotalIncl());
        $this->assertEquals(Money::EUR(0), $cart->getShippingCostExcl());
        $this->assertEquals(Money::EUR(0), $cart->getShippingCostIncl());
        $this->assertEquals(Money::EUR(0), $cart->getPaymentCostExcl());
        $this->assertEquals(Money::EUR(0), $cart->getPaymentCostIncl());
        $this->assertEquals(Money::EUR(0), $cart->getDiscountTotalExcl());
        $this->assertEquals(Money::EUR(0), $cart->getDiscountTotalIncl());
        $this->assertEquals(Money::EUR(200), $cart->getTotalVat());

        $this->assertEquals('€ 10', $cart->getFormattedTotalExcl());
        $this->assertEquals('€ 12', $cart->getFormattedTotalIncl());
        $this->assertEquals('€ 10', $cart->getFormattedSubtotalExcl());
        $this->assertEquals('€ 12', $cart->getFormattedSubtotalIncl());
        $this->assertEquals('€ 2', $cart->getFormattedTotalVat()); // tax is 20%
        $this->assertEquals('€ 0', $cart->getFormattedPaymentCostExcl());
        $this->assertEquals('€ 0', $cart->getFormattedPaymentCostIncl());
        $this->assertEquals('€ 0', $cart->getFormattedShippingCostExcl());
        $this->assertEquals('€ 0', $cart->getFormattedShippingCostIncl());
        $this->assertEquals('€ 0', $cart->getFormattedDiscountTotalExcl());
        $this->assertEquals('€ 0', $cart->getFormattedDiscountTotalIncl());

        $this->assertEquals(1, $cart->getSize());
        $this->assertEquals(2, $cart->getQuantity());
    }

    public function test_it_can_get_totals()
    {
        $this->markTestSkipped('todo: with payment / shipping / discount values');
    }

    public function test_in_order_to_confirm_my_product_choice_as_a_visitor__i_need_to_be_able_to_see_each_line_of_my_cart()
    {
        $this->orderContext->repos()->orderRepository()->setNextLineReference('foobar');

        $this->givenThereIsAProductWhichCostsEur('lightsaber', 5);
        $this->whenIAddTheVariantToTheCart('lightsaber-variant-aaa', 2);

        $this->refreshCart();

        $cart = $this->orderContext->repos()->cartRepository()->findCart(OrderId::fromString('xxx'));

        // Line
        $this->assertCount(1, $cart->getLines());
        $line = $cart->getLines()[0];

        $this->assertEquals('€ 10', $line->getFormattedTotalPriceExcl());
        $this->assertEquals('20', $line->getFormattedVatRate());
        $this->assertEquals(2, $line->getQuantity());
        $this->assertCount(0, $line->getImages());
        $this->assertEquals('lightsaber-variant-aaa title nl', $line->getTitle());
        $this->assertNull($line->getDescription());
        $this->assertCount(0, $line->getDiscounts());
    }

    public function test_it_can_see_shipping_address()
    {
        $this->givenThereIsAProductWhichCostsEur('lightsaber', 5);
        $this->whenIAddTheVariantToTheCart('lightsaber-variant-aaa', 2);
        $this->whenIAddShippingAddress('BE', 'molenstraat 146', null, '3000', 'Antwerp');

        $this->refreshCart();

        $cart = $this->orderContext->repos()->cartRepository()->findCart(OrderId::fromString('xxx'));

        $this->assertEquals('BE', $cart->getShippingAddress()->getCountryId());
        $this->assertEquals('molenstraat 146', $cart->getShippingAddress()->getLine1());
        $this->assertNull($cart->getShippingAddress()->getLine2());
        $this->assertEquals('3000', $cart->getShippingAddress()->getPostalCode());
        $this->assertEquals('Antwerp', $cart->getShippingAddress()->getCity());
        $this->assertNull($cart->getShippingAddress()->getTitle());
        $this->assertNull($cart->getShippingAddress()->getDescription());
    }

    public function test_it_can_see_billing_address()
    {
        $this->givenThereIsAProductWhichCostsEur('lightsaber', 5);
        $this->whenIAddTheVariantToTheCart('lightsaber-variant-aaa', 2);
        $this->whenIAddBillingAddress('BE', 'molenstraat 146', null, '3000', 'Antwerp');

        $this->refreshCart();

        $cart = $this->orderContext->repos()->cartRepository()->findCart(OrderId::fromString('xxx'));

        $this->assertEquals('BE', $cart->getBillingAddress()->getCountryId());
        $this->assertEquals('molenstraat 146', $cart->getBillingAddress()->getLine1());
        $this->assertNull($cart->getBillingAddress()->getLine2());
        $this->assertEquals('3000', $cart->getBillingAddress()->getPostalCode());
        $this->assertEquals('Antwerp', $cart->getBillingAddress()->getCity());
        $this->assertNull($cart->getBillingAddress()->getTitle());
        $this->assertNull($cart->getBillingAddress()->getDescription());
    }

    public function test_it_can_check_if_address_equals_other_address()
    {
        $this->givenThereIsAProductWhichCostsEur('lightsaber', 5);
        $this->whenIAddTheVariantToTheCart('lightsaber-variant-aaa', 2);
        $this->whenIAddShippingAddress('BE', 'molenstraat 146', null, '3000', 'Antwerp');
        $this->whenIAddBillingAddress('BE', 'molenstraat 146', null, '3000', 'Antwerp');

        $this->refreshCart();

        $cart = $this->orderContext->repos()->cartRepository()->findCart(OrderId::fromString('xxx'));
        $this->assertTrue($cart->getShippingAddress()->equalsAddress($cart->getBillingAddress()));

        $this->whenIAddBillingAddress('BE', 'molenstraat 22', null, '3000', 'Antwerp');

        $this->refreshCart();

        $cart = $this->orderContext->repos()->cartRepository()->findCart(OrderId::fromString('xxx'));
        $this->assertFalse($cart->getShippingAddress()->equalsAddress($cart->getBillingAddress()));
    }

    public function test_it_can_see_personalisations()
    {
        $this->givenThereIsAProductWhichCostsEur('lightsaber', 5);
        $this->givenThereIsAProductPersonalisation('lightsaber', [
            [
                'personalisation_id' => 'xxx',
                'personalisation_type' => PersonalisationType::TEXT,
                'data' => ['label' => 'label'],
            ],
        ]);
        $this->whenIAddTheVariantToTheCart('lightsaber-variant-aaa', 1, ['foo' => 'bar'], ['xxx' => 'foobar']);
        $this->thenIShouldHaveProductInTheCart(1, 1);

        $this->refreshCart();
        $cart = $this->orderContext->repos()->cartRepository()->findCart(OrderId::fromString('xxx'));

        $line = $cart->getLines()[0];

        $this->assertCount(1, $line->getPersonalisations());

        $personalisation = $line->getPersonalisations()[0];
        $this->assertEquals('label', $personalisation->getLabel());
        $this->assertEquals('foobar', $personalisation->getValue());
        $this->assertEquals(PersonalisationType::TEXT, $personalisation->getType());
    }

    public function test_it_can_see_localized_personalisation_label()
    {
        $this->givenThereIsAProductWhichCostsEur('lightsaber', 5);
        $this->givenThereIsAProductPersonalisation('lightsaber', [
            [
                'personalisation_id' => 'xxx',
                'personalisation_type' => PersonalisationType::TEXT,
                'data' => ['label' => ['nl' => 'label nl', 'en' => 'label en']],
            ],
        ]);
        $this->whenIAddTheVariantToTheCart('lightsaber-variant-aaa', 1, ['foo' => 'bar'], ['xxx' => 'foobar']);

        $this->refreshCart();
        $cart = $this->orderContext->repos()->cartRepository()->findCart(OrderId::fromString('xxx'));

        $personalisation = $cart->getLines()[0]->getPersonalisations()[0];
        $this->assertEquals('label nl', $personalisation->getLabel());
        $this->assertEquals('label nl', $personalisation->getLabel('nl'));
        $this->assertEquals('label en', $personalisation->getLabel('en'));
    }
}
