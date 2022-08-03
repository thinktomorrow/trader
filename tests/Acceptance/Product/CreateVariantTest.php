<?php
declare(strict_types=1);

namespace Tests\Acceptance\Product;

class CreateVariantTest extends ProductContext
{
    /** @test */
    public function it_can_create_a_variant()
    {
        $productId = $this->createAProduct('50', ['1','2'], 'sku', ['title' => ['nl' => 'foobar nl']]);
        $variantId = $this->createAVariant($productId->get(), "1234", "12", ['title' => ['nl' => 'foobar nl']], 'xxx-124');

        $product = $this->productRepository->find($productId);

        $variants = $product->getVariants();
        $this->assertCount(2, $variants);

        $this->assertEquals('50', $variants[0]->getMappedData()['unit_price']);
        $this->assertEquals('1234', $variants[1]->getMappedData()['unit_price']);
        $this->assertEquals(['nl' => 'foobar nl'], $variants[1]->getData('title'));

        $this->assertEquals(true, $variants[0]->getMappedData()['show_in_grid']);
        $this->assertEquals(false, $variants[1]->getMappedData()['show_in_grid']);
    }
}
