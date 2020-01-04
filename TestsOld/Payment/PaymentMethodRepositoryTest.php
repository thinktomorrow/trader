<?php

namespace Thinktomorrow\Trader\TestsOld\Payment;

use Thinktomorrow\Trader\Payment\Domain\PaymentMethod;
use Thinktomorrow\Trader\Payment\Domain\PaymentMethodId;
use Thinktomorrow\Trader\TestsOld\Stubs\InMemoryPaymentMethodRepository;
use Thinktomorrow\Trader\TestsOld\TestCase;

class PaymentMethodRepositoryTest extends TestCase
{
    /** @test */
    public function it_can_find_a_paymentMethod()
    {
        $paymentMethod = new PaymentMethod(PaymentMethodId::fromInteger(3), 'foobar');
        $repo = new InMemoryPaymentMethodRepository();

        $repo->add($paymentMethod);

        $this->assertEquals($paymentMethod, $repo->find(PaymentMethodId::fromInteger(3)));
    }

    public function it_throws_exception_if_order_does_not_exist()
    {
        $this->expectException(\RuntimeException::class);

        $repo = new InMemoryPaymentMethodRepository();
        $repo->find(PaymentMethodId::fromInteger(3));
    }
}
