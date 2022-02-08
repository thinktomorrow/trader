<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Tests\Catalog;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Event;
use Money\Money;
use Tests\TestCase;
use Thinktomorrow\Trader\Catalog\Products\Domain\Events\ProductCreated;
use Thinktomorrow\Trader\Catalog\Products\Domain\ProductRepository;
use Thinktomorrow\Trader\Taxes\TaxRate;

class CreateProductTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function it_can_store_a_product()
    {
        $product = $this->createProduct();

        $this->assertNotNull($product);

        $product = app(ProductRepository::class)->findById($product->getId());
        $this->assertEquals(Money::EUR(80), $product->getTotal());
        $this->assertEquals(Money::EUR(100), $product->getUnitPrice());
        $this->assertEquals(Money::EUR(20), $product->getDiscountTotal());
        $this->assertEquals(TaxRate::fromInteger(6), $product->getTaxRate());
        $this->assertEquals(Money::EUR((int) round((80 - (80 / 1.06)))), $product->getTaxTotal());
    }

    /** @test */
    public function it_emits_a_product_created_event()
    {
        Event::fake();

        $this->createProduct();

        Event::assertDispatched(ProductCreated::class);
    }
}
