<?php
declare(strict_types=1);

namespace Tests\Acceptance\Product;

use Tests\TestHelpers;
use Thinktomorrow\Trader\Application\Product\UpdateProduct\UpdateVariantTaxa;
use Thinktomorrow\Trader\Domain\Model\Product\Events\ProductTaxaUpdated;
use Thinktomorrow\Trader\Domain\Model\Product\Events\VariantUpdated;
use Thinktomorrow\Trader\Domain\Model\Product\VariantTaxa\VariantTaxon;

class UpdateVariantTaxaTest extends ProductContext
{
    use TestHelpers;

    public function test_it_can_add_taxa()
    {
        $productId = $this->createAProduct('50', ['1', '2'], 'sku', ['title' => ['nl' => 'foobar nl']]);
        $variantId = $this->catalogContext->catalogRepos()->productRepository()->find($productId)->getVariants()[0]->variantId;

        $this->catalogContext->catalogApps()->productApplication()->updateVariantTaxa(new UpdateVariantTaxa($productId->get(), $variantId->get(), ['1', '3']));

        $variant = $this->catalogContext->catalogRepos()->productRepository()->find($productId)->getVariants()[0];

        $this->assertContainsOnlyInstancesOf(VariantTaxon::class, $variant->getVariantTaxa());
        $this->assertCount(2, $variant->getChildEntities()[VariantTaxon::class]);
        $this->assertEquals([
            [
                'variant_id' => $variantId->get(),
                'taxon_id' => '1',
                'data' => json_encode([]),
                'state' => 'online',
            ],
            [
                'variant_id' => $variantId->get(),
                'taxon_id' => '3',
                'data' => json_encode([]),
                'state' => 'online',
            ],
        ], $variant->getChildEntities()[VariantTaxon::class]);

        $this->assertEquals([
            new VariantUpdated($productId, $variantId),
            new ProductTaxaUpdated($productId), // Duplicate because of the InMemoryRepo implementation.
        ], $this->eventDispatcher->releaseDispatchedEvents());
    }

    public function test_it_can_remove_taxa()
    {
        $productId = $this->createAProduct('50', ['1', '2'], 'sku', ['title' => ['nl' => 'foobar nl']]);
        $variantId = $this->catalogContext->catalogRepos()->productRepository()->find($productId)->getVariants()[0]->variantId;

        $this->catalogContext->catalogApps()->productApplication()->updateVariantTaxa(new UpdateVariantTaxa(
            $productId->get(),
            $variantId->get(),
            []
        ));

        $variant = $this->catalogContext->catalogRepos()->productRepository()->find($productId)->getVariants()[0];

        $this->assertCount(0, $variant->getChildEntities()[VariantTaxon::class]);

        $this->assertEquals([
            new VariantUpdated($productId, $variantId),
            new ProductTaxaUpdated($productId), // Duplicate because of the InMemoryRepo implementation.
        ], $this->eventDispatcher->releaseDispatchedEvents());
    }
}
