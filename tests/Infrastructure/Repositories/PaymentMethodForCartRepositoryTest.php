<?php

namespace Tests\Infrastructure\Repositories;

use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Testing\Order\OrderContext;

class PaymentMethodForCartRepositoryTest extends TestCase
{
    public function test_it_can_find_payment_methods_for_cart()
    {
        foreach (OrderContext::drivers() as $orderContext) {
            $orderContext->createPaymentMethod();

            $repository = $orderContext->repos()->paymentMethodRepository();
            $this->assertCount(1, $repository->findAllPaymentMethodsForCart());
        }
    }

    public function test_it_can_find_methods_for_cart_with_matching_countries()
    {
        foreach (OrderContext::drivers() as $orderContext) {

            // Create payment method with country BE
            $paymentMethod = $orderContext->dontPersist()->createPaymentMethod();
            $country = $orderContext->persist()->createCountry('BE');

            $repository = $orderContext->repos()->paymentMethodRepository();

            $paymentMethod->addCountry($country->countryId);
            $repository->save($paymentMethod);

            $this->assertCount(1, $repository->findAllPaymentMethodsForCart('BE'));
            $this->assertCount(0, $repository->findAllPaymentMethodsForCart('NL'));
        }
    }
}
