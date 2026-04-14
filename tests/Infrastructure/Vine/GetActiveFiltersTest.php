<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Vine;

use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Testing\Catalog\CatalogContext;

final class GetActiveFiltersTest extends TestCase
{
    public function test_it_can_get_active_filters()
    {
        foreach (CatalogContext::drivers() as $catalog) {

            // Create catalog
            $catalog->createTaxonomy();
            $taxon1 = $catalog->createTaxon('taxon-1');
            $taxon2 = $catalog->createTaxon('taxon-2');

            $product = $catalog->createProduct();
            $catalog->linkProductToTaxon($product->productId->get(), $taxon1->taxonId->get());
            $catalog->linkProductToTaxon($product->productId->get(), $taxon2->taxonId->get());

            // Fetch active filters
            $activeFilters = $catalog->repos()->taxonFilters()->getActiveFilters(
                ['taxon-1', 'taxon-2'],
                []
            );

            $this->assertEquals(2, $activeFilters->total());
            $this->assertEquals('taxon-1', $activeFilters->first()->getId());
            $this->assertEquals('taxon-2', $activeFilters[1]->getId());
        }
    }

    public function test_active_filter_returns_only_subfilters()
    {
        foreach (CatalogContext::drivers() as $catalog) {

            // Create catalog
            $taxonomy = $catalog->createTaxonomy();
            $taxon1 = $catalog->createTaxon('taxon-1');
            $taxon2 = $catalog->createTaxon('taxon-2', $taxonomy->taxonomyId->get(), $taxon1->taxonId->get());

            $product = $catalog->createProduct();
            $catalog->linkProductToTaxon($product->productId->get(), $taxon1->taxonId->get());
            $catalog->linkProductToTaxon($product->productId->get(), $taxon2->taxonId->get());

            // Fetch active filters
            $activeFilters = $catalog->repos()->taxonFilters()->getActiveFilters(
                ['taxon-1-key-nl'],
                ['taxon-2-key-nl']
            );

            $this->assertEquals(1, $activeFilters->total());
            $this->assertEquals('taxon-2', $activeFilters->first()->getId());
        }
    }

    public function test_scoped_taxon_can_be_passed_as_active_filter()
    {
        foreach (CatalogContext::drivers() as $catalog) {

            // Create catalog
            $taxonomy = $catalog->createTaxonomy();
            $taxon1 = $catalog->createTaxon('taxon-1');
            $taxon2 = $catalog->createTaxon('taxon-2', $taxonomy->taxonomyId->get(), $taxon1->taxonId->get());

            $product = $catalog->createProduct();
            $catalog->linkProductToTaxon($product->productId->get(), $taxon1->taxonId->get());
            $catalog->linkProductToTaxon($product->productId->get(), $taxon2->taxonId->get());

            // Fetch active filters
            $activeFilters = $catalog->repos()->taxonFilters()->getActiveFilters(
                ['taxon-1'],
                ['taxon-1-key-nl', 'taxon-2-key-nl']
            );

            $this->assertEquals(1, $activeFilters->total());
            $this->assertEquals('taxon-2', $activeFilters->first()->getId());
        }
    }

    public function test_it_keeps_main_taxon_as_filter_when_the_active_filters_are_no_children_of_main_taxon()
    {
        foreach (CatalogContext::drivers() as $catalog) {

            // Create catalog
            $taxonomy = $catalog->createTaxonomy();
            $taxon1 = $catalog->createTaxon('taxon-1');
            $taxon2 = $catalog->createTaxon('taxon-2');

            $product = $catalog->createProduct();
            $catalog->linkProductToTaxon($product->productId->get(), $taxon1->taxonId->get());
            $catalog->linkProductToTaxon($product->productId->get(), $taxon2->taxonId->get());

            // Fetch active filters
            $activeFilters = $catalog->repos()->taxonFilters()->getActiveFilters(
                ['taxon-1'],
                ['taxon-2-key-nl']
            );

            $this->assertEquals(2, $activeFilters->total());
            $this->assertEquals('taxon-1', $activeFilters->first()->getId());
            $this->assertEquals('taxon-2', $activeFilters[1]->getId());
        }
    }

    public function test_it_returns_empty_active_filter_if_taxon_key_does_not_exist()
    {
        foreach (CatalogContext::drivers() as $catalog) {

            $activeFilters = $catalog->repos()->taxonFilters()->getActiveFilters(
                ['xxx'],
                []
            );

            $this->assertEquals(0, $activeFilters->total());
        }
    }

    public function test_it_returns_empty_if_no_scoped_taxa_given()
    {
        foreach (CatalogContext::drivers() as $catalog) {
            $activeFilters = $catalog->repos()->taxonFilters()->getActiveFilters(
                [],
                []
            );

            $this->assertEquals(0, $activeFilters->total());
        }
    }

    public function test_it_returns_only_scoped_if_active_taxa_do_not_exist()
    {
        foreach (CatalogContext::drivers() as $catalog) {
            $taxonomy = $catalog->createTaxonomy();
            $taxon = $catalog->createTaxon('taxon-1');

            $product = $catalog->createProduct();
            $catalog->linkProductToTaxon($product->productId->get(), $taxon->taxonId->get());

            $activeFilters = $catalog->repos()->taxonFilters()->getActiveFilters(
                ['taxon-1'],
                ['nonexistent-key']
            );

            $this->assertEquals(1, $activeFilters->total());
            $this->assertEquals('taxon-1', $activeFilters->first()->getId());
        }
    }

    public function test_it_combines_child_and_unrelated_active_filters()
    {
        foreach (CatalogContext::drivers() as $catalog) {
            $taxonomy = $catalog->createTaxonomy();
            $taxon1 = $catalog->createTaxon('taxon-1');
            $taxonChild = $catalog->createTaxon('taxon-child', $taxonomy->taxonomyId->get(), $taxon1->taxonId->get());
            $taxonUnrelated = $catalog->createTaxon('taxon-unrelated');

            $product = $catalog->createProduct();
            $catalog->linkProductToTaxon($product->productId->get(), $taxon1->taxonId->get());
            $catalog->linkProductToTaxon($product->productId->get(), $taxonChild->taxonId->get());
            $catalog->linkProductToTaxon($product->productId->get(), $taxonUnrelated->taxonId->get());

            $activeFilters = $catalog->repos()->taxonFilters()->getActiveFilters(
                ['taxon-1'],
                ['taxon-child-key-nl', 'taxon-unrelated-key-nl']
            );

            $this->assertEquals(2, $activeFilters->total());
            $this->assertEquals('taxon-child', $activeFilters->first()->getId());
            $this->assertEquals('taxon-unrelated', $activeFilters[1]->getId());
        }
    }

    public function test_it_deduplicates_active_filters()
    {
        foreach (CatalogContext::drivers() as $catalog) {
            $catalog->createTaxonomy();
            $taxon = $catalog->createTaxon('taxon-1');

            $product = $catalog->createProduct();
            $catalog->linkProductToTaxon($product->productId->get(), $taxon->taxonId->get());

            $activeFilters = $catalog->repos()->taxonFilters()->getActiveFilters(
                ['taxon-1'],
                ['taxon-1-key-nl', 'taxon-1-key-nl']
            );

            // child taxon ontbreekt, dus scoped blijft
            $this->assertEquals(1, $activeFilters->total());
            $this->assertEquals('taxon-1', $activeFilters->first()->getId());
        }
    }
}
