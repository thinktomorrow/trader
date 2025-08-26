<?php
declare(strict_types=1);

namespace Tests\Acceptance\Product;

use Tests\TestHelpers;
use Thinktomorrow\Trader\Application\Product\UpdateProduct\UpdateProductTaxa;
use Thinktomorrow\Trader\Domain\Model\Product\Events\ProductTaxaUpdated;
use Thinktomorrow\Trader\Domain\Model\Product\ProductTaxa\ProductTaxon;

class UpdateProductTaxaTest extends ProductContext
{
    use TestHelpers;

    public function test_it_can_add_taxa()
    {
        $productId = $this->createAProduct('50', ['1', '2'], 'sku', ['title' => ['nl' => 'foobar nl']]);

        $this->productApplication->updateProductTaxa(new UpdateProductTaxa($productId->get(), ['1', '3']));

        $product = $this->productRepository->find($productId);

        $this->assertContainsOnlyInstancesOf(ProductTaxon::class, $product->getProductTaxa());
        $this->assertCount(2, $product->getChildEntities()[ProductTaxon::class]);
        $this->assertEquals([
            [
                'product_id' => $productId->get(),
                'taxon_id' => '1',
                'data' => json_encode([]),
                'state' => 'online',
            ],
            [
                'product_id' => $productId->get(),
                'taxon_id' => '3',
                'data' => json_encode([]),
                'state' => 'online',
            ],
        ], $product->getChildEntities()[ProductTaxon::class]);

        $this->assertEquals([
            new ProductTaxaUpdated($productId),
        ], $this->eventDispatcher->releaseDispatchedEvents());
    }

    public function test_it_can_remove_taxa()
    {
        $productId = $this->createAProduct('50', ['1', '2'], 'sku', ['title' => ['nl' => 'foobar nl']]);

        $this->productApplication->updateProductTaxa(new UpdateProductTaxa($productId->get(), []));

        $product = $this->productRepository->find($productId);

        $this->assertCount(0, $product->getChildEntities()[ProductTaxon::class]);

        $this->assertEquals([
            new ProductTaxaUpdated($productId),
        ], $this->eventDispatcher->releaseDispatchedEvents());
    }

    public function test_when_removing_taxa_all_corresponding_taxa_on_variants_are_removed_as_well()
    {
        $product = $this->createProductWithVariantAndTaxon();
        $this->productRepository->save($product);

        $this->assertCount(1, $product->getProductTaxa());
        $this->assertCount(1, $product->getVariants()[0]->getVariantTaxa());

        $this->productApplication->updateProductTaxa(new UpdateProductTaxa($product->productId->get(), []));

        $product = $this->productRepository->find($product->productId);

        $this->assertCount(0, $product->getProductTaxa());
        $this->assertCount(0, $product->getVariants()[0]->getVariantTaxa());
    }
}
