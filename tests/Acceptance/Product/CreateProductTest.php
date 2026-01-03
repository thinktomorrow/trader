<?php
declare(strict_types=1);

namespace Tests\Acceptance\Product;

use Thinktomorrow\Trader\Domain\Model\Product\Product;

class CreateProductTest extends ProductContext
{
    public function test_it_can_create_a_product()
    {
        $product = $this->catalogContext->createProduct('product-aaa', null);

        $this->assertInstanceOf(Product::class, $product);
    }

    public function test_it_can_create_a_variant()
    {
        $product = $this->catalogContext->createProduct('product-aaa', null);
        $this->catalogContext->createVariant($product->productId->get());

        $product = $this->catalogContext->catalogRepos()->productRepository()->find($product->productId);

        $variants = $product->getVariants();
        $this->assertCount(1, $variants);
        $this->assertEquals('100', $variants[0]->getMappedData()['unit_price']);
    }
}
