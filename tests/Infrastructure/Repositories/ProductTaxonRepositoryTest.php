<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Domain\Model\Product\ProductTaxa\ProductTaxon;
use Thinktomorrow\Trader\Domain\Model\Product\ProductTaxa\VariantProperty;
use Thinktomorrow\Trader\Testing\Catalog\CatalogContext;

final class ProductTaxonRepositoryTest extends TestCase
{
    public function test_it_can_get_product_taxa_by_product()
    {
        foreach (CatalogContext::drivers() as $catalog) {

            $catalog->dontPersist();

            foreach ($catalog->products() as $product) {

                $originalProductTaxa = $product->getProductTaxa();

                $catalog->repos()->productRepository()->save($product);
                $product->releaseEvents();

                $product = $catalog->repos()->productRepository()->find($product->productId);

                $this->assertCount(count($originalProductTaxa), $product->getProductTaxa());
                $this->assertContainsOnlyInstancesOf(ProductTaxon::class, $product->getProductTaxa());

                if (count($originalProductTaxa) > 0) {
                    $this->assertEquals($product->productId->get(), $product->getProductTaxa()[0]->productId);
                    $this->assertEqualsCanonicalizing(
                        array_map(fn ($productTaxon) => $productTaxon->taxonId->get(), $originalProductTaxa),
                        array_map(fn ($productTaxon) => $productTaxon->taxonId->get(), $product->getProductTaxa())
                    );
                }
            }
        }
    }

    public function test_it_can_get_variant_properties_by_product()
    {
        foreach (CatalogContext::drivers() as $catalog) {

            $catalog->dontPersist();

            foreach ($catalog->products() as $product) {

                $count = count($product->getVariantProperties());

                $catalog->repos()->productRepository()->save($product);
                $product->releaseEvents();

                $product = $catalog->repos()->productRepository()->find($product->productId);

                $this->assertCount($count, $product->getVariantProperties());
                $this->assertContainsOnlyInstancesOf(VariantProperty::class, $product->getVariantProperties());

                if ($count > 0) {
                    $this->assertEquals($product->productId->get(), $product->getVariantProperties()[0]->productId);
                    $this->assertEquals('taxon-bbb', $product->getVariantProperties()[0]->taxonId);
                }
            }
        }
    }

    public function test_it_can_get_variant_properties_by_variant()
    {
        foreach (CatalogContext::drivers() as $catalog) {

            $catalog->dontPersist();

            foreach ($catalog->products() as $product) {

                if (! $product->hasVariants()) {
                    continue;
                }

                $variant = $product->getVariants()[0];

                $count = count($variant->getVariantProperties());

                $catalog->repos()->productRepository()->save($product);
                $product->releaseEvents();

                $variant = $catalog->repos()->productRepository()->find($product->productId)->getVariants()[0];

                $this->assertCount($count, $variant->getVariantProperties());
                $this->assertContainsOnlyInstancesOf(\Thinktomorrow\Trader\Domain\Model\Product\VariantTaxa\VariantProperty::class, $variant->getVariantProperties());

                if ($count > 0) {
                    $this->assertEquals($product->productId->get(), $product->getVariantProperties()[0]->productId);
                    $this->assertEquals($variant->variantId->get(), $product->getVariantProperties()[0]->variantId);
                    $this->assertEquals('taxon-aaa', $product->getVariantProperties()[0]->taxonId);
                }
            }
        }
    }
}
