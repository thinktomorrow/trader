<?php
declare(strict_types=1);

namespace Tests\Acceptance\Product;

use Tests\TestHelpers;
use Thinktomorrow\Trader\Application\Product\DeleteProduct;
use Thinktomorrow\Trader\Domain\Model\Product\Events\ProductDeleted;
use Thinktomorrow\Trader\Domain\Model\Product\Exceptions\CouldNotFindProduct;

class DeleteProductTest extends ProductContext
{
    use TestHelpers;

    /** @test */
    public function it_can_delete_product()
    {
        $productId = $this->createAProduct('50', ['1','2'], ['title' => ['nl' => 'foobar nl']]);

        $this->productApplication->deleteProduct(new DeleteProduct($productId->get()));

        $this->assertEquals([
            new ProductDeleted($productId),
        ], $this->eventDispatcher->releaseDispatchedEvents());

        $this->expectException(CouldNotFindProduct::class);
        $this->productRepository->find($productId);
    }
}
