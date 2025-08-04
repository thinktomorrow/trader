<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Domain\Model\Product\Exceptions\CouldNotFindProduct;
use Thinktomorrow\Trader\Domain\Model\Product\Product;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlProductRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlVariantRepository;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;

final class ProductRepositoryTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('products')]
    public function test_it_can_save_and_find_a_product(Product $product)
    {
        foreach ($this->repositories() as $repository) {
            $repository->save($product);
            $product->releaseEvents();
            dd($product);

            $this->assertEquals($product, $repository->find($product->productId));
        }
    }

    #[DataProvider('products')]
    public function test_it_can_delete_a_product(Product $product)
    {
        $productsNotFound = 0;

        foreach ($this->repositories() as $repository) {
            $repository->save($product);
            $repository->delete($product->productId);

            try {
                $repository->find($product->productId);
            } catch (CouldNotFindProduct $e) {
                $productsNotFound++;
            }
        }

        $this->assertEquals(count(iterator_to_array($this->repositories())), $productsNotFound);
    }

    public function test_it_can_generate_a_next_reference()
    {
        foreach ($this->repositories() as $repository) {
            $this->assertInstanceOf(ProductId::class, $repository->nextReference());
        }
    }

    private static function repositories(): \Generator
    {
//        yield new InMemoryProductRepository();
        yield new MysqlProductRepository(new MysqlVariantRepository(new TestContainer()));
    }

    public static function products(): \Generator
    {
//        yield [static::createProduct()];
//        yield [static::createProductWithPersonalisations()];
        yield [static::createProductWithVariant()];
    }
}
