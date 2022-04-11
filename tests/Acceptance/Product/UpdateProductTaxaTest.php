<?php
declare(strict_types=1);

namespace Tests\Acceptance\Product;

use Tests\TestHelpers;
use Thinktomorrow\Trader\Domain\Model\Product\Event\ProductTaxaUpdated;
use Thinktomorrow\Trader\Application\Product\UpdateProduct\UpdateProductTaxa;

class UpdateProductTaxaTest extends ProductContext
{
    use TestHelpers;

    /** @test */
    public function it_can_add_taxa()
    {
        $productId = $this->createAProduct('50', ['1','2'], ['title' => ['nl' => 'foobar nl']]);

        $this->productApplication->updateProductTaxa(new UpdateProductTaxa($productId->get(), ['1','3']));

        $product = $this->productRepository->find($productId);

        $this->assertEquals(['1','3'], $product->getMappedData()['taxon_ids']);

        $this->assertEquals([
            new ProductTaxaUpdated($productId),
        ], $this->eventDispatcher->releaseDispatchedEvents());
    }

    /** @test */
    public function it_can_remove_taxa()
    {
        $productId = $this->createAProduct('50', ['1','2'], ['title' => ['nl' => 'foobar nl']]);

        $this->productApplication->updateProductTaxa(new UpdateProductTaxa($productId->get(), []));

        $product = $this->productRepository->find($productId);

        $this->assertEquals([], $product->getMappedData()['taxon_ids']);

        $this->assertEquals([
            new ProductTaxaUpdated($productId),
        ], $this->eventDispatcher->releaseDispatchedEvents());
    }
}
