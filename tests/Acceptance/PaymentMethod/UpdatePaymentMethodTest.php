<?php
declare(strict_types=1);

namespace Tests\Acceptance\PaymentMethod;

use Money\Money;
use Thinktomorrow\Trader\Application\PaymentMethod\CreatePaymentMethod;
use Thinktomorrow\Trader\Application\PaymentMethod\UpdatePaymentMethod;
use Thinktomorrow\Trader\Domain\Model\Country\CountryId;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodProviderId;

class UpdatePaymentMethodTest extends PaymentMethodContext
{
    public function test_it_can_update_a_profile()
    {
        $paymentMethodId = $this->paymentMethodApplication->createPaymentMethod(new CreatePaymentMethod(
            'mollie',
            "10",
            ['BE', 'NL'],
            ['foo' => 'bar']
        ));

        $this->paymentMethodApplication->updatePaymentMethod(new UpdatePaymentMethod(
            $paymentMethodId->get(),
            'stripe',
            "20",
            ['BE'],
            ['foo' => 'baz']
        ));

        $updatedPaymentMethod = $this->paymentMethodRepository->find($paymentMethodId);

        $this->assertEquals(PaymentMethodProviderId::fromString('stripe'), $updatedPaymentMethod->getProvider());
        $this->assertEquals(Money::EUR(20), $updatedPaymentMethod->getRate());
        $this->assertEquals([
            CountryId::fromString('BE'),
        ], $updatedPaymentMethod->getCountryIds());
        $this->assertEquals(['foo' => 'baz'], $updatedPaymentMethod->getData());
    }
}
