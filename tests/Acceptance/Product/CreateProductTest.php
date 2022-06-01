<?php
declare(strict_types=1);

namespace Tests\Acceptance\Product;

class CreateProductTest extends ProductContext
{
    /** @test */
    public function it_can_create_a_product()
    {
        $productId = $this->createAProduct('50', ['1','2'], ['title' => ['nl' => 'foobar nl']]);

        $product = $this->productRepository->find($productId);

        // Title
        $this->assertEquals(json_encode(['title' => ['nl' => 'foobar nl']]), $product->getMappedData()['data']);

        // Taxon ids
        $this->assertEquals(['1','2'], $product->getMappedData()['taxon_ids']);

        // Auto created variant
        $variants = $product->getVariants();

        $this->assertCount(1, $variants);
        $this->assertEquals('50', $variants[0]->getMappedData()['unit_price']);
        $this->assertEquals('50', $variants[0]->getMappedData()['sale_price']);
        $this->assertEquals(true, $variants[0]->getMappedData()['show_in_grid']);
    }
}
