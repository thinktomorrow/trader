<?php
declare(strict_types=1);

namespace Tests\Acceptance\Product;

use Tests\TestHelpers;
use Thinktomorrow\Trader\Application\Product\UpdateProduct\UpdateProductTaxa;
use Thinktomorrow\Trader\Domain\Model\Product\Events\ProductTaxaUpdated;

class UpdateProductTaxaTest extends ProductContext
{
    use TestHelpers;

    public function test_it_can_add_taxa()
    {
        $productId = $this->createAProduct('50', ['1', '2'], 'sku', ['title' => ['nl' => 'foobar nl']]);

        $this->productApplication->updateProductTaxa(new UpdateProductTaxa($productId->get(), ['1', '3']));

        $product = $this->productRepository->find($productId);

        $this->assertEquals(['1', '3'], $product->getMappedData()['taxon_ids']);

        $this->assertEquals([
            new ProductTaxaUpdated($productId),
        ], $this->eventDispatcher->releaseDispatchedEvents());
    }

    public function test_it_can_remove_taxa()
    {
        $productId = $this->createAProduct('50', ['1', '2'], 'sku', ['title' => ['nl' => 'foobar nl']]);

        $this->productApplication->updateProductTaxa(new UpdateProductTaxa($productId->get(), []));

        $product = $this->productRepository->find($productId);

        $this->assertEquals([], $product->getMappedData()['taxon_ids']);

        $this->assertEquals([
            new ProductTaxaUpdated($productId),
        ], $this->eventDispatcher->releaseDispatchedEvents());
    }
}
