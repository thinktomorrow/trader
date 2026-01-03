<?php
declare(strict_types=1);

namespace Tests\Acceptance\Product;

use Tests\Acceptance\TestCase;

abstract class ProductContext extends TestCase
{
//    protected EventDispatcherSpy $eventDispatcher;
//    protected TaxonomyApplication $taxonomyApplication;
//    protected TaxonApplication $taxonApplication;
//    protected InMemoryTaxonRepository $taxonRepository;
//    protected InMemoryTaxonomyRepository $taxonomyRepository;
//    protected ProductApplication $productApplication;
//    protected InMemoryProductRepository $productRepository;
//    protected InMemoryVariantRepository $variantRepository;
//    protected VariantLinksComposer $productOptionsComposer;
//    protected InMemoryProductDetailRepository $productDetailRepository;
//    protected MissingVariants $missingOptionCombinations;

    protected function setUp(): void
    {
        parent::setUp();

//        $this->taxonomyApplication = new TaxonomyApplication(
//            new TestTraderConfig(),
//            $this->eventDispatcher = new EventDispatcherSpy(),
//            $this->taxonomyRepository = new InMemoryTaxonomyRepository(),
//        );
//
//        $this->taxonApplication = new TaxonApplication(
//            new TestTraderConfig(),
//            $this->eventDispatcher,
//            $this->taxonRepository = new InMemoryTaxonRepository(),
//        );
//
//        $this->catalogContext->catalogApps()->productApplication() = new ProductApplication(
//            new TestTraderConfig(),
//            $this->eventDispatcher,
//            $this->catalogContext->catalogRepos()->productRepository() = new InMemoryProductRepository(),
//            $this->variantRepository = new InMemoryVariantRepository(),
//            new InMemoryTaxonTreeRepository(new TestContainer(), new TestTraderConfig()),
//            new InMemoryTaxonomyRepository(),
//        );
//
//        (new TestContainer())->add(TaxonNode::class, DefaultTaxonNode::class);
//        (new TestContainer())->add(VariantLink::class, DefaultVariantLink::class);
//
//        $this->productDetailRepository = new InMemoryProductDetailRepository();
//
//        $this->productOptionsComposer = new VariantLinksComposer(
//            $this->catalogContext->catalogRepos()->productRepository(),
//            new TestContainer(),
//        );
//
//
//        $this->missingOptionCombinations = new MissingVariants(
//            $this->taxonomyRepository,
//            $this->taxonRepository,
//        );
    }

    public function tearDown(): void
    {
        parent::tearDown();

//        $this->catalogContext->catalogRepos()->productRepository()->clear();
//        $this->variantRepository->clear();
    }

//    protected function createAProduct(string $unitPrice, array $taxonIds, string $sku = 'sku', array $data = [], array $variantData = []): ProductId
//    {
//        $productId = $this->catalogContext->catalogApps()->productApplication()->createProduct(new CreateProduct(
//            $taxonIds,
//            $unitPrice,
//            '12',
//            $sku,
//            $data,
//            $variantData
//        ));
//
//        Assert::assertNotNull($product = $this->catalogContext->catalogRepos()->productRepository()->find($productId));
//
//        $this->assertEquals([
//            new ProductCreated($productId),
//            new ProductTaxaUpdated($productId),
//            new ProductDataUpdated($productId),
//            new VariantCreated($productId, $product->getVariants()[0]->variantId),
//            new ProductTaxaUpdated($productId), // because of the InMemoryRepo implementation.
//        ], $this->eventDispatcher->releaseDispatchedEvents());
//
//        return $productId;
//    }
//
//    protected function createAVariant(string $productId, string $unitPrice, string $taxRate, array $data = [], string $variantId = 'xxx-123', string $sku = 'sku'): VariantId
//    {
//        InMemoryVariantRepository::setNextReference($variantId);
//
//        $variantId = $this->catalogContext->catalogApps()->productApplication()->createVariant(new CreateVariant(
//            $productId,
//            $unitPrice,
//            $taxRate,
//            $sku,
//            $data
//        ));
//
//        $this->assertEquals([
//            new VariantCreated(ProductId::fromString($productId), $variantId),
//            new ProductTaxaUpdated(ProductId::fromString($productId)), // because of the InMemoryRepo implementation.
//        ], $this->eventDispatcher->releaseDispatchedEvents());
//
//        return $variantId;
//    }
}
