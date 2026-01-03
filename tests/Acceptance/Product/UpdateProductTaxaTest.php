<?php
declare(strict_types=1);

namespace Tests\Acceptance\Product;

use Tests\TestHelpers;
use Thinktomorrow\Trader\Application\Product\UpdateProduct\UpdateProductTaxa;
use Thinktomorrow\Trader\Domain\Model\Product\Events\ProductTaxaUpdated;
use Thinktomorrow\Trader\Domain\Model\Product\ProductTaxa\ProductTaxon;
use Thinktomorrow\Trader\Domain\Model\Taxonomy\TaxonomyType;

class UpdateProductTaxaTest extends ProductContext
{
    use TestHelpers;

    public function test_it_can_add_taxa()
    {
        $this->catalogContext->createTaxonomy();
        $this->catalogContext->createTaxon();
        $this->catalogContext->createTaxon('taxon-bbb');

        $product = $this->catalogContext->createProduct();
        $productId = $product->productId;

        $this->catalogContext->apps()->productApplication()->updateProductTaxa(new UpdateProductTaxa($productId->get(), ['taxon-aaa', 'taxon-bbb'], ['taxonomy-aaa']));

        $product = $this->catalogContext->repos()->productRepository()->find($productId);

        $this->assertContainsOnlyInstancesOf(ProductTaxon::class, $product->getProductTaxa());
        $this->assertCount(2, $product->getChildEntities()[ProductTaxon::class]);
        $this->assertEquals([
            [
                'product_id' => $productId->get(),
                'taxon_id' => 'taxon-aaa',
                'data' => json_encode([]),
                'state' => 'online',
            ],
            [
                'product_id' => $productId->get(),
                'taxon_id' => 'taxon-bbb',
                'data' => json_encode([]),
                'state' => 'online',
            ],
        ], $product->getChildEntities()[ProductTaxon::class]);

        $this->assertEquals([
            new ProductTaxaUpdated($productId), // Duplicate because of the InMemoryRepo implementation.
            new ProductTaxaUpdated($productId),
        ], $this->catalogContext->apps()->getEventDispatcher()->releaseDispatchedEvents());
    }

    public function test_it_can_add_taxa_without_taxonomy_scope()
    {
        $this->catalogContext->createTaxon();
        $product = $this->catalogContext->createProduct();
        $productId = $product->productId;

        $this->catalogContext->apps()->productApplication()->updateProductTaxa(new UpdateProductTaxa($productId->get(), ['taxon-aaa']));

        $product = $this->catalogContext->repos()->productRepository()->find($productId);

        $this->assertContainsOnlyInstancesOf(ProductTaxon::class, $product->getProductTaxa());
        $this->assertCount(1, $product->getChildEntities()[ProductTaxon::class]);
        $this->assertEquals([
            [
                'product_id' => $productId->get(),
                'taxon_id' => 'taxon-aaa',
                'data' => json_encode([]),
                'state' => 'online',
            ],
        ], $product->getChildEntities()[ProductTaxon::class]);

        $this->assertEquals([
            new ProductTaxaUpdated($productId), // Duplicate because of the InMemoryRepo implementation.
            new ProductTaxaUpdated($productId),
        ], $this->catalogContext->apps()->getEventDispatcher()->releaseDispatchedEvents());
    }

    public function test_it_can_remove_taxa()
    {
        $product = $this->catalogContext->createProduct();
        $productId = $product->productId;

        $this->catalogContext->apps()->productApplication()->updateProductTaxa(new UpdateProductTaxa($productId->get(), [], []));

        $product = $this->catalogContext->repos()->productRepository()->find($productId);

        $this->assertCount(0, $product->getChildEntities()[ProductTaxon::class]);

        $this->assertEquals([
            new ProductTaxaUpdated($productId), // Duplicate because of the InMemoryRepo implementation.
            new ProductTaxaUpdated($productId),
        ], $this->catalogContext->apps()->getEventDispatcher()->releaseDispatchedEvents());
    }

    public function test_when_removing_taxa_all_corresponding_variant_properties_on_variants_are_removed_as_well()
    {
        $product = $this->catalogContext->createProduct();
        $productId = $product->productId;
        $variantId = $product->getVariants()[0]->variantId;

        $this->catalogContext->createTaxonomy('taxonomy-aaa', TaxonomyType::variant_property->value);
        $taxon = $this->catalogContext->createTaxon();
        $this->catalogContext->linkProductToTaxon($productId->get(), $taxon->taxonId->get());
        $this->catalogContext->linkVariantToTaxon($productId->get(), $variantId->get(), $taxon->taxonId->get());

        $this->catalogContext->repos()->productRepository()->save($product);

        $this->assertCount(1, $product->getProductTaxa());
        $this->assertCount(1, $product->getVariants()[0]->getVariantTaxa());

        $this->catalogContext->apps()->productApplication()->updateProductTaxa(new UpdateProductTaxa($product->productId->get(), [], []));

        $product = $this->catalogContext->repos()->productRepository()->find($product->productId);

        $this->assertCount(0, $product->getProductTaxa());
        $this->assertCount(0, $product->getVariants()[0]->getVariantTaxa());
    }

    public function test_when_updating_taxa_all_existing_taxa_on_variants_remain_intact(): void
    {
        $product = $this->catalogContext->createProduct();
        $productId = $product->productId;
        $variantId = $product->getVariants()[0]->variantId;

        $this->catalogContext->createTaxonomy('taxonomy-aaa', TaxonomyType::variant_property->value);
        $taxon = $this->catalogContext->createTaxon();
        $taxon2 = $this->catalogContext->createTaxon('taxon-bbb');
        $this->catalogContext->linkProductToTaxon($productId->get(), $taxon->taxonId->get());
        $this->catalogContext->linkVariantToTaxon($productId->get(), $variantId->get(), $taxon->taxonId->get());

        $this->catalogContext->repos()->productRepository()->save($product);

        $this->assertCount(1, $product->getProductTaxa());
        $this->assertCount(1, $product->getVariants()[0]->getVariantTaxa());

        // Add a different taxon, existing variant taxa remain intact.
        $this->catalogContext->linkProductToTaxon($productId->get(), $taxon2->taxonId->get());

        $product = $this->catalogContext->repos()->productRepository()->find($product->productId);

        $this->assertCount(2, $product->getProductTaxa());
        $this->assertEquals('taxon-aaa', $product->getProductTaxa()[0]->taxonId->get());
        $this->assertEquals('taxon-bbb', $product->getProductTaxa()[1]->taxonId->get());

        // Existing variant taxa remain intact.
        $this->assertCount(1, $product->getVariants()[0]->getVariantTaxa());
        $this->assertEquals('taxon-aaa', $product->getVariants()[0]->getVariantTaxa()[0]->taxonId->get());
    }
}
