<?php
declare(strict_types=1);

namespace Tests\Acceptance\Product;

use Thinktomorrow\Trader\Domain\Model\Product\ProductTaxa\ProductTaxon;

class CreateProductTest extends ProductContext
{
    public function test_it_can_create_a_product()
    {
        $productId = $this->createAProduct('50', ['1', '2'], 'sku', ['title' => ['nl' => 'foobar nl']]);

        $product = $this->catalogContext->catalogRepos()->productRepository()->find($productId);

        // Title
        $this->assertEquals(json_encode(['title' => ['nl' => 'foobar nl']]), $product->getMappedData()['data']);

        // Taxa
        $this->assertCount(2, $product->getProductTaxa());
        $this->assertContainsOnlyInstancesOf(ProductTaxon::class, $product->getProductTaxa());

        // Auto created variant
        $variants = $product->getVariants();

        $this->assertCount(1, $variants);
        $this->assertEquals('50', $variants[0]->getMappedData()['unit_price']);
        $this->assertEquals('50', $variants[0]->getMappedData()['sale_price']);
        $this->assertEquals(true, $variants[0]->getMappedData()['show_in_grid']);
    }
}
