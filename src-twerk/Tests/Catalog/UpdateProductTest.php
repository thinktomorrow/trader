<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Tests\Catalog;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Event;
use Money\Money;
use Tests\TestCase;
use Thinktomorrow\Trader\Catalog\Products\Application\UpdateProduct;
use Thinktomorrow\Trader\Catalog\Products\Domain\Events\ProductUpdated;
use Thinktomorrow\Trader\Catalog\Products\Domain\ProductRepository;
use Thinktomorrow\Trader\Taxes\TaxRate;
use Thinktomorrow\Trader\Tests\Catalog\Options\OptionTestHelpers;

class UpdateProductTest extends TestCase
{
    use DatabaseMigrations;
    use OptionTestHelpers;

    /** @test */
    public function it_can_update_a_product()
    {
        $product = $this->productWithOption();

        app()->make(UpdateProduct::class)->handle(
            $product->getId(),
            false,
            Money::EUR(250),
            Money::EUR(300),
            TaxRate::fromInteger(21),
            [],
            ['custom' => 'custom-value']
        );

        $product = app(ProductRepository::class)->findById($product->getId());

        $this->assertEquals(Money::EUR(250), $product->getTotal());
        $this->assertEquals(Money::EUR(300), $product->getUnitPrice());
        $this->assertEquals(Money::EUR(50), $product->getDiscountTotal());
        $this->assertEquals(TaxRate::fromInteger(21), $product->getTaxRate());
        $this->assertEquals(Money::EUR((int) round((250 - (250 / 1.21)))), $product->getTaxTotal());
        $this->assertEquals('blue', $product->getOptions()[0]->getValue('nl'));

        // Data attributes
        $this->assertEquals('custom-value', $product->getData()['custom']);
        $this->assertEquals(new Collection(), $product->getData()['images']);
        $this->assertEquals(false, $product->getData()['prices_include_tax']);
    }

    /** @test */
    public function it_emits_a_product_updated_event()
    {
        Event::fake();

        $product = $this->createProduct();

        app()->make(UpdateProduct::class)->handle(
            $product->getId(),
            false,
            Money::EUR(250),
            Money::EUR(300),
            TaxRate::fromInteger(21),
            [],
            ['custom' => 'custom-value']
        );

        Event::assertDispatched(ProductUpdated::class);
    }

    /** @test */
    public function it_wont_update_data_if_not_passed()
    {
        $product = $this->createProduct([
            'data' => ['custom' => 'custom-value', 'sku' => 'foobar'],
        ]);

        app()->make(UpdateProduct::class)->handle(
            $product->getId(),
            false,
            Money::EUR(250),
            Money::EUR(300),
            TaxRate::fromInteger(21)
        );

        $product = app(ProductRepository::class)->findById($product->getId());

        $this->assertEquals('custom-value', $product->getData()['custom']);
        $this->assertEquals('foobar', $product->getData()['sku']);
    }
}
