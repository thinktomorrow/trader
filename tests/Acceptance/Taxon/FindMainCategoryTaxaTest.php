<?php

declare(strict_types=1);

namespace Tests\Acceptance\Taxon;

use Tests\Infrastructure\Vine\TaxonHelpers;
use Thinktomorrow\Trader\Application\Taxon\Category\FindMainCategoryTaxonRoots;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryTaxonomyRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryTaxonRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryTaxonTreeRepository;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;
use Thinktomorrow\Trader\Infrastructure\Test\TestTraderConfig;

class FindMainCategoryTaxaTest extends TaxonContext
{
    use TaxonHelpers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createDefaultTaxonomies();
        $this->createDefaultTaxons();
    }

    /** Ensure only in memory is used for taxonomy/taxon creation */
    protected function entityRepositories(): \Generator
    {
        yield new InMemoryTaxonRepository;
    }

    protected function entityTaxonomyRepositories(): \Generator
    {
        yield new InMemoryTaxonomyRepository();
    }

    public function test_it_can_find_the_main_category_taxa()
    {
        $finder = new FindMainCategoryTaxonRoots(
            new TestTraderConfig(['category_taxonomy_id' => 'bbb']),
            new InMemoryTaxonTreeRepository(new TestContainer, new TestTraderConfig)
        );

        $this->assertEquals(['fifth'], array_map(fn($taxonnode) => $taxonnode->getId(), $finder->byTaxonIds(['sixth'])));
        $this->assertEquals(['first'], array_map(fn($taxonnode) => $taxonnode->getId(), $finder->byTaxonIds(['third'])));
        $this->assertEquals(['first'], array_map(fn($taxonnode) => $taxonnode->getId(), $finder->byTaxonIds(['first', 'second', 'third'])));
    }

    public function test_it_uses_the_first_taxon_subtree_as_default_category()
    {
        $finder = new FindMainCategoryTaxonRoots(
            new TestTraderConfig(['category_taxonomy_id' => null]),
            new InMemoryTaxonTreeRepository(new TestContainer, new TestTraderConfig)
        );

        $this->assertEquals([], $finder->byTaxonIds(['second', 'sixth']));
    }

    public function test_it_can_get_multiple_root_taxa()
    {
        $finder = new FindMainCategoryTaxonRoots(
            new TestTraderConfig(['category_taxonomy_id' => 'bbb']),
            new InMemoryTaxonTreeRepository(new TestContainer, new TestTraderConfig)
        );

        $this->assertEquals(['first', 'fifth'], array_map(fn($taxonnode) => $taxonnode->getId(), $finder->byTaxonIds(['second', 'sixth'])));
    }

    public function test_it_can_get_return_same_taxon_if_taxon_is_category_root()
    {
        $finder = new FindMainCategoryTaxonRoots(
            new TestTraderConfig(['category_taxonomy_id' => 'bbb']),
            new InMemoryTaxonTreeRepository(new TestContainer, new TestTraderConfig)
        );

        $this->assertEquals(['first'], array_map(fn($taxonnode) => $taxonnode->getId(), $finder->byTaxonIds(['first'])));
    }
}
