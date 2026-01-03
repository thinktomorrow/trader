<?php

declare(strict_types=1);

namespace Tests\Acceptance\Taxon;

use Tests\Acceptance\TestCase;
use Thinktomorrow\Trader\Application\Taxon\Queries\FindMainCategoryTaxon;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryTaxonTreeRepository;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;
use Thinktomorrow\Trader\Infrastructure\Test\TestTraderConfig;

class FindMainCategoryTaxaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->catalogContext->createTaxonomy();
        $this->catalogContext->createTaxon();
        $this->catalogContext->createTaxon('taxon-bbb', 'taxonomy-aaa', 'taxon-aaa');
    }

    public function test_it_can_find_the_main_category_taxa()
    {
        $finder = new FindMainCategoryTaxon(
            new TestTraderConfig(['category_taxonomy_id' => 'taxonomy-aaa']),
            new InMemoryTaxonTreeRepository(new TestContainer, new TestTraderConfig)
        );

        $this->assertEquals('taxon-aaa', $finder->findFirstByTaxonIds(['taxon-aaa'])->getId());
        $this->assertEquals('taxon-bbb', $finder->findFirstByTaxonIds(['taxon-bbb'])->getId());
    }

    public function test_it_can_get_return_same_taxon_if_taxon_is_category_root()
    {
        $finder = new FindMainCategoryTaxon(
            new TestTraderConfig(['category_taxonomy_id' => 'taxonomy-aaa']),
            new InMemoryTaxonTreeRepository(new TestContainer, new TestTraderConfig)
        );

        $this->assertEquals('taxon-aaa', $finder->findFirstByTaxonIds(['taxon-aaa'])->getId());
    }

    public function test_it_uses_the_first_taxon_subtree_as_default_category()
    {
        $finder = new FindMainCategoryTaxon(
            new TestTraderConfig(['category_taxonomy_id' => null]),
            new InMemoryTaxonTreeRepository(new TestContainer, new TestTraderConfig)
        );

        $this->assertEquals(null, $finder->findFirstByTaxonIds(['taxon-aaa', 'taxon-bbb']));
    }

    public function test_it_can_get_multiple_root_taxa()
    {
        $finder = new FindMainCategoryTaxon(
            new TestTraderConfig(['category_taxonomy_id' => 'taxonomy-aaa']),
            new InMemoryTaxonTreeRepository(new TestContainer, new TestTraderConfig)
        );

        $this->assertEquals('taxon-aaa', $finder->findFirstByTaxonIds(['taxon-aaa', 'taxon-bbb'])->getId());
    }
}
