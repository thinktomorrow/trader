<?php
declare(strict_types=1);

namespace Tests\Acceptance\Product;

use Tests\TestHelpers;
use Thinktomorrow\Trader\Application\Product\UpdateProduct\UpdateProductData;
use Thinktomorrow\Trader\Domain\Model\Product\Events\ProductDataUpdated;
use Thinktomorrow\Trader\Domain\Model\Product\Events\ProductTaxaUpdated;

class UpdateProductDataTest extends ProductContext
{
    use TestHelpers;

    public function test_it_can_add_data()
    {
        $product = $this->catalogContext->createProduct();
        $productId = $product->productId;

        $dataPayload = ['foo' => 'bar'];

        $this->catalogContext->catalogApps()->productApplication()->updateProductData(new UpdateProductData($productId->get(), $dataPayload));

        $product = $this->catalogContext->catalogRepos()->productRepository()->find($productId);

        $this->assertEquals(json_encode($dataPayload), $product->getMappedData()['data']);

        $this->assertEquals([
            new ProductDataUpdated($productId),
            new ProductTaxaUpdated($productId), // because of the InMemoryRepo implementation.
        ], $this->catalogContext->catalogApps()->getEventDispatcher()->releaseDispatchedEvents());
    }

    public function test_it_overwrites_data_by_payload()
    {
        $product = $this->catalogContext->createProduct();
        $productId = $product->productId;

        $this->catalogContext->catalogApps()->productApplication()->updateProductData(new UpdateProductData($productId->get(), ['foo' => ['nl' => 'baz']]));

        $product = $this->catalogContext->catalogRepos()->productRepository()->find($productId);

        $this->assertEquals(json_encode(['foo' => ['nl' => 'baz']]), $product->getMappedData()['data']);
    }

    public function test_it_does_not_remove_data_not_in_payload()
    {
        $product = $this->catalogContext->createProduct('product-aaa', null, [], ['foo' => 'bar']);
        $productId = $product->productId;

        $this->catalogContext->catalogApps()->productApplication()->updateProductData(new UpdateProductData($productId->get(), ['label' => ['nl' => 'baz']]));

        $product = $this->catalogContext->catalogRepos()->productRepository()->find($productId);

        $this->assertEquals(json_encode(['foo' => 'bar', 'label' => ['nl' => 'baz']]), $product->getMappedData()['data']);
    }
}
