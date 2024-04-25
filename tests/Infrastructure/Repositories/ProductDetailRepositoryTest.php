<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Application\Product\ProductDetail\ProductDetail;
use Thinktomorrow\Trader\Domain\Model\Product\Exceptions\CouldNotFindVariant;
use Thinktomorrow\Trader\Domain\Model\Product\Product;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlProductDetailRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlProductRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlVariantRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryProductDetailRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryProductRepository;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;

final class ProductDetailRepositoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider products
     */
    public function it_can_find_a_product(Product $product)
    {
        foreach ($this->repositories() as $i => $repository) {
            $productRepository = iterator_to_array($this->productRepositories())[$i];
            $productRepository->save($product);
            $product->releaseEvents();

            $this->assertInstanceOf(ProductDetail::class, $repository->findProductDetail($product->getVariants()[0]->variantId));
        }
    }

    /**
     * @test
     * @dataProvider offlineProducts
     */
    public function it_cannot_find_an_offline_productdetail(Product $product)
    {
        $productsNotFound = 0;

        foreach ($this->repositories() as $i => $repository) {
            $productRepository = iterator_to_array($this->productRepositories())[$i];
            $productRepository->save($product);
            $product->releaseEvents();

            try {
                $repository->findProductDetail($product->getVariants()[0]->variantId);
            } catch (CouldNotFindVariant $e) {
                $productsNotFound++;
            }
        }

        $this->assertEquals(count(iterator_to_array($this->repositories())), $productsNotFound);
    }

    /**
     * @test
     * @dataProvider offlineProducts
     */
    public function it_can_find_an_offline_product_if_offline_is_allowed(Product $product)
    {
        $productsNotFound = 0;

        foreach ($this->repositories() as $i => $repository) {
            $productRepository = iterator_to_array($this->productRepositories())[$i];
            $productRepository->save($product);
            $product->releaseEvents();

            try {
                $repository->findProductDetail($product->getVariants()[0]->variantId, true);
            } catch (CouldNotFindVariant $e) {
                $productsNotFound++;
            }
        }

        $this->assertEquals(0, $productsNotFound);
    }

    private function productRepositories(): \Generator
    {
        yield new InMemoryProductRepository();
        yield new MysqlProductRepository(new MysqlVariantRepository(new TestContainer()));
    }

    private function repositories(): \Generator
    {
        yield new InMemoryProductDetailRepository();
        yield new MysqlProductDetailRepository(new TestContainer());
    }

    public function products(): \Generator
    {
        yield [$this->createProductWithVariant()];
    }

    public function offlineProducts(): \Generator
    {
        yield [$this->createOfflineProductWithVariant()];
    }
}
