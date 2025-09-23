<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Domain\Model\Product\Exceptions\CouldNotFindProduct;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Testing\Support\Catalog;

final class ProductRepositoryTest extends TestCase
{
    public function test_it_can_save_and_find_a_product()
    {
        foreach (Catalog::drivers() as $catalog) {

            $catalog->dontPersist();

            foreach ($catalog->products() as $product) {

                $catalog->repos->productRepository()->save($product);
                $product->releaseEvents();

                $this->assertEquals($product, $catalog->repos->productRepository()->find($product->productId));
            }
        }
    }

    public function test_it_can_delete_a_product()
    {
        $productsNotFound = 0;

        foreach (Catalog::drivers() as $catalog) {

            $catalog->dontPersist();

            foreach ($catalog->products() as $product) {

                $catalog->repos->productRepository()->save($product);
                $product->releaseEvents();

                $catalog->repos->productRepository()->delete($product->productId);

                try {
                    $catalog->repos->productRepository()->find($product->productId);
                } catch (CouldNotFindProduct $e) {
                    $productsNotFound++;
                }
            }
        }

        $this->assertEquals(count(Catalog::drivers()) * 4, $productsNotFound); // 4 products per catalog driver
    }

    public function test_it_can_generate_a_next_reference()
    {
        foreach (Catalog::drivers() as $catalog) {
            $this->assertInstanceOf(ProductId::class, $catalog->repos->productRepository()->nextReference());
        }
    }
}
