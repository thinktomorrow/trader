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
        $this->createAndSaveTaxonomiesAndTaxa(['ooo'], ['xxx', 'yyy']);
        $productId = $this->createAProduct('50', [], 'sku', ['title' => ['nl' => 'foobar nl']]);

        $this->catalogContext->catalogApps()->productApplication()->updateProductTaxa(new UpdateProductTaxa($productId->get(), ['xxx', 'yyy'], ['ooo']));

        $product = $this->catalogContext->catalogRepos()->productRepository()->find($productId);

        $this->assertContainsOnlyInstancesOf(ProductTaxon::class, $product->getProductTaxa());
        $this->assertCount(2, $product->getChildEntities()[ProductTaxon::class]);
        $this->assertEquals([
            [
                'product_id' => $productId->get(),
                'taxon_id' => 'xxx',
                'data' => json_encode([]),
                'state' => 'online',
            ],
            [
                'product_id' => $productId->get(),
                'taxon_id' => 'yyy',
                'data' => json_encode([]),
                'state' => 'online',
            ],
        ], $product->getChildEntities()[ProductTaxon::class]);

        $this->assertEquals([
            new ProductTaxaUpdated($productId), // Duplicate because of the InMemoryRepo implementation.
            new ProductTaxaUpdated($productId),
        ], $this->eventDispatcher->releaseDispatchedEvents());
    }

    public function test_it_can_add_taxa_without_taxonomy_scope()
    {
        $productId = $this->createAProduct('50', [], 'sku', ['title' => ['nl' => 'foobar nl']]);

        $this->catalogContext->catalogApps()->productApplication()->updateProductTaxa(new UpdateProductTaxa($productId->get(), ['xxx', 'yyy']));

        $product = $this->catalogContext->catalogRepos()->productRepository()->find($productId);

        $this->assertContainsOnlyInstancesOf(ProductTaxon::class, $product->getProductTaxa());
        $this->assertCount(2, $product->getChildEntities()[ProductTaxon::class]);
        $this->assertEquals([
            [
                'product_id' => $productId->get(),
                'taxon_id' => 'xxx',
                'data' => json_encode([]),
                'state' => 'online',
            ],
            [
                'product_id' => $productId->get(),
                'taxon_id' => 'yyy',
                'data' => json_encode([]),
                'state' => 'online',
            ],
        ], $product->getChildEntities()[ProductTaxon::class]);

        $this->assertEquals([
            new ProductTaxaUpdated($productId), // Duplicate because of the InMemoryRepo implementation.
            new ProductTaxaUpdated($productId),
        ], $this->eventDispatcher->releaseDispatchedEvents());
    }

    public function test_it_can_remove_taxa()
    {
        $productId = $this->createAProduct('50', ['1', '2'], 'sku', ['title' => ['nl' => 'foobar nl']]);

        $this->catalogContext->catalogApps()->productApplication()->updateProductTaxa(new UpdateProductTaxa($productId->get(), [], []));

        $product = $this->catalogContext->catalogRepos()->productRepository()->find($productId);

        $this->assertCount(0, $product->getChildEntities()[ProductTaxon::class]);

        $this->assertEquals([
            new ProductTaxaUpdated($productId), // Duplicate because of the InMemoryRepo implementation.
            new ProductTaxaUpdated($productId),
        ], $this->eventDispatcher->releaseDispatchedEvents());
    }

    public function test_when_removing_taxa_all_corresponding_taxa_on_variants_are_removed_as_well()
    {
        $product = $this->createProductWithVariantAndTaxon();

        $this->catalogContext->catalogRepos()->productRepository()->save($product);

        $this->assertCount(1, $product->getProductTaxa());
        $this->assertCount(1, $product->getVariants()[0]->getVariantTaxa());

        $this->catalogContext->catalogApps()->productApplication()->updateProductTaxa(new UpdateProductTaxa($product->productId->get(), [], []));

        $product = $this->catalogContext->catalogRepos()->productRepository()->find($product->productId);

        $this->assertCount(0, $product->getProductTaxa());
        $this->assertCount(0, $product->getVariants()[0]->getVariantTaxa());
    }

    public function test_when_updating_taxa_all_existing_taxa_on_variants_remain_intact(): void
    {
        $this->createAndSaveTaxonomiesAndTaxa(['ooo'], ['xxx', 'yyy']);
        $product = $this->createProductWithVariantAndTaxon();

        $this->catalogContext->catalogRepos()->productRepository()->save($product);

        $this->assertCount(1, $product->getProductTaxa());
        $this->assertCount(1, $product->getVariants()[0]->getVariantTaxa());

        // Add a different taxon, existing variant taxa remain intact.
        $this->catalogContext->catalogApps()->productApplication()->updateProductTaxa(new UpdateProductTaxa($product->productId->get(), ['xxx', 'yyy'], []));

        $product = $this->catalogContext->catalogRepos()->productRepository()->find($product->productId);

        $this->assertCount(2, $product->getProductTaxa());
        $this->assertEquals('xxx', $product->getProductTaxa()[0]->taxonId->get());
        $this->assertEquals('yyy', $product->getProductTaxa()[1]->taxonId->get());

        // Existing variant taxa remain intact.
        $this->assertCount(1, $product->getVariants()[0]->getVariantTaxa());
        $this->assertEquals('xxx', $product->getVariants()[0]->getVariantTaxa()[0]->taxonId->get());
    }
}
