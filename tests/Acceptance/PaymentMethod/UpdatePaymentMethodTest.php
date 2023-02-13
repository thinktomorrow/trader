<?php
declare(strict_types=1);

namespace Tests\Acceptance\PaymentMethod;

use Money\Money;
use Thinktomorrow\Trader\Application\PaymentMethod\CreatePaymentMethod;
use Thinktomorrow\Trader\Application\PaymentMethod\CreateTariff;
use Thinktomorrow\Trader\Application\PaymentMethod\UpdatePaymentMethod;
use Thinktomorrow\Trader\Application\PaymentMethod\UpdateTariff;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;

class UpdatePaymentMethodTest extends PaymentMethodContext
{
    /** @test */
    public function it_can_update_a_profile()
    {
        $paymentMethodId = $this->paymentMethodApplication->createPaymentMethod(new CreatePaymentMethod(
            "10",
            ['BE','NL'],
            ['foo' => 'bar']
        ));

        $this->paymentMethodApplication->updatePaymentMethod(new UpdatePaymentMethod(
            $paymentMethodId->get(),
            "20",
            ['BE'],
            ['foo' => 'baz']
        ));

        $updatedPaymentMethod = $this->paymentMethodRepository->find($paymentMethodId);

        $this->assertEquals(Money::EUR(20), $updatedPaymentMethod->getRate());
        $this->assertEquals([
            CountryId::fromString('BE'),
        ], $updatedPaymentMethod->getCountryIds());
        $this->assertEquals(['foo' => 'baz'], $updatedPaymentMethod->getData());
    }
}
