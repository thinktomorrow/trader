<?php

declare(strict_types=1);

namespace Tests\Acceptance\Product;

use Tests\TestHelpers;
use Thinktomorrow\Trader\Application\Product\DeleteProduct;
use Thinktomorrow\Trader\Application\Product\DeleteVariant;
use Thinktomorrow\Trader\Domain\Model\Product\Events\ProductDeleted;
use Thinktomorrow\Trader\Domain\Model\Product\Events\ProductTaxaUpdated;
use Thinktomorrow\Trader\Domain\Model\Product\Events\VariantDeleted;
use Thinktomorrow\Trader\Domain\Model\Product\Exceptions\CouldNotDeleteVariant;
use Thinktomorrow\Trader\Domain\Model\Product\Exceptions\CouldNotFindProduct;

class DeleteProductTest extends ProductContext
{
    use TestHelpers;

    public function test_it_can_delete_product()
    {
        $product = $this->catalogContext->createProduct();

        $this->catalogContext->apps()->productApplication()->deleteProduct(new DeleteProduct($product->productId->get()));

        $this->assertEquals([
            new ProductDeleted($product->productId),
        ], $this->catalogContext->apps()->getEventDispatcher()->releaseDispatchedEvents());

        $this->expectException(CouldNotFindProduct::class);
        $this->catalogContext->repos()->productRepository()->find($product->productId);
    }

    public function test_it_can_delete_a_variant()
    {
        $product = $this->catalogContext->createProduct();

        // Add a second variant to be able to delete one.
        $this->catalogContext->createVariant($product->productId->get(), 'variant-bbb');

        $variantId = $product->getVariants()[0]->variantId;

        $this->catalogContext->apps()->productApplication()->deleteVariant(new DeleteVariant($product->productId->get(), $variantId->get()));

        $this->assertEquals([
            new VariantDeleted($product->productId, $variantId),
            new ProductTaxaUpdated($product->productId), // because of the InMemoryRepo implementation.
        ], $this->catalogContext->apps()->getEventDispatcher()->releaseDispatchedEvents());
    }

    public function test_it_cannot_delete_last_remaining_variant()
    {
        $this->expectException(CouldNotDeleteVariant::class);

        $product = $this->catalogContext->createProduct();
        $variantId = $product->getVariants()[0]->variantId;

        $this->catalogContext->apps()->productApplication()->deleteVariant(new DeleteVariant($product->productId->get(), $variantId->get()));

        $this->assertCount(1, $this->catalogContext->repos()->productRepository()->find($product->productId)->getVariants());
    }
}
