<?php
declare(strict_types=1);

namespace Tests\Acceptance\Product;

use Money\Money;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Application\Common\DataRenderer;
use Thinktomorrow\Trader\Application\Product\CreateProduct;
use Thinktomorrow\Trader\Infrastructure\Test\TestTraderConfig;
use Thinktomorrow\Trader\Infrastructure\Test\EventDispatcherSpy;
use Thinktomorrow\Trader\Application\Product\ProductApplication;
use Thinktomorrow\Trader\Domain\Model\Product\Event\ProductCreated;
use Thinktomorrow\Trader\Domain\Model\Product\Event\VariantCreated;
use Thinktomorrow\Trader\Domain\Model\Product\Event\ProductTaxaUpdated;
use Thinktomorrow\Trader\Domain\Model\Product\Event\ProductDataUpdated;
use Thinktomorrow\Trader\Application\Product\ProductOptions\ProductOptionsComposer;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryProductRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryVariantRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryProductDetailRepository;

abstract class ProductContext extends TestCase
{
    protected ProductApplication $productApplication;
    protected InMemoryProductRepository $productRepository;
    protected InMemoryVariantRepository $variantRepository;
    protected EventDispatcherSpy $eventDispatcher;
    protected ProductOptionsComposer $productOptionsComposer;

    protected function setUp(): void
    {
        DataRenderer::setResolver(function(array $data, string $key, string $language = null, string $default = null)
        {
            if(!isset($data[$key])) {
                return $default;
            }

            if(!$language) {
                $language = 'nl';
            }

            if(isset($data[$key][$language])) {
                return $data[$key][$language];
            }

            return $data[$key];
        });

        $this->productApplication = new ProductApplication(
            new TestTraderConfig(),
            $this->eventDispatcher = new EventDispatcherSpy(),
            $this->productRepository = new InMemoryProductRepository(),
            $this->variantRepository = new InMemoryVariantRepository($this->productRepository),
        );

        $this->productOptionsComposer = new ProductOptionsComposer(
            new InMemoryProductDetailRepository(),
            new InMemoryVariantRepository($this->productRepository),
        );
    }

    public function tearDown(): void
    {
        $this->productRepository->clear();
    }

    protected function createAProduct(string $unitPrice, array $taxonIds, array $data = []): ProductId
    {
        $productId = $this->productApplication->createProduct(new CreateProduct(
            $taxonIds, $unitPrice, $data
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

    protected function editProductOptions(string $unitPrice, array $taxonIds, array $data = []): ProductId
    {
        $productId = $this->productApplication->createProduct(new CreateProduct(
            $taxonIds, $unitPrice, $data
        ));

        Assert::assertNotNull($this->productRepository->find($productId));

        return $productId;
    }
}