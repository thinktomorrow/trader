<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Vine;

use Tests\Infrastructure\Common\Catalog;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Domain\Common\Locale;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonState;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyState;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyType;
use Thinktomorrow\Trader\Infrastructure\Test\TestTraderConfig;

final class GetAvailableFiltersTest extends TestCase
{
    public function test_it_can_retrieve_an_available_taxon_filter_tree()
    {
        foreach (Catalog::drivers() as $catalog) {

            // Catalog setup
            $catalog->createTaxonomy();
            $taxon = $catalog->createTaxon();
            $product = $catalog->createProduct();
            $catalog->linkProductToTaxon($product->productId->get(), $taxon->taxonId->get());

            // Compose filter tree
            $filters = $catalog->repos->taxonFilters()->getAvailableFilters(Locale::fromString('nl'), ['taxon-aaa']);

            $this->assertCount(1, $filters);
            $this->assertCount(1, reset($filters)['taxa']);
        }
    }

    public function test_it_can_retrieve_all_product_ids()
    {
        foreach (Catalog::drivers() as $catalog) {

            // Catalog setup
            $catalog->createTaxonomy();
            $taxon = $catalog->createTaxon();
            $product = $catalog->createProduct();
            $product2 = $catalog->createProduct('product-bbb', 'variant-bbb');

            $catalog->linkProductToTaxon($product->productId->get(), $taxon->taxonId->get());
            $catalog->linkProductToTaxon($product2->productId->get(), $taxon->taxonId->get());

            $productIds = $catalog->repos->taxonFilters()->getProductIds([$taxon->taxonId->get()]);

            $this->assertEquals([
                $product->productId->get(), $product2->productId->get(),
            ], $productIds);
        }
    }

    public function test_it_can_retrieve_all_grid_product_ids()
    {
        foreach (Catalog::drivers() as $catalog) {

            // Catalog setup
            $catalog->createTaxonomy();
            $taxon = $catalog->createTaxon();
            $product = $catalog->createProduct();
            $product2 = $catalog->createProduct('product-bbb', 'variant-bbb');

            // Product 1 is not shown in grid
            $product->getVariants()[0]->showInGrid(false);
            $catalog->saveProduct($product);

            $catalog->linkProductToTaxon($product->productId->get(), $taxon->taxonId->get());
            $catalog->linkProductToTaxon($product2->productId->get(), $taxon->taxonId->get());

            $productIds = $catalog->repos->taxonFilters()->getGridProductIds([$taxon->taxonId->get()]);

            $this->assertEquals([
                $product2->productId->get(),
            ], $productIds);
        }
    }

    public function test_taxon_without_grid_product_is_not_added_to_filter_tree()
    {
        foreach (Catalog::drivers() as $catalog) {

            // Catalog setup
            $catalog->createTaxonomy();
            $taxon = $catalog->createTaxon();
            $product = $catalog->createProduct();

            // Product 1 is not shown in grid
            $product->getVariants()[0]->showInGrid(false);
            $catalog->saveProduct($product);

            $catalog->linkProductToTaxon($product->productId->get(), $taxon->taxonId->get());

            $filters = $catalog->repos->taxonFilters()->getAvailableFilters(Locale::fromString('nl'), [$taxon->taxonId->get()]);

            $this->assertCount(1, $filters);
            $this->assertCount(0, reset($filters)['taxa']);
        }
    }

    public function test_filters_are_ordered()
    {
        foreach (Catalog::drivers() as $catalog) {

            // Catalog setup
            $catalog->createTaxonomy();
            $taxon = $catalog->createTaxon();
            $taxon2 = $catalog->createTaxon('taxon-bbb');

            // Specify order of each taxon
            $taxon->changeOrder(2);
            $taxon2->changeOrder(1);
            $catalog->saveTaxon($taxon);
            $catalog->saveTaxon($taxon2);

            // Link product to both taxa
            $product = $catalog->createProduct();
            $catalog->linkProductToTaxon($product->productId->get(), $taxon->taxonId->get());
            $catalog->linkProductToTaxon($product->productId->get(), $taxon2->taxonId->get());

            // Compose filter tree
            $filters = $catalog->repos->taxonFilters()->getAvailableFilters(Locale::fromString('nl'), ['taxon-aaa']);

            $this->assertCount(1, $filters);
            $this->assertCount(2, reset($filters)['taxa']);
            $this->assertEquals('taxon-bbb', reset($filters)['taxa'][0]->getId());
            $this->assertEquals('taxon-aaa', reset($filters)['taxa'][1]->getId());
        }
    }

    public function test_it_excludes_offline_taxonomy()
    {
        foreach (Catalog::drivers() as $catalog) {

            // Catalog setup
            $taxonomy = $catalog->createTaxonomy();
            $taxon = $catalog->createTaxon();

            // Put taxonomy offline
            $taxonomy->changeState(TaxonomyState::offline);
            $catalog->saveTaxonomy($taxonomy);

            // Link to product
            $product = $catalog->createProduct();
            $catalog->linkProductToTaxon($product->productId->get(), $taxon->taxonId->get());

            // Compose filter tree
            $filters = $catalog->repos->taxonFilters()->getAvailableFilters(Locale::fromString('nl'), ['taxon-aaa']);

            $this->assertCount(0, $filters);
        }
    }

    public function test_it_excludes_offline_taxon()
    {
        foreach (Catalog::drivers() as $catalog) {

            // Catalog setup
            $catalog->createTaxonomy();
            $taxon = $catalog->createTaxon();

            // Put taxon offline
            $taxon->changeState(TaxonState::offline);
            $catalog->saveTaxon($taxon);

            // Link to product
            $product = $catalog->createProduct();
            $catalog->linkProductToTaxon($product->productId->get(), $taxon->taxonId->get());

            // Compose filter tree
            $filters = $catalog->repos->taxonFilters()->getAvailableFilters(Locale::fromString('nl'), ['taxon-aaa']);

            $this->assertCount(1, $filters);
            $this->assertCount(0, reset($filters)['taxa']);
        }
    }

    public function test_taxon_filters_are_grouped_by_taxonomy()
    {
        foreach (Catalog::drivers() as $catalog) {

            // Catalog setup
            $catalog->createTaxonomy();
            $catalog->createTaxonomy('taxonomy-bbb');
            $taxon = $catalog->createTaxon();
            $taxon2 = $catalog->createTaxon('taxon-bbb', 'taxonomy-bbb');

            // Link product to both taxa
            $product = $catalog->createProduct();
            $catalog->linkProductToTaxon($product->productId->get(), $taxon->taxonId->get());
            $catalog->linkProductToTaxon($product->productId->get(), $taxon2->taxonId->get());

            // Compose filter tree
            $filters = $catalog->repos->taxonFilters()->getAvailableFilters(Locale::fromString('nl'), ['taxon-aaa']);

            $this->assertCount(2, $filters);
            $this->assertCount(1, $filters[0]['taxa']);
            $this->assertCount(1, $filters[1]['taxa']);
            $this->assertEquals('taxon-aaa', $filters[0]['taxa'][0]->getId());
            $this->assertEquals('taxon-bbb', $filters[1]['taxa'][0]->getId());
        }
    }

    public function test_it_returns_empty_filter_if_taxon_key_does_not_exist()
    {
        foreach (Catalog::drivers() as $catalog) {

            // Compose filter tree
            $filters = $catalog->repos->taxonFilters()->getAvailableFilters(Locale::fromString('nl'), ['xxx']);

            $this->assertCount(0, $filters);
        }
    }

    public function test_it_returns_empty_if_no_scoped_taxa_given()
    {
        foreach (Catalog::drivers() as $catalog) {
            $filters = $catalog->repos->taxonFilters()->getAvailableFilters(Locale::fromString('nl'), []);
            $this->assertEquals([], $filters);
        }
    }

    public function test_it_returns_empty_if_scoped_taxon_has_no_products()
    {
        foreach (Catalog::drivers() as $catalog) {
            $catalog->createTaxonomy();
            $taxon = $catalog->createTaxon();

            $filters = $catalog->repos->taxonFilters()->getAvailableFilters(Locale::fromString('nl'), [$taxon->taxonId->get()]);

            $this->assertCount(1, $filters);
            $this->assertCount(0, reset($filters)['taxa']);
        }
    }

    public function test_main_category_taxon_returns_only_children()
    {
        foreach (Catalog::drivers() as $catalog) {
            $taxonomy = $catalog->createTaxonomy('main-category');
            TestTraderConfig::setMainCategoryTaxonomyId($taxonomy->taxonomyId->get());

            $parent = $catalog->createTaxon('parent', $taxonomy->taxonomyId->get());
            $child = $catalog->createTaxon('child', $taxonomy->taxonomyId->get(), $parent->taxonId->get());

            $product = $catalog->createProduct();
            $catalog->linkProductToTaxon($product->productId->get(), $child->taxonId->get());

            $filters = $catalog->repos->taxonFilters()->getAvailableFilters(Locale::fromString('nl'), [$parent->taxonId->get()]);

            $this->assertCount(1, $filters);
            $this->assertEquals('child', $filters[0]['taxa'][0]->getId());
        }
    }

    public function test_variant_property_taxonomy_only_includes_taxa_with_variants()
    {
        foreach (Catalog::drivers() as $catalog) {
            $taxonomy = $catalog->createTaxonomy('color-taxonomy', TaxonomyType::variant_property->value);
            $taxon = $catalog->createTaxon('red', $taxonomy->taxonomyId->get());

            // Variant with the variant property taxon
            $product = $catalog->createProduct();
            $catalog->linkVariantToTaxon($product->productId->get(), $product->getVariants()[0]->variantId->get(), $taxon->taxonId->get());

            $filters = $catalog->repos->taxonFilters()->getAvailableFilters(Locale::fromString('nl'), [$taxon->taxonId->get()]);

            $this->assertCount(1, $filters);
            $this->assertEquals('red', $filters[0]['taxa'][0]->getId());
        }
    }

    public function test_get_product_ids_returns_unique_ids_even_if_product_in_multiple_taxa()
    {
        foreach (Catalog::drivers() as $catalog) {
            $catalog->createTaxonomy();
            $taxon1 = $catalog->createTaxon('taxon-1');
            $taxon2 = $catalog->createTaxon('taxon-2');

            $product = $catalog->createProduct();
            $catalog->linkProductToTaxon($product->productId->get(), $taxon1->taxonId->get());
            $catalog->linkProductToTaxon($product->productId->get(), $taxon2->taxonId->get());

            $ids = $catalog->repos->taxonFilters()->getProductIds([$taxon1->taxonId->get(), $taxon2->taxonId->get()]);

            $this->assertEquals([$product->productId->get()], $ids);
        }
    }

}
