<?php
declare(strict_types=1);

namespace Tests\Acceptance\Taxon;

use Tests\Infrastructure\Vine\TaxonHelpers;
use Thinktomorrow\Trader\Application\Taxon\Category\FindCategoryTaxon;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryTaxonRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryTaxonTreeRepository;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;
use Thinktomorrow\Trader\Infrastructure\Test\TestTraderConfig;

class FindCategoryTaxonTest extends TaxonContext
{
    use TaxonHelpers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createDefaultTaxons();
    }

    private function entityRepositories(): \Generator
    {
        yield new InMemoryTaxonRepository();
    }

    public function test_it_can_find_the_category_taxon()
    {
        $finder = new FindCategoryTaxon(
            new TestTraderConfig(['category_root_id' => 'fifth']),
            new InMemoryTaxonTreeRepository(new TestContainer(), new TestTraderConfig())
        );

        $this->assertEquals('sixth', $finder->byTaxonIds(['first', 'second', 'sixth'])->getId());
    }

    public function test_it_uses_the_first_taxon_subtree_as_default_category()
    {
        $finder = new FindCategoryTaxon(
            new TestTraderConfig(['category_root_id' => null]),
            new InMemoryTaxonTreeRepository(new TestContainer(), new TestTraderConfig())
        );

        $this->assertEquals('second', $finder->byTaxonIds(['second', 'sixth'])->getId());
    }

    public function test_if_own_taxon_is_also_category_root()
    {
        $finder = new FindCategoryTaxon(
            new TestTraderConfig(['category_root_id' => null]),
            new InMemoryTaxonTreeRepository(new TestContainer(), new TestTraderConfig())
        );

        $this->assertEquals('first', $finder->byTaxonIds(['first', 'sixth'])->getId());
    }
}
