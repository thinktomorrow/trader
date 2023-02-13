<?php
declare(strict_types=1);

namespace Tests\Acceptance\PaymentMethod;

use Money\Money;
use Thinktomorrow\Trader\Application\PaymentMethod\CreatePaymentMethod;
use Thinktomorrow\Trader\Application\PaymentMethod\CreateTariff;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodId;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\Tariff;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\TariffId;

class CreatePaymentMethodTest extends PaymentMethodContext
{
    /** @test */
    public function it_can_create_a_shipping_profile()
    {
        $paymentMethodId = $this->paymentMethodApplication->createPaymentMethod(new CreatePaymentMethod(
            '10',
            ['BE','NL'],
            ['foo' => 'bar']
        ));

        $this->assertInstanceOf(PaymentMethodId::class, $paymentMethodId);
        $this->assertEquals($paymentMethodId, $this->paymentMethodRepository->find($paymentMethodId)->paymentMethodId);
        $this->assertEquals(Money::EUR(10), $this->paymentMethodRepository->find($paymentMethodId)->getRate());
        $this->assertEquals([
            CountryId::fromString('BE'),
            CountryId::fromString('NL'),
        ], $this->paymentMethodRepository->find($paymentMethodId)->getCountryIds());
        $this->assertEquals(['foo' => 'bar'], $this->paymentMethodRepository->find($paymentMethodId)->getData());
    }
}
