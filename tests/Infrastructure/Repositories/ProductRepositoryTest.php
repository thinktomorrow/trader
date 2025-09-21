<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Tests\Infrastructure\TestCase;
use Tests\Support\Catalog;
use Thinktomorrow\Trader\Domain\Model\Product\Exceptions\CouldNotFindProduct;
use Thinktomorrow\Trader\Domain\Model\Product\Product;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;

final class ProductRepositoryTest extends TestCase
{
    public function test_it_can_save_and_find_a_product()
    {
        foreach (Catalog::drivers() as $catalog) {

            $catalog->dontPersist();

            foreach ($this->products($catalog) as $product) {

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

            foreach ($this->products($catalog) as $product) {

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

    private function products(Catalog $catalog): array
    {
        // For product with taxon
        $catalog->createTaxonomy();
        $taxon = $catalog->createTaxon();

        return [
            $catalog->createProduct(),
            Product::create(ProductId::fromString('product-aaa')), // Without variant
            $catalog->addPersonalisationToProduct($catalog->createProduct(), $catalog->makePersonalisation()),
            $catalog->linkProductToTaxon($catalog->createProduct(), $taxon),
        ];
    }
}
