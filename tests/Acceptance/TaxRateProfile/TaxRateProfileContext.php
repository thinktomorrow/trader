<?php
declare(strict_types=1);

namespace Tests\Acceptance\TaxRateProfile;

use Tests\Acceptance\TestCase;
use Thinktomorrow\Trader\Application\TaxRateProfile\TaxRateProfileApplication;
use Thinktomorrow\Trader\Infrastructure\Test\EventDispatcherSpy;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryTaxRateProfileRepository;

class TaxRateProfileContext extends TestCase
{
    protected TaxRateProfileApplication $taxRateProfileApplication;
    protected InMemoryTaxRateProfileRepository $taxRateProfileRepository;
    protected EventDispatcherSpy $eventDispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->taxRateProfileApplication = new TaxRateProfileApplication(
            $this->eventDispatcher = new EventDispatcherSpy(),
            $this->taxRateProfileRepository = new InMemoryTaxRateProfileRepository(),
        );
    }

    public function tearDown(): void
    {
        $this->taxRateProfileRepository->clear();
    }
}
