<?php

namespace Thinktomorrow\Trader\Tests\Unit;

use Thinktomorrow\Trader\Catalog\Products\Ports\Persistence\InMemoryProductRepository;
use Thinktomorrow\Trader\Catalog\Products\Product;

class ProductRepositoryTest extends UnitTestCase
{
    protected function makeProduct()
    {
        return new Product(3);
    }

    /** @test */
    public function it_can_find_an_product()
    {
        $product = $this->makeProduct();
        $repo = new InMemoryProductRepository();

        $repo->add($product);

        $this->assertEquals($product, $repo->find(3));
    }

    /** @test */
    public function it_throws_exception_if_product_does_not_exist()
    {
        $this->setExpectedException(\RuntimeException::class);

        $repo = new InMemoryProductRepository();
        $repo->find(9);
    }
}
