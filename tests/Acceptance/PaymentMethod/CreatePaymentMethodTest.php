<?php
declare(strict_types=1);

namespace Tests\Acceptance\PaymentMethod;

use Money\Money;
use Thinktomorrow\Trader\Application\PaymentMethod\CreatePaymentMethod;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodId;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodProviderId;

class CreatePaymentMethodTest extends PaymentMethodContext
{
    /** @test */
    public function it_can_create_a_payment_method()
    {
        $paymentMethodId = $this->paymentMethodApplication->createPaymentMethod(new CreatePaymentMethod(
            'mollie',
            '10',
            ['BE','NL'],
            ['foo' => 'bar']
        ));

        $paymentMethod = $this->paymentMethodRepository->find($paymentMethodId);

        $this->assertInstanceOf(PaymentMethodId::class, $paymentMethodId);
        $this->assertEquals($paymentMethodId, $paymentMethod->paymentMethodId);
        $this->assertEquals(PaymentMethodProviderId::fromString('mollie'), $paymentMethod->getProvider());
        $this->assertEquals(Money::EUR(10), $paymentMethod->getRate());
        $this->assertEquals([
            CountryId::fromString('BE'),
            CountryId::fromString('NL'),
        ], $paymentMethod->getCountryIds());
        $this->assertEquals(['foo' => 'bar'], $paymentMethod->getData());
    }
}
