<?php
declare(strict_types=1);

namespace Tests\Acceptance\Product;

use Tests\TestHelpers;
use Thinktomorrow\Trader\Application\Product\UpdateProduct\UpdateProductData;
use Thinktomorrow\Trader\Domain\Model\Product\Events\ProductDataUpdated;

class UpdateProductDataTest extends ProductContext
{
    use TestHelpers;

    public function test_it_can_add_data()
    {
        $productId = $this->createAProduct('50', []);
        $dataPayload = ['foo' => 'bar'];

        $this->productApplication->updateProductData(new UpdateProductData($productId->get(), $dataPayload));

        $product = $this->productRepository->find($productId);

        $this->assertEquals(json_encode($dataPayload), $product->getMappedData()['data']);

        $this->assertEquals([
            new ProductDataUpdated($productId),
        ], $this->eventDispatcher->releaseDispatchedEvents());
    }

    public function test_it_overwrites_data_by_payload()
    {
        $productId = $this->createAProduct('50', [], 'sku', ['foo' => 'bar']);

        $this->productApplication->updateProductData(new UpdateProductData($productId->get(), ['foo' => ['nl' => 'baz']]));

        $product = $this->productRepository->find($productId);

        $this->assertEquals(json_encode(['foo' => ['nl' => 'baz']]), $product->getMappedData()['data']);
    }

    public function test_it_does_not_remove_data_not_in_payload()
    {
        $productId = $this->createAProduct('50', [], 'sku', ['foo' => 'bar']);

        $this->productApplication->updateProductData(new UpdateProductData($productId->get(), ['label' => ['nl' => 'baz']]));

        $product = $this->productRepository->find($productId);

        $this->assertEquals(json_encode(['foo' => 'bar', 'label' => ['nl' => 'baz']]), $product->getMappedData()['data']);
    }
}
