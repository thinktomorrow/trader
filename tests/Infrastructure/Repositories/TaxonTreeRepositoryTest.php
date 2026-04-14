<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Application\Taxon\Tree\TaxonNode;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Testing\Catalog\CatalogContext;

final class TaxonTreeRepositoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_it_can_get_the_entire_tree()
    {
        foreach (CatalogContext::drivers() as $catalog) {

            $catalog->createDefaultTree();

            $repository = $catalog->repos()->taxonTreeRepository();

            $this->assertContainsOnlyInstancesOf(TaxonNode::class, $repository->getTree());
            $this->assertEquals(3, $repository->getTree()->count());
            $this->assertEquals(7, $repository->getTree()->total());
        }
    }

    public function test_it_can_get_the_tree_per_taxonomy()
    {
        foreach (CatalogContext::drivers() as $catalog) {

            $catalog->createDefaultTree();

            $repository = $catalog->repos()->taxonTreeRepository();

            $this->assertContainsOnlyInstancesOf(TaxonNode::class, $repository->getTreeByTaxonomy('taxonomy-aaa'));

            $this->assertEquals(2, $repository->getTreeByTaxonomy('taxonomy-aaa')->count());
            $this->assertEquals(5, $repository->getTreeByTaxonomy('taxonomy-aaa')->total());
            $this->assertEquals(1, $repository->getTreeByTaxonomy('taxonomy-bbb')->count());
            $this->assertEquals(2, $repository->getTreeByTaxonomy('taxonomy-bbb')->total());
        }
    }

    public function test_it_can_get_the_tree_per_taxonomies()
    {
        foreach (CatalogContext::drivers() as $catalog) {

            $catalog->createDefaultTree();

            $repository = $catalog->repos()->taxonTreeRepository();

            $results = $repository->getTreeByTaxonomies(['taxonomy-aaa', 'taxonomy-bbb']);

            $this->assertContainsOnlyInstancesOf(TaxonNode::class, $results);

            $this->assertEquals(3, $results->count());
            $this->assertEquals(7, $results->total());
        }
    }

    public function test_it_can_find_taxon_by_id()
    {
        foreach (CatalogContext::drivers() as $catalog) {

            $catalog->createDefaultTree();

            $repository = $catalog->repos()->taxonTreeRepository();

            $this->assertNotNull($repository->findTaxonById('taxon-ddd'));
            $this->assertNotNull($repository->findTaxonById('taxon-fff'));
        }
    }

    public function test_it_can_find_taxon_by_key()
    {
        foreach (CatalogContext::drivers() as $catalog) {

            $catalog->createDefaultTree();

            $repository = $catalog->repos()->taxonTreeRepository();

            $this->assertNotNull($repository->findTaxonByKey('taxon-ddd-key-nl'));
            $this->assertNotNull($repository->findTaxonByKey('taxon-fff-key-nl'));
        }
    }

    public function test_it_can_find_taxon_by_key_per_locale()
    {
        foreach (CatalogContext::drivers() as $catalog) {

            $catalog->createDefaultTree();

            $repository = $catalog->repos()->taxonTreeRepository();
            $repository->setLocale(Locale::fromString('fr'));

            $this->assertNotNull($repository->findTaxonByKey('taxon-ddd-key-fr'));
            $this->assertNull($repository->findTaxonByKey('taxon-ddd-key-nl'));
        }
    }
}
