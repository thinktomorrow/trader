<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Application\Product\ProductDetail\ProductDetail;
use Thinktomorrow\Trader\Domain\Model\Product\Exceptions\CouldNotFindVariant;
use Thinktomorrow\Trader\Domain\Model\Product\ProductState;
use Thinktomorrow\Trader\Testing\Catalog\CatalogContext;

final class ProductDetailRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_find_a_product()
    {
        foreach (CatalogContext::drivers() as $catalog) {

            $catalog->dontPersist();

            foreach ($catalog->products() as $product) {

                if (!$product->hasVariants()) {
                    continue;
                }

                $catalog->repos()->productRepository()->save($product);
                $product->releaseEvents();

                $this->assertInstanceOf(ProductDetail::class, $catalog->findProductDetail($product->getVariants()[0]->variantId));
            }
        }
    }

    public function test_it_cannot_find_an_offline_product()
    {
        $expectedCount = 0;
        $productsNotFound = 0;

        foreach (CatalogContext::drivers() as $catalog) {

            $catalog->dontPersist();

            foreach ($catalog->products() as $product) {

                if (!$product->hasVariants()) {
                    continue;
                }

                $expectedCount++;

                $product->updateState(ProductState::offline);

                $catalog->repos()->productRepository()->save($product);
                $product->releaseEvents();

                try {
                    $catalog->findProductDetail($product->getVariants()[0]->variantId);
                } catch (CouldNotFindVariant $e) {
                    $productsNotFound++;
                }
            }
        }

        $this->assertEquals($expectedCount, $productsNotFound);
    }

    public function test_it_can_find_an_offline_product_if_offline_is_allowed()
    {
        $productsNotFound = 0;

        foreach (CatalogContext::drivers() as $catalog) {

            $catalog->dontPersist();

            foreach ($catalog->products() as $product) {

                if (!$product->hasVariants()) {
                    continue;
                }

                $product->updateState(ProductState::offline);

                $catalog->repos()->productRepository()->save($product);
                $product->releaseEvents();

                try {
                    $catalog->repos()->productDetailRepository()->findProductDetail($product->getVariants()[0]->variantId, true);
                } catch (CouldNotFindVariant $e) {
                    $productsNotFound++;
                }
            }
        }

        $this->assertEquals(0, $productsNotFound);
    }
}
