<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Illuminate\Support\Facades\DB;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Domain\Model\Product\Exceptions\CouldNotFindProduct;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyType;
use Thinktomorrow\Trader\Testing\Catalog\CatalogContext;

final class ProductRepositoryTest extends TestCase
{
    public function test_it_can_save_and_find_a_product()
    {
        foreach (CatalogContext::drivers() as $catalog) {

            $catalog->dontPersist();

            foreach ($catalog->products() as $product) {

                $catalog->repos()->productRepository()->save($product);

                $found = $catalog->repos()->productRepository()->find($product->productId);

                $this->assertEquals($product->productId, $found->productId);
                $this->assertCount(count($product->getProductTaxa()), $found->getProductTaxa());
            }
        }
    }

    public function test_it_can_delete_a_product()
    {
        $productsNotFound = 0;
        $productCountPerCatalog = 5;

        foreach (CatalogContext::drivers() as $catalog) {

            $catalog->dontPersist();

            foreach ($catalog->products() as $product) {

                $catalog->repos()->productRepository()->save($product);
                $product->releaseEvents();

                $catalog->repos()->productRepository()->delete($product->productId);

                try {
                    $catalog->repos()->productRepository()->find($product->productId);
                } catch (CouldNotFindProduct $e) {
                    $productsNotFound++;
                }
            }
        }

        $this->assertEquals(count(CatalogContext::drivers()) * $productCountPerCatalog, $productsNotFound); // 4 products per catalog driver
    }

    public function test_it_can_generate_a_next_reference()
    {
        foreach (CatalogContext::drivers() as $catalog) {
            $this->assertInstanceOf(ProductId::class, $catalog->repos()->productRepository()->nextReference());
        }
    }

    public function test_it_keeps_product_taxa_with_null_data_when_loading(): void
    {
        foreach (CatalogContext::drivers() as $catalog) {
            $suffix = uniqid();

            $taxonomy = $catalog->createTaxonomy('taxonomy-'.$suffix, TaxonomyType::property->value);
            $taxon = $catalog->createTaxon('taxon-'.$suffix, $taxonomy->taxonomyId->get());

            $product = $catalog->linkProductToTaxon(
                $catalog->createProduct('product-'.$suffix, 'variant-'.$suffix),
                $taxon,
            );

            DB::table('trader_taxa_products')
                ->where('product_id', $product->productId->get())
                ->where('taxon_id', $taxon->taxonId->get())
                ->update(['data' => null]);

            $found = $catalog->repos()->productRepository()->find($product->productId);
            $foundTaxonIds = array_map(fn ($productTaxon) => $productTaxon->taxonId->get(), $found->getProductTaxa());

            $this->assertContains($taxon->taxonId->get(), $foundTaxonIds);
        }
    }
}
