<?php
declare(strict_types=1);

namespace Tests\Acceptance\PaymentMethod;

use Tests\Acceptance\TestCase;
use Thinktomorrow\Trader\Application\PaymentMethod\PaymentMethodApplication;
use Thinktomorrow\Trader\Infrastructure\Test\EventDispatcherSpy;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryPaymentMethodRepository;

class PaymentMethodContext extends TestCase
{
    protected PaymentMethodApplication $paymentMethodApplication;
    protected InMemoryPaymentMethodRepository $paymentMethodRepository;
    protected EventDispatcherSpy $eventDispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentMethodApplication = new PaymentMethodApplication(
            $this->eventDispatcher = new EventDispatcherSpy(),
            $this->paymentMethodRepository = new InMemoryPaymentMethodRepository(),
        );
    }

    public function tearDown(): void
    {
        $this->paymentMethodRepository->clear();
    }
}
