<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\Exceptions\CouldNotFindTaxonomy;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyId;
use Thinktomorrow\Trader\Testing\Catalog\CatalogContext;

final class TaxonomyRepositoryTest extends TestCase
{
    public function test_it_can_save_and_find_a_taxonomy()
    {
        foreach (CatalogContext::drivers() as $catalog) {

            $repository = $catalog->repos()->taxonomyRepository();

            $catalog->dontPersist();

            $taxonomy = $catalog->createTaxonomy();

            $repository->save($taxonomy);

            $this->assertEquals($taxonomy, $repository->find($taxonomy->taxonomyId));
        }
    }

    public function test_it_can_find_many_taxonomies_by_taxon_id()
    {
        /** @var CatalogContext $catalog */
        foreach (CatalogContext::drivers() as $catalog) {

            $repository = $catalog->repos()->taxonomyRepository();

            $taxonomy = $catalog->createTaxonomy();
            $taxon = $catalog->createTaxon('taxon-aaa', $taxonomy->taxonomyId->get());

            $this->assertEquals([$taxonomy], $repository->findManyByTaxa([$taxon->taxonId->get()]));
        }
    }

    public function test_it_can_delete_a_taxonomy()
    {
        $taxonomiesNotFound = 0;

        /** @var CatalogContext $catalog */
        foreach (CatalogContext::drivers() as $catalog) {

            $repository = $catalog->repos()->taxonomyRepository();

            $taxonomy = $catalog->createTaxonomy();

            $repository->delete($taxonomy->taxonomyId);

            try {
                $repository->find($taxonomy->taxonomyId);
            } catch (CouldNotFindTaxonomy $e) {
                $taxonomiesNotFound++;
            }
        }

        $this->assertCount($taxonomiesNotFound, CatalogContext::drivers());
    }

    public function test_it_can_generate_a_next_reference()
    {
        /** @var CatalogContext $catalog */
        foreach (CatalogContext::drivers() as $catalog) {

            $repository = $catalog->repos()->taxonomyRepository();

            $this->assertInstanceOf(TaxonomyId::class, $repository->nextReference());
        }
    }
}
