<?php

declare(strict_types=1);

namespace Tests\Acceptance\Taxonomy;

use Tests\Acceptance\TestCase;
use Thinktomorrow\Trader\Application\Taxonomy\TaxonomyApplication;
use Thinktomorrow\Trader\Infrastructure\Test\EventDispatcherSpy;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryTaxonomyRepository;
use Thinktomorrow\Trader\Infrastructure\Test\TestTraderConfig;

abstract class TaxonomyContext extends TestCase
{
    protected TaxonomyApplication $taxonomyApplication;

    protected EventDispatcherSpy $eventDispatcher;

    protected InMemoryTaxonomyRepository $taxonomyRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->taxonomyApplication = new TaxonomyApplication(
            new TestTraderConfig,
            $this->eventDispatcher = new EventDispatcherSpy,
            $this->taxonomyRepository = new InMemoryTaxonomyRepository,
        );
    }

    protected function tearDown(): void
    {
        $this->taxonomyRepository->clear();
    }
}
