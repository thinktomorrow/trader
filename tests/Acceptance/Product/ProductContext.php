<?php
declare(strict_types=1);

namespace Tests\Acceptance\Product;

use PHPUnit\Framework\Assert;
use Tests\Acceptance\TestCase;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Application\Product\CreateProduct;
use Thinktomorrow\Trader\Application\Product\CreateVariant;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;
use Thinktomorrow\Trader\Infrastructure\Test\TestTraderConfig;
use Thinktomorrow\Trader\Infrastructure\Test\EventDispatcherSpy;
use Thinktomorrow\Trader\Application\Product\ProductApplication;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\Product\Event\ProductCreated;
use Thinktomorrow\Trader\Domain\Model\Product\Event\VariantCreated;
use Thinktomorrow\Trader\Application\Product\OptionLinks\OptionLink;
use Thinktomorrow\Trader\Domain\Model\Product\Event\ProductTaxaUpdated;
use Thinktomorrow\Trader\Domain\Model\Product\Event\ProductDataUpdated;
use Thinktomorrow\Trader\Infrastructure\Laravel\Models\DefaultOptionLink;
use Thinktomorrow\Trader\Application\Product\OptionLinks\ProductOptionValues;
use Thinktomorrow\Trader\Application\Product\OptionLinks\OptionLinksComposer;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryProductRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryVariantRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryProductDetailRepository;
use Thinktomorrow\Trader\Application\Product\CheckProductOptions\MissingOptionCombinations;

abstract class ProductContext extends TestCase
{
    protected ProductApplication $productApplication;
    protected InMemoryProductRepository $productRepository;
    protected InMemoryVariantRepository $variantRepository;
    protected EventDispatcherSpy $eventDispatcher;
    protected OptionLinksComposer $productOptionsComposer;
    protected InMemoryProductDetailRepository $productDetailRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->productApplication = new ProductApplication(
            new TestTraderConfig(),
            $this->eventDispatcher = new EventDispatcherSpy(),
            $this->productRepository = new InMemoryProductRepository(),
            $this->variantRepository = new InMemoryVariantRepository($this->productRepository),
        );

        (new TestContainer())->add(OptionLink::class, DefaultOptionLink::class);

        $this->productOptionsComposer = new OptionLinksComposer(
            $this->productRepository,
            new TestContainer(),
        );

        $this->productDetailRepository = new InMemoryProductDetailRepository();

        $this->missingOptionCombinations = new MissingOptionCombinations(
            new ProductOptionValues(new InMemoryProductRepository())
        );
    }

    public function tearDown(): void
    {
        $this->productRepository->clear();
        $this->variantRepository->clear();
    }

    protected function createAProduct(string $unitPrice, array $taxonIds, array $data = []): ProductId
    {
        $productId = $this->productApplication->createProduct(new CreateProduct(
            $taxonIds, $unitPrice, '12', $data
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

    protected function createAVariant(string $productId, string $unitPrice, string $taxRate, array $data = [], string $variantId = 'xxx-123'): VariantId
    {
        InMemoryVariantRepository::setNextReference($variantId);

        $variantId = $this->productApplication->createVariant(new CreateVariant(
            $productId, $unitPrice, $taxRate, $data
        ));

        $this->assertEquals([
            new VariantCreated(ProductId::fromString($productId), $variantId),
        ], $this->eventDispatcher->releaseDispatchedEvents());

        return $variantId;
    }
}
