<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Application\Product\Taxa\ProductTaxonItem;
use Thinktomorrow\Trader\Testing\Catalog\CatalogContext;

final class ProductDetailRepositoryTaxaTest extends TestCase
{
    public function test_it_can_get_product_taxa()
    {
        foreach (CatalogContext::drivers() as $catalog) {

            $catalog->dontPersist();

            foreach ($catalog->products() as $product) {

                if (! $product->hasVariants()) {
                    continue;
                }

                $originalProductTaxa = $product->getProductTaxa();

                $catalog->repos()->productRepository()->save($product);
                $product->releaseEvents();

                $productDetail = $catalog->findProductDetail($product->getVariants()[0]->variantId);

                $this->assertCount(count($originalProductTaxa), $productDetail->getTaxa());
                $this->assertContainsOnlyInstancesOf(ProductTaxonItem::class, $productDetail->getTaxa());

                if (count($originalProductTaxa) > 0) {
                    $this->assertEquals($product->productId->get(), $productDetail->getTaxa()[0]->getProductId());
                    $this->assertEqualsCanonicalizing(
                        array_map(fn ($productTaxon) => $productTaxon->taxonId->get(), $originalProductTaxa),
                        array_map(fn ($productTaxon) => $productTaxon->getTaxonId(), $productDetail->getTaxa())
                    );
                }
            }
        }
    }

    public function test_it_can_get_taxon_keys_by_product()
    {
        foreach (CatalogContext::drivers() as $catalog) {

            $catalog->dontPersist();

            foreach ($catalog->products() as $product) {

                if (! $product->hasVariants()) {
                    continue;
                }

                $originalProductTaxa = $product->getProductTaxa();

                $catalog->repos()->productRepository()->save($product);
                $product->releaseEvents();

                $productDetail = $catalog->findProductDetail($product->getVariants()[0]->variantId);

                $this->assertCount(count($originalProductTaxa), $productDetail->getTaxa());
                $this->assertContainsOnlyInstancesOf(ProductTaxonItem::class, $productDetail->getTaxa());

                if (count($originalProductTaxa) > 0) {
                    $this->assertEquals($product->productId->get(), $productDetail->getTaxa()[0]->getProductId());
                    $this->assertEqualsCanonicalizing(
                        array_map(fn ($productTaxon) => $productTaxon->taxonId->get(), $originalProductTaxa),
                        array_map(fn ($productTaxon) => $productTaxon->getTaxonId(), $productDetail->getTaxa())
                    );
                }
            }
        }
    }
}
