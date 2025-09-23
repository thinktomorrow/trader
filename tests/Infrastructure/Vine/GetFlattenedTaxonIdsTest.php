<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Vine;

use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Testing\Support\Catalog;

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

    public function test_it_returns_empty_list_for_non_found_taxa()
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

    public function test_it_returns_empty_array_for_empty_input()
    {
        foreach (Catalog::drivers() as $catalog) {
            $taxonIds = $catalog->repos->flattenedTaxonIds()->getGroupedByTaxonomyByKeys([]);
            $this->assertEquals([], $taxonIds);
        }
    }

    public function test_it_returns_self_if_taxon_has_no_children()
    {
        foreach (Catalog::drivers() as $catalog) {
            $taxonomy = $catalog->createTaxonomy();
            $taxon = $catalog->createTaxon('lonely-taxon', $taxonomy->taxonomyId->get());

            $taxonIds = $catalog->repos->flattenedTaxonIds()->getGroupedByTaxonomyByIds([$taxon->taxonId->get()]);

            $this->assertEquals([
                $taxonomy->taxonomyId->get() => ['lonely-taxon'],
            ], $taxonIds);
        }
    }

    public function test_it_merges_multiple_taxa_from_same_taxonomy()
    {
        foreach (Catalog::drivers() as $catalog) {
            $taxonomy = $catalog->createTaxonomy();
            $taxon1 = $catalog->createTaxon('taxon-1', $taxonomy->taxonomyId->get());
            $taxon2 = $catalog->createTaxon('taxon-2', $taxonomy->taxonomyId->get());

            $taxonIds = $catalog->repos->flattenedTaxonIds()->getGroupedByTaxonomyByIds([
                $taxon1->taxonId->get(),
                $taxon2->taxonId->get(),
            ]);

            $this->assertEqualsCanonicalizing([
                $taxonomy->taxonomyId->get() => ['taxon-1', 'taxon-2'],
            ], $taxonIds);
        }
    }

    public function test_it_groups_taxa_from_different_taxonomies()
    {
        foreach (Catalog::drivers() as $catalog) {
            $taxonomy1 = $catalog->createTaxonomy('taxonomy-aaa');
            $taxonomy2 = $catalog->createTaxonomy('taxonomy-bbb');
            $taxon1 = $catalog->createTaxon('taxon-aaa', $taxonomy1->taxonomyId->get());
            $taxon2 = $catalog->createTaxon('taxon-bbb', $taxonomy2->taxonomyId->get());

            $taxonIds = $catalog->repos->flattenedTaxonIds()->getGroupedByTaxonomyByIds([
                $taxon1->taxonId->get(),
                $taxon2->taxonId->get(),
            ]);

            $this->assertArrayHasKey('taxonomy-aaa', $taxonIds);
            $this->assertArrayHasKey('taxonomy-bbb', $taxonIds);
        }
    }

    public function test_it_preserves_order_of_input_keys()
    {
        foreach (Catalog::drivers() as $catalog) {
            $taxonomy = $catalog->createTaxonomy();
            $taxon1 = $catalog->createTaxon('taxon-1', $taxonomy->taxonomyId->get());
            $taxon2 = $catalog->createTaxon('taxon-2', $taxonomy->taxonomyId->get());

            $taxonIds = $catalog->repos->flattenedTaxonIds()->getGroupedByTaxonomyByIds([
                $taxon2->taxonId->get(),
                $taxon1->taxonId->get(),
            ]);

            $this->assertEquals(['taxon-2', 'taxon-1'], $taxonIds[$taxonomy->taxonomyId->get()]);
        }
    }

}
