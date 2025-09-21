<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Vine;

use Tests\Infrastructure\Common\Catalog;
use Tests\Infrastructure\TestCase;

final class GetFlattenedTaxonIdsTest extends TestCase
{
    public function test_it_can_retrieve_taxon_ids_grouped_by_taxonomy()
    {
        foreach (Catalog::drivers() as $catalog) {

            // Catalog setup
            $catalog->createTaxonomy();
            $taxonomy2 = $catalog->createTaxonomy('taxonomy-bbb');
            $catalog->createTaxon();
            $catalog->createTaxon('taxon-bbb', $taxonomy2->taxonomyId->get());
            $catalog->createTaxon('taxon-ccc', $taxonomy2->taxonomyId->get());

            // Compose taxon ids grouped by taxonomy
            $taxonIds = $catalog->repos->flattenedTaxonIds()->getGroupedByTaxonomyByKeys(['taxon-aaa-key-nl', 'taxon-bbb-key-nl']);

            $this->assertEquals([
                'taxonomy-aaa' => ['taxon-aaa'],
                'taxonomy-bbb' => ['taxon-bbb'],
            ], $taxonIds);
        }
    }

    public function test_it_can_retrieve_taxon_ids_by_ids()
    {
        foreach (Catalog::drivers() as $catalog) {

            // Catalog setup
            $catalog->createTaxonomy();
            $taxonomy2 = $catalog->createTaxonomy('taxonomy-bbb');
            $catalog->createTaxon();
            $catalog->createTaxon('taxon-bbb', $taxonomy2->taxonomyId->get());
            $catalog->createTaxon('taxon-ccc', $taxonomy2->taxonomyId->get());

            // Compose taxon ids grouped by taxonomy
            $taxonIds = $catalog->repos->flattenedTaxonIds()->getGroupedByTaxonomyByIds(['taxon-aaa', 'taxon-bbb']);

            $this->assertEquals([
                'taxonomy-aaa' => ['taxon-aaa'],
                'taxonomy-bbb' => ['taxon-bbb'],
            ], $taxonIds);
        }
    }

    public function test_it_expands_tree_for_each_given_taxon(): void
    {
        foreach (Catalog::drivers() as $catalog) {

            // Catalog setup
            $catalog->createTaxonomy();
            $taxonomy2 = $catalog->createTaxonomy('taxonomy-bbb');
            $catalog->createTaxon();
            $catalog->createTaxon('taxon-bbb', $taxonomy2->taxonomyId->get());
            $catalog->createTaxon('taxon-ccc', $taxonomy2->taxonomyId->get(), 'taxon-bbb');

            // Compose taxon ids grouped by taxonomy
            $taxonIds = $catalog->repos->flattenedTaxonIds()->getGroupedByTaxonomyByKeys(['taxon-aaa-key-nl', 'taxon-bbb-key-nl']);

            $this->assertEquals([
                'taxonomy-aaa' => ['taxon-aaa'],
                'taxonomy-bbb' => ['taxon-bbb', 'taxon-ccc'],
            ], $taxonIds);
        }
    }

    public function test_it_returns_unique_values()
    {
        foreach (Catalog::drivers() as $catalog) {

            // Catalog setup
            $catalog->createTaxonomy();
            $taxonomy2 = $catalog->createTaxonomy('taxonomy-bbb');
            $catalog->createTaxon();
            $catalog->createTaxon('taxon-bbb', $taxonomy2->taxonomyId->get());
            $catalog->createTaxon('taxon-ccc', $taxonomy2->taxonomyId->get(), 'taxon-bbb');

            // Compose taxon ids grouped by taxonomy
            $taxonIds = $catalog->repos->flattenedTaxonIds()->getGroupedByTaxonomyByIds(['taxon-aaa', 'taxon-bbb', 'taxon-bbb', 'taxon-ccc']);

            $this->assertEquals([
                'taxonomy-aaa' => ['taxon-aaa'],
                'taxonomy-bbb' => ['taxon-bbb', 'taxon-ccc'],
            ], $taxonIds);
        }
    }

    public function test_it_returns_empty_list_for_non_found_taxons()
    {
        foreach (Catalog::drivers() as $catalog) {

            // Catalog setup
            $catalog->createTaxonomy();
            $catalog->createTaxon();

            // Compose taxon ids grouped by taxonomy
            $taxonIds = $catalog->repos->flattenedTaxonIds()->getGroupedByTaxonomyByIds(['xxx']);

            $this->assertEquals([], $taxonIds);
        }
    }
}
