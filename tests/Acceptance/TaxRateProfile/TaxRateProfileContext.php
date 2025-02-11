<?php
declare(strict_types=1);

namespace Tests\Acceptance\TaxRateProfile;

use Tests\Acceptance\TestCase;
use Thinktomorrow\Trader\Application\VatRate\VatRateApplication;
use Thinktomorrow\Trader\Infrastructure\Test\EventDispatcherSpy;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryVatRateRepository;

class TaxRateProfileContext extends TestCase
{
    protected VatRateApplication $taxRateProfileApplication;
    protected InMemoryVatRateRepository $taxRateProfileRepository;
    protected EventDispatcherSpy $eventDispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->taxRateProfileApplication = new VatRateApplication(
            $this->eventDispatcher = new EventDispatcherSpy(),
            $this->taxRateProfileRepository = new InMemoryVatRateRepository(),
        );
    }

    public function tearDown(): void
    {
        $this->taxRateProfileRepository->clear();
    }
}
