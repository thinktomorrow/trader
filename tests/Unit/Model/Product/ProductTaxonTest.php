<?php
declare(strict_types=1);

namespace Tests\Unit\Model\Product;

use Tests\Unit\TestCase;
use Thinktomorrow\Trader\Domain\Model\Product\Events\ProductCreated;
use Thinktomorrow\Trader\Domain\Model\Product\Events\ProductTaxaUpdated;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\ProductTaxa\ProductTaxon;
use Thinktomorrow\Trader\Domain\Model\Product\ProductTaxa\VariantProperty;
use Thinktomorrow\Trader\Domain\Model\Taxon\TaxonId;

class ProductTaxonTest extends TestCase
{
    public function test_it_can_update_product_taxa(): void
    {
        $product = $this->catalogContext->createProduct();

        $product->updateProductTaxa([
            $productTaxon = ProductTaxon::create($product->productId, TaxonId::fromString('ppp')),
        ]);

        $this->assertEquals([
            new ProductCreated(ProductId::fromString('xxx')),
            new ProductTaxaUpdated(ProductId::fromString('xxx')),
        ], $product->releaseEvents());

        $this->assertEquals([$productTaxon->getMappedData()], $product->getChildEntities()[ProductTaxon::class]);
    }

    public function test_it_cannot_add_same_product_variant_property_twice()
    {
        $product = $this->catalogContext->createProduct();

        $product->updateProductTaxa([
            ProductTaxon::create($product->productId, TaxonId::fromString('bbb')),
            ProductTaxon::create($product->productId, TaxonId::fromString('bbb')),
        ]);

        $this->assertCount(1, $product->getChildEntities()[ProductTaxon::class]);
    }

    public function test_it_can_have_variant_properties(): void
    {
        $product = $this->catalogContext->createProduct();

        $product->updateProductTaxa([
            $variantProperty = VariantProperty::create($product->productId, TaxonId::fromString('ppp')),
        ]);

        $this->assertEquals([$variantProperty], $product->getVariantProperties());
    }

    public function test_it_rejects_invalid_product_taxa(): void
    {
        $product = $this->catalogContext->createProduct();

        $this->expectException(\InvalidArgumentException::class);

        $product->updateProductTaxa([new \stdClass()]);
    }
}
