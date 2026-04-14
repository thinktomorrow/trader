<?php

declare(strict_types=1);

namespace Acceptance\Cart;

use Money\Money;
use Tests\Acceptance\Cart\CartContext;
use Thinktomorrow\Trader\Application\Cart\Read\CartPayment;
use Thinktomorrow\Trader\Application\Cart\Read\CartShipping;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;

class CartServicesTest extends CartContext
{
    public function test_it_can_see_shipping()
    {
        $this->givenOrderHasAShippingCountry('BE');
        $this->givenShippingCostsForAPurchaseOfEur(30, 0, 1000);
        $this->givenThereIsAProductWhichCostsEur('lightsaber', 5);
        $this->whenIAddTheVariantToTheCart('lightsaber-variant-aaa', 2);
        $this->whenIChooseShipping('bpost_home');

        $this->refreshCart();

        $cart = $this->orderContext->repos()->cartRepository()->findCart(OrderId::fromString('xxx'));

        $this->assertInstanceOf(CartShipping::class, $cart->getShipping());
        $this->assertEquals('shipping-123', $cart->getShipping()->getShippingId());
        $this->assertTrue($cart->getShipping()->requiresAddress());
        $this->assertEquals('bpost_home', $cart->getShipping()->getShippingProfileId());
        $this->assertEquals(Money::EUR('3000'), $cart->getShipping()->getCostPriceExcl());
        $this->assertEquals('Bpost Home', $cart->getShipping()->getTitle());
    }

    public function test_it_can_see_payment()
    {
        $this->givenPaymentMethod(30, 'bancontact');
        $this->givenThereIsAProductWhichCostsEur('lightsaber', 5);
        $this->whenIAddTheVariantToTheCart('lightsaber-variant-aaa', 2);
        $this->whenIChoosePayment('bancontact');

        $this->refreshCart();
        $cart = $this->orderContext->repos()->cartRepository()->findCart(OrderId::fromString('xxx'));

        $this->assertInstanceOf(CartPayment::class, $cart->getPayment());
        $this->assertEquals('payment-123', $cart->getPayment()->getPaymentId());
        $this->assertEquals('bancontact', $cart->getPayment()->getPaymentMethodId());
        $this->assertEquals(Money::EUR('3000'), $cart->getPayment()->getCostPriceExcl());
    }

    public function test_it_can_see_shipping_prices()
    {
        $this->givenOrderHasAShippingCountry('BE');
        $this->givenShippingCostsForAPurchaseOfEur(30, 0, 1000);
        $this->givenThereIsAProductWhichCostsEur('lightsaber', 5);
        $this->whenIAddTheVariantToTheCart('lightsaber-variant-aaa', 2);
        $this->whenIChooseShipping('bpost_home');

        $this->refreshCart();

        $cart = $this->orderContext->repos()->cartRepository()->findCart(OrderId::fromString('xxx'));

        $this->assertEquals(Money::EUR('3000'), $cart->getShipping()->getCostPriceExcl());
        $this->assertEquals(Money::EUR(0), $cart->getShipping()->getDiscountPriceExcl());
        $this->assertEquals(Money::EUR('3000'), $cart->getShipping()->getTotalPriceExcl());
        $this->assertEquals('€ 30', $cart->getShipping()->getFormattedCostPriceExcl());
        $this->assertEquals('€ 0', $cart->getShipping()->getFormattedDiscountPriceExcl());
        $this->assertEquals('€ 30', $cart->getShipping()->getFormattedTotalPriceExcl());
    }

    public function test_it_can_see_payment_prices()
    {
        $this->givenPaymentMethod(30, 'bancontact');
        $this->givenThereIsAProductWhichCostsEur('lightsaber', 5);
        $this->whenIAddTheVariantToTheCart('lightsaber-variant-aaa', 2);
        $this->whenIChoosePayment('bancontact');

        $this->refreshCart();
        $cart = $this->orderContext->repos()->cartRepository()->findCart(OrderId::fromString('xxx'));

        $this->assertEquals(Money::EUR('3000'), $cart->getPayment()->getCostPriceExcl());
        $this->assertEquals(Money::EUR(0), $cart->getPayment()->getDiscountPriceExcl());
        $this->assertEquals(Money::EUR('3000'), $cart->getPayment()->getTotalPriceExcl());
        $this->assertEquals('€ 30', $cart->getPayment()->getFormattedCostPriceExcl());
        $this->assertEquals('€ 0', $cart->getPayment()->getFormattedDiscountPriceExcl());
        $this->assertEquals('€ 30', $cart->getPayment()->getFormattedTotalPriceExcl());
    }
}
