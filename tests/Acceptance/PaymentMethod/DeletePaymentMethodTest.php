<?php

declare(strict_types=1);

namespace Tests\Acceptance\PaymentMethod;

use Tests\Acceptance\TestCase;
use Tests\TestHelpers;
use Thinktomorrow\Trader\Application\PaymentMethod\CreatePaymentMethod;
use Thinktomorrow\Trader\Application\PaymentMethod\DeletePaymentMethod;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\Events\PaymentMethodDeleted;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\Exceptions\CouldNotFindPaymentMethod;

class DeletePaymentMethodTest extends TestCase
{
    use TestHelpers;

    public function test_it_can_delete_a_method()
    {
        $paymentMethodId = $this->orderContext->apps()->paymentMethodApplication()->createPaymentMethod(new CreatePaymentMethod(
            'pay-after-invoice',
            '10',
            ['BE', 'NL'],
            ['foo' => 'bar']
        ));

        $this->orderContext->apps()->paymentMethodApplication()->deletePaymentMethod(new DeletePaymentMethod($paymentMethodId->get()));

        $this->assertEquals([
            new PaymentMethodDeleted($paymentMethodId),
        ], $this->orderContext->apps()->getEventDispatcher()->releaseDispatchedEvents());

        $this->expectException(CouldNotFindPaymentMethod::class);
        $this->orderContext->repos()->paymentMethodRepository()->find($paymentMethodId);
    }
}
