<?php
declare(strict_types=1);

namespace Tests\Acceptance\Cart;

use Money\Money;
use Thinktomorrow\Trader\Application\Cart\Read\Cart;
use Thinktomorrow\Trader\Application\Cart\Read\CartPayment;
use Thinktomorrow\Trader\Application\Cart\Read\CartShipping;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Product\Personalisation\PersonalisationType;

class CartReadTest extends CartContext
{
    /** @test */
    public function in_order_to_know_how_much_to_pay_as_a_visitor__i_need_to_be_able_to_see_the_cart_totals()
    {
        $this->givenThereIsAProductWhichCostsEur('aaa', 5);
        $this->whenIAddTheVariantToTheCart('aaa-123', 2);

        $cart = $this->cartRepository->findCart(OrderId::fromString('xxx'));

        $this->assertEquals('€ 10', $cart->getTotalPrice());
        $this->assertEquals('€ 10', $cart->getSubtotalPrice());
        $this->assertEquals('€ 1,67', $cart->getTaxPrice()); // tax is 20%
        $this->assertNull($cart->getPaymentCost());
        $this->assertNull($cart->getShippingCost());
        $this->assertNull($cart->getDiscountPrice());
        $this->assertEquals(Money::EUR(1000), $cart->getTotalPriceAsMoney());
        $this->assertEquals(Money::EUR(1000), $cart->getSubtotalPriceAsMoney());
        $this->assertEquals(Money::EUR(0), $cart->getShippingCostAsMoney());
        $this->assertEquals(Money::EUR(0), $cart->getPaymentCostAsMoney());
        $this->assertEquals(Money::EUR(0), $cart->getDiscountPriceAsMoney());
        $this->assertEquals(Money::EUR(167), $cart->getTaxPriceAsMoney());

        $this->assertEquals(1, $cart->getSize());
        $this->assertEquals(2, $cart->getQuantity());
    }

    public function test_it_can_get_totals()
    {
        $this->markTestSkipped('todo: with payment / shipping / discount values');
    }


    /** @test */
    public function in_order_to_confirm_my_product_choice_as_a_visitor__i_need_to_be_able_to_see_each_line_of_my_cart()
    {
        $this->orderRepository->setNextLineReference('foobar');

        $this->givenThereIsAProductWhichCostsEur('lightsaber', 5);
        $this->whenIAddTheVariantToTheCart('lightsaber-123', 2);

        $cart = $this->cartRepository->findCart(OrderId::fromString('xxx'));

        // Line
        $this->assertInstanceOf(Cart::class, $cart);
        $this->assertCount(1, $cart->getLines());
        $line = $cart->getLines()[0];

        $this->assertStringStartsWith('foobar', $line->getLineId());
        $this->assertEquals('€ 5', $line->getLinePrice());
        $this->assertEquals('€ 10', $line->getTotalPrice());
        $this->assertEquals('€ 10', $line->getSubtotalPrice());
        $this->assertEquals('€ 1,67', $line->getTaxPrice()); // tax is 20%
        $this->assertEquals(2, $line->getQuantity());
        $this->assertCount(0, $line->getImages());
        $this->assertEquals('lightsaber variant', $line->getTitle());
        $this->assertNull($line->getDescription());
        $this->assertCount(0, $line->getDiscounts());
    }

    /** @test */
    public function it_can_show_line_prices_without_tax_for_business_accounts()
    {
        $this->givenThereIsAProductWhichCostsEur('lightsaber', 5);
        $this->whenIAddTheVariantToTheCart('lightsaber-123', 2);

        $cart = $this->cartRepository->findCart(OrderId::fromString('xxx'));

        $this->assertEquals(Money::EUR(1000), $cart->getTotalPriceAsMoney());
        $this->assertEquals(Money::EUR(1000), $cart->getSubtotalPriceAsMoney());
        $this->assertEquals(Money::EUR(833), $cart->getTotalPriceAsMoney(false));
        $this->assertEquals(Money::EUR(833), $cart->getSubtotalPriceAsMoney(false));

        $line = $cart->getLines()[0];

        $line->includeTax(false);
        $this->assertEquals('€ 4,17', $line->getLinePrice()); // 4,1666666
        $this->assertEquals('€ 8,33', $line->getTotalPrice()); // 8,333333
        $this->assertEquals('€ 8,33', $line->getSubtotalPrice());
        $this->assertEquals('€ 1,67', $line->getTaxPrice()); // tax is 20%

        $line->includeTax();
        $this->assertEquals('€ 5', $line->getLinePrice());
        $this->assertEquals('€ 10', $line->getTotalPrice());
        $this->assertEquals('€ 10', $line->getSubtotalPrice());
        $this->assertEquals('€ 1,67', $line->getTaxPrice()); // tax is 20%
    }

    /** @test */
    public function it_can_see_shipping_address()
    {
        $this->givenThereIsAProductWhichCostsEur('lightsaber', 5);
        $this->whenIAddTheVariantToTheCart('lightsaber-123', 2);
        $this->whenIAddShippingAddress('BE', 'molenstraat 146', null, '3000', 'Antwerp');

        $cart = $this->cartRepository->findCart(OrderId::fromString('xxx'));

        $this->assertEquals('BE', $cart->getShippingAddress()->getCountryId());
        $this->assertEquals('molenstraat 146', $cart->getShippingAddress()->getLine1());
        $this->assertNull($cart->getShippingAddress()->getLine2());
        $this->assertEquals('3000', $cart->getShippingAddress()->getPostalCode());
        $this->assertEquals('Antwerp', $cart->getShippingAddress()->getCity());
        $this->assertNull($cart->getShippingAddress()->getTitle());
        $this->assertNull($cart->getShippingAddress()->getDescription());
    }

    /** @test */
    public function it_can_see_billing_address()
    {
        $this->givenThereIsAProductWhichCostsEur('lightsaber', 5);
        $this->whenIAddTheVariantToTheCart('lightsaber-123', 2);
        $this->whenIAddBillingAddress('BE', 'molenstraat 146', null, '3000', 'Antwerp');

        $cart = $this->cartRepository->findCart(OrderId::fromString('xxx'));

        $this->assertEquals('BE', $cart->getBillingAddress()->getCountryId());
        $this->assertEquals('molenstraat 146', $cart->getBillingAddress()->getLine1());
        $this->assertNull($cart->getBillingAddress()->getLine2());
        $this->assertEquals('3000', $cart->getBillingAddress()->getPostalCode());
        $this->assertEquals('Antwerp', $cart->getBillingAddress()->getCity());
        $this->assertNull($cart->getBillingAddress()->getTitle());
        $this->assertNull($cart->getBillingAddress()->getDescription());
    }

    /** @test */
    public function it_can_check_if_address_equals_other_address()
    {
        $this->givenThereIsAProductWhichCostsEur('lightsaber', 5);
        $this->whenIAddTheVariantToTheCart('lightsaber-123', 2);
        $this->whenIAddShippingAddress('BE', 'molenstraat 146', null, '3000', 'Antwerp');
        $this->whenIAddBillingAddress('BE', 'molenstraat 146', null, '3000', 'Antwerp');

        $cart = $this->cartRepository->findCart(OrderId::fromString('xxx'));
        $this->assertTrue($cart->getShippingAddress()->equalsAddress($cart->getBillingAddress()));

        $this->whenIAddBillingAddress('BE', 'molenstraat 22', null, '3000', 'Antwerp');

        $cart = $this->cartRepository->findCart(OrderId::fromString('xxx'));
        $this->assertFalse($cart->getShippingAddress()->equalsAddress($cart->getBillingAddress()));
    }

    /** @test */
    public function it_can_see_shipping()
    {
        $this->givenOrderHasAShippingCountry('BE');
        $this->givenShippingCostsForAPurchaseOfEur(30, 0, 1000);
        $this->givenThereIsAProductWhichCostsEur('lightsaber', 5);
        $this->whenIAddTheVariantToTheCart('lightsaber-123', 2);
        $this->whenIChooseShipping('bpost_home');

        $cart = $this->cartRepository->findCart(OrderId::fromString('xxx'));

        $this->assertInstanceOf(CartShipping::class, $cart->getShipping());
        $this->assertEquals('shipping-123', $cart->getShipping()->getShippingId());
        $this->assertTrue($cart->getShipping()->requiresAddress());
        $this->assertEquals('bpost_home', $cart->getShipping()->getShippingProfileId());
        $this->assertEquals('€ 30', $cart->getShipping()->getCostPrice());
        $this->assertEquals('Bpost Home', $cart->getShipping()->getTitle());
    }

    /** @test */
    public function it_can_see_payment()
    {
        $this->givenPaymentMethod(30, 'bancontact');
        $this->givenThereIsAProductWhichCostsEur('lightsaber', 5);
        $this->whenIAddTheVariantToTheCart('lightsaber-123', 2);
        $this->whenIChoosePayment('bancontact');

        $cart = $this->cartRepository->findCart(OrderId::fromString('xxx'));

        $this->assertInstanceOf(CartPayment::class, $cart->getPayment());
        $this->assertEquals('payment-123', $cart->getPayment()->getPaymentId());
        $this->assertEquals('bancontact', $cart->getPayment()->getPaymentMethodId());
        $this->assertEquals('€ 30', $cart->getPayment()->getCostPrice());
    }

    /** @test */
    public function it_can_see_personalisations()
    {
        $this->givenThereIsAProductWhichCostsEur('lightsaber', 5);
        $this->givenThereIsAProductPersonalisation('lightsaber', [
            [
                'personalisation_id' => 'xxx',
                'personalisation_type' => PersonalisationType::TEXT,
                'data' => ['label' => 'label'],
            ],
        ]);
        $this->whenIAddTheVariantToTheCart('lightsaber-123', 1, ['foo' => 'bar'], ['xxx' => 'foobar']);
        $this->thenIShouldHaveProductInTheCart(1, 1);

        $cart = $this->cartRepository->findCart(OrderId::fromString('xxx'));

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
                'data' => ['label' => ['nl' => 'label nl' , 'en' => 'label en']],
            ],
        ]);
        $this->whenIAddTheVariantToTheCart('lightsaber-123', 1, ['foo' => 'bar'], ['xxx' => 'foobar']);

        $cart = $this->cartRepository->findCart(OrderId::fromString('xxx'));

        $personalisation = $cart->getLines()[0]->getPersonalisations()[0];
        $this->assertEquals('label nl', $personalisation->getLabel());
        $this->assertEquals('label nl', $personalisation->getLabel('nl'));
        $this->assertEquals('label en', $personalisation->getLabel('en'));
    }
}
