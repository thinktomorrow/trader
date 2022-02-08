<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Tests\Catalog;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use Thinktomorrow\Trader\Catalog\Products\Domain\ProductRepository;

class DeleteProductTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function it_can_delete_a_product()
    {
        $product = $this->createProduct();

        app(ProductRepository::class)->delete($product->getId());

        $this->assertCount(0, app(ProductRepository::class)->getByProductGroup($product->getProductGroupId()));
    }
}
