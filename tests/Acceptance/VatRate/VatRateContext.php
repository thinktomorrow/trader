<?php
declare(strict_types=1);

namespace Tests\Acceptance\VatRate;

use Tests\Acceptance\TestCase;
use Thinktomorrow\Trader\Application\VatRate\VatRateApplication;
use Thinktomorrow\Trader\Infrastructure\Test\EventDispatcherSpy;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryVatRateRepository;

class VatRateContext extends TestCase
{
    protected VatRateApplication $vatRateApplication;
    protected InMemoryVatRateRepository $vatRateRepository;
    protected EventDispatcherSpy $eventDispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vatRateApplication = new VatRateApplication(
            $this->eventDispatcher = new EventDispatcherSpy(),
            $this->vatRateRepository = new InMemoryVatRateRepository(),
        );
    }

    public function tearDown(): void
    {
        $this->vatRateRepository->clear();
    }
}
