<?php

declare(strict_types=1);

namespace Acceptance\Cart;

use Money\Money;
use Tests\Acceptance\Cart\CartContext;
use Thinktomorrow\Trader\Application\Cart\Read\CartPayment;
use Thinktomorrow\Trader\Application\Cart\Read\CartShipping;
use Thinktomorrow\Trader\Domain\Common\Price\DefaultServicePrice;
use Thinktomorrow\Trader\Domain\Common\Price\VatApplicableAmount;
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

    public function test_cart_and_merchant_services_expose_inclusive_cost_discount_and_total(): void
    {
        $order = $this->orderContext->createDefaultOrder('service-prices');
        $this->orderContext->addLineToOrder($order, $this->orderContext->createLine('service-prices', 'reduced-rate', [
            'unit_price_excl' => '1000',
            'unit_price_incl' => '1060',
            'tax_rate' => '6',
        ]));

        $allocator = $this->catalogContext->apps()->vatAllocator();
        $grossShipping = VatApplicableAmount::includingVat(Money::EUR(1000));
        $order->getShippings()[0]->updateCost(DefaultServicePrice::fromIncludingVat(
            Money::EUR(1000),
            $allocator->resolveVatApplicableAmountExcludingVat($order, $grossShipping),
        ));
        $order->getShippings()[0]->addDiscount($this->orderContext->createShippingDiscount(
            'service-prices', 'service-prices:shipping-aaa', 'shipping-discount',
            [
                'total_excl' => $allocator->resolveVatApplicableAmountExcludingVat($order, VatApplicableAmount::includingVat(Money::EUR(250)))->getAmount(),
                'total_incl' => '250',
                'tax_mode' => 'inclusive',
            ],
        ));
        $order->getPayments()[0]->updateCost(DefaultServicePrice::fromExcludingVat(Money::EUR(1000)));
        $order->getPayments()[0]->addDiscount($this->orderContext->createPaymentDiscount(
            'service-prices', 'service-prices:payment-aaa', 'payment-discount',
            ['total_excl' => '200'],
        ));
        $this->orderContext->saveOrder($order);

        $cart = $this->orderContext->repos()->cartRepository()->findCart($order->orderId);
        $merchantOrder = $this->orderContext->findMerchantOrder($order->orderId->get());

        foreach ([$cart->getShipping(), $merchantOrder->getShippings()[0]] as $shipping) {
            $this->assertEquals(Money::EUR(1000), $shipping->getCostPriceIncl());
            $this->assertEquals(Money::EUR(250), $shipping->getDiscountPriceIncl());
            $this->assertEquals(Money::EUR(750), $shipping->getTotalPriceIncl());
            $this->assertSame('€ 10', $shipping->getFormattedCostPriceIncl());
            $this->assertSame('€ 2,50', $shipping->getFormattedDiscountPriceIncl());
            $this->assertSame('€ 7,50', $shipping->getFormattedTotalPriceIncl());
        }

        foreach ([$cart->getPayment(), $merchantOrder->getPayments()[0]] as $payment) {
            $this->assertEquals($allocator->allocateServicePrice($order, $order->getPayments()[0]->getPaymentCost())->getTotalIncludingVat(), $payment->getCostPriceIncl());
            $this->assertEquals($allocator->allocateServicePrice($order, $order->getPayments()[0]->getDiscountPrice())->getTotalIncludingVat(), $payment->getDiscountPriceIncl());
            $this->assertEquals($payment->getCostPriceIncl()->subtract($payment->getDiscountPriceIncl()), $payment->getTotalPriceIncl());
            $this->assertSame('€ 10,81', $payment->getFormattedCostPriceIncl());
            $this->assertSame('€ 2,16', $payment->getFormattedDiscountPriceIncl());
            $this->assertSame('€ 8,65', $payment->getFormattedTotalPriceIncl());
        }

        $this->assertEquals($cart->getShippingCostIncl(), $cart->getShipping()->getTotalPriceIncl());
        $this->assertEquals($cart->getPaymentCostIncl(), $cart->getPayment()->getTotalPriceIncl());
    }
}
