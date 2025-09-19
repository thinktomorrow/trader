<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Vine;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Infrastructure\Common\Catalog;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Domain\Common\Locale;

final class GetActiveFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_get_active_filters()
    {
        foreach (Catalog::drivers() as $catalog) {

            // Create catalog
            $catalog->createTaxonomy();
            $taxon1 = $catalog->createTaxon('taxon-1');
            $taxon2 = $catalog->createTaxon('taxon-2');

            $product = $catalog->createProduct();
            $catalog->linkProductToTaxon($product->productId->get(), $taxon1->taxonId->get());
            $catalog->linkProductToTaxon($product->productId->get(), $taxon2->taxonId->get());

            // Fetch active filters
            $activeFilters = $catalog->repos->filterTreeComposer()->getActiveFilters(
                Locale::fromString('nl'),
                ['taxon-1-key-nl', 'taxon-2-key-nl'],
                []
            );

            $this->assertEquals(2, $activeFilters->total());
            $this->assertEquals('taxon-1', $activeFilters->first()->getId());
            $this->assertEquals('taxon-2', $activeFilters[1]->getId());
        }
    }

    public function test_active_filter_returns_only_subfilters()
    {
        foreach (Catalog::drivers() as $catalog) {

            // Create catalog
            $taxonomy = $catalog->createTaxonomy();
            $taxon1 = $catalog->createTaxon('taxon-1');
            $taxon2 = $catalog->createTaxon('taxon-2', $taxonomy->taxonomyId->get(), $taxon1->taxonId->get());

            $product = $catalog->createProduct();
            $catalog->linkProductToTaxon($product->productId->get(), $taxon1->taxonId->get());
            $catalog->linkProductToTaxon($product->productId->get(), $taxon2->taxonId->get());

            // Fetch active filters
            $activeFilters = $catalog->repos->filterTreeComposer()->getActiveFilters(
                Locale::fromString('nl'),
                ['taxon-1-key-nl'],
                ['taxon-2-key-nl']
            );

            $this->assertEquals(1, $activeFilters->total());
            $this->assertEquals('taxon-2', $activeFilters->first()->getId());
        }
    }

    public function test_scoped_taxon_can_be_passed_as_active_filter()
    {
        foreach (Catalog::drivers() as $catalog) {

            // Create catalog
            $taxonomy = $catalog->createTaxonomy();
            $taxon1 = $catalog->createTaxon('taxon-1');
            $taxon2 = $catalog->createTaxon('taxon-2', $taxonomy->taxonomyId->get(), $taxon1->taxonId->get());

            $product = $catalog->createProduct();
            $catalog->linkProductToTaxon($product->productId->get(), $taxon1->taxonId->get());
            $catalog->linkProductToTaxon($product->productId->get(), $taxon2->taxonId->get());

            // Fetch active filters
            $activeFilters = $catalog->repos->filterTreeComposer()->getActiveFilters(
                Locale::fromString('nl'),
                ['taxon-1-key-nl'],
                ['taxon-1-key-nl', 'taxon-2-key-nl']
            );

            $this->assertEquals(1, $activeFilters->total());
            $this->assertEquals('taxon-2', $activeFilters->first()->getId());
        }
    }

    public function test_it_keeps_main_taxon_as_filter_when_the_active_filters_are_no_children_of_main_taxon()
    {
        foreach (Catalog::drivers() as $catalog) {

            // Create catalog
            $taxonomy = $catalog->createTaxonomy();
            $taxon1 = $catalog->createTaxon('taxon-1');
            $taxon2 = $catalog->createTaxon('taxon-2');

            $product = $catalog->createProduct();
            $catalog->linkProductToTaxon($product->productId->get(), $taxon1->taxonId->get());
            $catalog->linkProductToTaxon($product->productId->get(), $taxon2->taxonId->get());

            // Fetch active filters
            $activeFilters = $catalog->repos->filterTreeComposer()->getActiveFilters(
                Locale::fromString('nl'),
                ['taxon-1-key-nl'],
                ['taxon-2-key-nl']
            );

            $this->assertEquals(2, $activeFilters->total());
            $this->assertEquals('taxon-1', $activeFilters->first()->getId());
            $this->assertEquals('taxon-2', $activeFilters[1]->getId());
        }
    }

    public function test_it_returns_empty_active_filter_if_taxon_key_does_not_exist()
    {
        foreach (Catalog::drivers() as $catalog) {

            $activeFilters = $catalog->repos->filterTreeComposer()->getActiveFilters(
                Locale::fromString('nl'),
                ['xxx'],
                []
            );

            $this->assertEquals(0, $activeFilters->total());
        }
    }
}
