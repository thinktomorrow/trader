<?php
declare(strict_types=1);

namespace Tests\Acceptance\Product;

use PHPUnit\Framework\Assert;
use Tests\Acceptance\TestCase;
use Thinktomorrow\Trader\Application\Product\CheckProductVariantProperties\MissingVariantPropertyCombinations;
use Thinktomorrow\Trader\Application\Product\CreateProduct;
use Thinktomorrow\Trader\Application\Product\CreateVariant;
use Thinktomorrow\Trader\Application\Product\ProductApplication;
use Thinktomorrow\Trader\Application\Product\VariantLinks\ProductOptionsAndValues;
use Thinktomorrow\Trader\Application\Product\VariantLinks\VariantLink;
use Thinktomorrow\Trader\Application\Product\VariantLinks\VariantLinksComposer;
use Thinktomorrow\Trader\Application\Taxon\TaxonApplication;
use Thinktomorrow\Trader\Application\Taxonomy\TaxonomyApplication;
use Thinktomorrow\Trader\Domain\Model\Product\Events\ProductCreated;
use Thinktomorrow\Trader\Domain\Model\Product\Events\ProductDataUpdated;
use Thinktomorrow\Trader\Domain\Model\Product\Events\ProductTaxaUpdated;
use Thinktomorrow\Trader\Domain\Model\Product\Events\VariantCreated;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\DefaultVariantLink;
use Thinktomorrow\Trader\Infrastructure\Test\EventDispatcherSpy;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryProductDetailRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryProductRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryTaxonomyRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryTaxonRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryVariantRepository;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;
use Thinktomorrow\Trader\Infrastructure\Test\TestTraderConfig;

abstract class ProductContext extends TestCase
{
    protected EventDispatcherSpy $eventDispatcher;
    protected TaxonomyApplication $taxonomyApplication;
    protected TaxonApplication $taxonApplication;
    protected InMemoryTaxonRepository $taxonRepository;
    protected InMemoryTaxonomyRepository $taxonomyRepository;
    protected ProductApplication $productApplication;
    protected InMemoryProductRepository $productRepository;
    protected InMemoryVariantRepository $variantRepository;
    protected VariantLinksComposer $productOptionsComposer;
    protected InMemoryProductDetailRepository $productDetailRepository;
    protected MissingVariantPropertyCombinations $missingOptionCombinations;

    protected function setUp(): void
    {
        parent::setUp();

        $this->taxonomyApplication = new TaxonomyApplication(
            new TestTraderConfig(),
            $this->eventDispatcher = new EventDispatcherSpy(),
            $this->taxonomyRepository = new InMemoryTaxonomyRepository(),
        );

        $this->taxonApplication = new TaxonApplication(
            new TestTraderConfig(),
            $this->eventDispatcher,
            $this->taxonRepository = new InMemoryTaxonRepository(),
        );

        $this->productApplication = new ProductApplication(
            new TestTraderConfig(),
            $this->eventDispatcher,
            $this->productRepository = new InMemoryProductRepository(),
            $this->variantRepository = new InMemoryVariantRepository(),
        );

        (new TestContainer())->add(VariantLink::class, DefaultVariantLink::class);

        $this->productOptionsComposer = new VariantLinksComposer(
            $this->productRepository,
            new TestContainer(),
        );

        $this->productDetailRepository = new InMemoryProductDetailRepository();

        $this->missingOptionCombinations = new MissingVariantPropertyCombinations(
            new ProductOptionsAndValues(new InMemoryProductRepository())
        );
    }

    public function tearDown(): void
    {
        $this->productRepository->clear();
        $this->variantRepository->clear();
    }

    protected function createAProduct(string $unitPrice, array $taxonIds, string $sku = 'sku', array $data = [], array $variantData = []): ProductId
    {
        $productId = $this->productApplication->createProduct(new CreateProduct(
            $taxonIds,
            $unitPrice,
            '12',
            $sku,
            $data,
            $variantData
        ));

        Assert::assertNotNull($product = $this->productRepository->find($productId));

        $this->assertEquals([
            new ProductCreated($productId),
            new ProductTaxaUpdated($productId),
            new ProductDataUpdated($productId),
            new VariantCreated($productId, $product->getVariants()[0]->variantId),
        ], $this->eventDispatcher->releaseDispatchedEvents());

        return $productId;
    }

    protected function createAVariant(string $productId, string $unitPrice, string $taxRate, array $data = [], string $variantId = 'xxx-123', string $sku = 'sku'): VariantId
    {
        InMemoryVariantRepository::setNextReference($variantId);

        $variantId = $this->productApplication->createVariant(new CreateVariant(
            $productId,
            $unitPrice,
            $taxRate,
            $sku,
            $data
        ));

        $this->assertEquals([
            new VariantCreated(ProductId::fromString($productId), $variantId),
        ], $this->eventDispatcher->releaseDispatchedEvents());

        return $variantId;
    }
}
