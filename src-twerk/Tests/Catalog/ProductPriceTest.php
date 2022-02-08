<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Tests\Catalog;

use App\Shop\TraderConfig;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Money\Money;
use Tests\TestCase;
use Thinktomorrow\Trader\Catalog\Products\Domain\ProductRepository;
use Thinktomorrow\Trader\Catalog\Products\Ports\ProductPrice;

final class ProductPriceTest extends TestCase
{
    use DatabaseMigrations;

    /** @test */
    public function it_can_enter_unit_price_inclusive_vat()
    {
        $this->app->bind('trader_config', function () {
            return new class() extends TraderConfig {
                public function doPricesIncludeTax(): bool
                {
                    return true;
                }
            };
        });

        $product = $this->createProduct();
        $product = app(ProductRepository::class)->findById($product->getId());

        $this->assertTrue($product->doPricesIncludeTax());

        $productPrice = new ProductPrice();

        $this->assertEquals(Money::EUR(80), $product->getTotal());
        $this->assertEquals(Money::EUR(80), $productPrice->getTotalInclusiveTax($product));
        $this->assertEquals(Money::EUR(75), $productPrice->getTotalExclusiveTax($product));

        $this->assertEquals(Money::EUR(100), $product->getUnitPrice());
        $this->assertEquals(Money::EUR(100), $productPrice->getUnitPriceInclusiveTax($product));
        $this->assertEquals(Money::EUR(94), $productPrice->getUnitPriceExclusiveTax($product));
    }

    /** @test */
    public function it_can_enter_unit_price_exclusive_vat()
    {
        $this->app->bind('trader_config', function () {
            return new class() extends TraderConfig {
                public function doPricesIncludeTax(): bool
                {
                    return false;
                }
            };
        });

        $product = $this->createProduct();
        $product = app(ProductRepository::class)->findById($product->getId());

        $this->assertFalse($product->doPricesIncludeTax());

        $productPrice = new ProductPrice();

        $this->assertEquals(Money::EUR(80), $product->getTotal());
        $this->assertEquals(Money::EUR(85), $productPrice->getTotalInclusiveTax($product));
        $this->assertEquals(Money::EUR(80), $productPrice->getTotalExclusiveTax($product));

        $this->assertEquals(Money::EUR(100), $product->getUnitPrice());
        $this->assertEquals(Money::EUR(106), $productPrice->getUnitPriceInclusiveTax($product));
        $this->assertEquals(Money::EUR(100), $productPrice->getUnitPriceExclusiveTax($product));
    }
}
