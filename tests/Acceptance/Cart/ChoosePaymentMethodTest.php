<?php

namespace Tests\Acceptance\Cart;

use Thinktomorrow\Trader\Domain\Common\Cash\Cash;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\Exceptions\CouldNotFindPaymentMethod;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethod;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodId;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodState;

class ChoosePaymentMethodTest extends CartContext
{
    public function test_it_can_choose_payment_method()
    {
        $this->givenPaymentMethod(10);
        $this->whenIChoosePayment('visa');

        // Assert all is present
        $cart = $this->cartRepository->findCart($this->getOrder()->orderId);
        $this->assertNotNull($cart->getPayment());
    }

    public function test_it_cannot_choose_payment_method_when_none_is_online()
    {
        $paymentMethod = PaymentMethod::create(PaymentMethodId::fromString('foobar'), Cash::make(10 * 100));
        $paymentMethod->updateState(PaymentMethodState::offline);
        $this->paymentMethodRepository->save($paymentMethod);

        $this->whenIChoosePayment('foobar');

        $cart = $this->cartRepository->findCart($this->getOrder()->orderId);
        $this->assertNull($cart->getPayment());
    }

    public function test_it_can_choose_payment_method_when_method_has_country_restriction_but_billing_country_is_not_given()
    {
        $paymentMethod = PaymentMethod::create(PaymentMethodId::fromString('foobar'), Cash::make(10 * 100));
        $paymentMethod->addCountry(CountryId::fromString('LU'));
        $this->paymentMethodRepository->save($paymentMethod);

        $this->whenIChoosePayment('foobar');

        $cart = $this->cartRepository->findCart($this->getOrder()->orderId);
        $this->assertNotNull($cart->getPayment());
    }

    public function test_it_can_choose_payment_method_when_it_is_allowed_for_given_billing_country()
    {
        $paymentMethod = PaymentMethod::create(PaymentMethodId::fromString('foobar'), Cash::make(10 * 100));
        $paymentMethod->addCountry(CountryId::fromString('LU'));
        $this->paymentMethodRepository->save($paymentMethod);

        $this->whenIAddBillingAddress('LU', 'example 13', 'bus 2', '1200', 'Brussel');
        $this->whenIChoosePayment('foobar');

        $cart = $this->cartRepository->findCart($this->getOrder()->orderId);
        $this->assertNotNull($cart->getPayment());
    }

    public function test_it_cannot_choose_payment_method_when_none_is_allowed_for_given_billing_country()
    {
        $paymentMethod = PaymentMethod::create(PaymentMethodId::fromString('foobar'), Cash::make(10 * 100));
        $paymentMethod->addCountry(CountryId::fromString('LU'));
        $this->paymentMethodRepository->save($paymentMethod);

        $this->whenIAddBillingAddress('BE', 'example 13', 'bus 2', '1200', 'Brussel');
        $this->whenIChoosePayment('foobar');

        $cart = $this->cartRepository->findCart($this->getOrder()->orderId);
        $this->assertNull($cart->getPayment());
    }

    public function test_it_halts_when_payment_method_id_does_not_exist()
    {
        $this->expectException(CouldNotFindPaymentMethod::class);

        $this->whenIChoosePayment('visa');
    }
}
