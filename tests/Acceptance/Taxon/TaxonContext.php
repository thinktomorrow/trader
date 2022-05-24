<?php
declare(strict_types=1);

namespace Tests\Acceptance\Taxon;

use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Application\Taxon\TaxonApplication;
use Thinktomorrow\Trader\Infrastructure\Test\TestTraderConfig;
use Thinktomorrow\Trader\Infrastructure\Test\EventDispatcherSpy;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryTaxonRepository;

abstract class TaxonContext extends TestCase
{
    protected TaxonApplication $taxonApplication;
    protected EventDispatcherSpy $eventDispatcher;

    protected function setUp(): void
    {
        $this->taxonApplication = new TaxonApplication(
            new TestTraderConfig(),
            $this->eventDispatcher = new EventDispatcherSpy(),
            $this->taxonRepository = new InMemoryTaxonRepository(),
        );
    }

    public function tearDown(): void
    {
        $this->taxonRepository->clear();
    }
}
