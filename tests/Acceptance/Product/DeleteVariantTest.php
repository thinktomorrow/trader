<?php
declare(strict_types=1);

namespace Tests\Acceptance\Product;

use Tests\TestHelpers;
use Thinktomorrow\Trader\Application\Product\DeleteVariant;
use Thinktomorrow\Trader\Domain\Model\Product\Events\ProductTaxaUpdated;
use Thinktomorrow\Trader\Domain\Model\Product\Events\VariantDeleted;
use Thinktomorrow\Trader\Domain\Model\Product\Exceptions\CouldNotDeleteVariant;

class DeleteVariantTest extends ProductContext
{
    use TestHelpers;

    public function test_it_can_delete_a_variant()
    {
        $productId = $this->createAProduct('50', ['1', '2'], 'sku', ['title' => ['nl' => 'foobar nl']]);
        $variantId = $this->createAVariant($productId->get(), '12', '3', [], 'yyy-123');

        $this->catalogContext->catalogApps()->productApplication()->deleteVariant(new DeleteVariant($productId->get(), $variantId->get()));

        $this->assertEquals([
            new VariantDeleted($productId, $variantId),
            new ProductTaxaUpdated($productId), // because of the InMemoryRepo implementation.
        ], $this->eventDispatcher->releaseDispatchedEvents());
    }

    public function test_it_cannot_delete_last_remaining_variant()
    {
        $this->expectException(CouldNotDeleteVariant::class);

        $productId = $this->createAProduct('50', ['1', '2'], 'sku', ['title' => ['nl' => 'foobar nl']]);
        $variantId = $this->catalogContext->catalogRepos()->productRepository()->find($productId)->getVariants()[0]->variantId;

        $this->catalogContext->catalogApps()->productApplication()->deleteVariant(new DeleteVariant($productId->get(), $variantId->get()));

        $this->assertEquals([], $this->eventDispatcher->releaseDispatchedEvents());

        $this->assertCount(1, $this->catalogContext->catalogRepos()->productRepository()->find($productId)->getVariants());
    }
}
