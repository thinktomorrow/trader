<?php
declare(strict_types=1);

namespace Tests\Unit\Model\Product;

use Tests\Unit\TestCase;
use Thinktomorrow\Trader\Domain\Model\Product\Events\ProductCreated;
use Thinktomorrow\Trader\Domain\Model\Product\Events\ProductTaxaUpdated;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\ProductTaxa\ProductTaxon;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyId;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyType;

class ProductTaxonTest extends TestCase
{
    public function test_it_can_update_product_taxa(): void
    {
        $product = $this->createProduct();

        $product->updateProductTaxa([
            $productTaxon = ProductTaxon::create($product->productId, TaxonomyId::fromString('ooo'), TaxonomyType::variant_property, TaxonId::fromString('ppp')),
        ]);

        $this->assertEquals([
            new ProductCreated(ProductId::fromString('xxx')),
            new ProductTaxaUpdated(ProductId::fromString('xxx')),
        ], $product->releaseEvents());

        $this->assertEquals([$productTaxon->getMappedData()], $product->getChildEntities()[ProductTaxon::class]);
    }

    public function test_it_cannot_add_same_product_variant_property_twice()
    {
        $product = $this->createProduct();

        $product->updateProductTaxa([
            $productVariantProperty = ProductTaxon::create($product->productId, TaxonomyId::fromString('aaa'), TaxonomyType::variant_property, TaxonId::fromString('bbb')),
            ProductTaxon::create($product->productId, TaxonomyId::fromString('aaa'), TaxonomyType::variant_property, TaxonId::fromString('bbb')),
        ]);

        $this->assertCount(1, $product->getChildEntities()[ProductTaxon::class]);
    }

    public function test_when_removing_properties_all_corresponding_properties_on_variants_are_removed_as_well()
    {
        $product = $this->createProductWithVariant();

        $this->assertCount(1, $product->getProductTaxa());
        $this->assertCount(1, $product->getVariants()[0]->getVariantTaxa());

        $product->updateProductTaxa([]);

        $this->assertCount(0, $product->getProductTaxa());
        $this->assertCount(0, $product->getVariants()[0]->getVariantTaxa());
    }
}
