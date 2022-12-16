<?php
declare(strict_types=1);

namespace Tests\Acceptance\Taxon;

use Tests\Acceptance\TestCase;
use Thinktomorrow\Trader\Application\Taxon\TaxonApplication;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonNode;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\DefaultTaxonNode;
use Thinktomorrow\Trader\Infrastructure\Test\EventDispatcherSpy;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryTaxonRepository;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;
use Thinktomorrow\Trader\Infrastructure\Test\TestTraderConfig;

abstract class TaxonContext extends TestCase
{
    protected TaxonApplication $taxonApplication;
    protected EventDispatcherSpy $eventDispatcher;
    protected InMemoryTaxonRepository $taxonRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->taxonApplication = new TaxonApplication(
            new TestTraderConfig(),
            $this->eventDispatcher = new EventDispatcherSpy(),
            $this->taxonRepository = new InMemoryTaxonRepository(),
        );

        (new TestContainer())->add(TaxonNode::class, DefaultTaxonNode::class);
    }

    public function tearDown(): void
    {
        $this->taxonRepository->clear();
    }
}
