<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Tests\Catalog;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Money\Money;
use Tests\TestCase;
use Thinktomorrow\Trader\Catalog\Products\Application\CreateProduct;
use Thinktomorrow\Trader\Catalog\Products\Application\CreateProductGroup;
use Thinktomorrow\Trader\Catalog\Products\Domain\Product;
use Thinktomorrow\Trader\Catalog\Products\Domain\ProductGroup;
use Thinktomorrow\Trader\Catalog\Products\Domain\ProductGroupRepository;
use Thinktomorrow\Trader\Taxes\TaxRate;

class CreateProductGroupTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function it_can_store_a_productgroup()
    {
        /** @var ProductGroup $productGroup */
        $productGroup = app(CreateProductGroup::class)->handle(
            [],
            []
        );

        $this->assertNotNull($productGroup);
        $productGroup = app(ProductGroupRepository::class)->findById($productGroup->getId());
        $this->assertEquals(new Collection(), $productGroup->getTaxonomy());
        $this->assertEquals(new Collection(), $productGroup->getGridProducts());
        $this->assertEquals(new Collection(), $productGroup->getProducts());
    }

    /** @test */
    public function it_can_get_all_products()
    {
        /** @var ProductGroup $productGroup */
        $productGroup = app(CreateProductGroup::class)->handle(
            [],
            []
        );

        /** @var Product $product */
        $product = app(CreateProduct::class)->handle(
            $productGroup->getId(),
            true,
            Money::EUR(80),
            Money::EUR(100),
            TaxRate::fromInteger(6),
            [],
            [
                'sku' => '123',
            ]
        );

        $productGroup = app(ProductGroupRepository::class)->findById($productGroup->getId());
        $this->assertCount(1, $productGroup->getProducts());
        $this->assertEquals($product, $productGroup->getProducts()->first());
    }

    /** @test */
    public function it_can_add_custom_data()
    {
        // Image we need a custom "vendor" field
        $this->markTestIncomplete();
    }
}
