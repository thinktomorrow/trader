<?php

namespace Thinktomorrow\Trader\Tests;

use Money\Money;
use Thinktomorrow\Trader\Common\Domain\Price\Percentage;
use Thinktomorrow\Trader\Sales\Domain\AppliedSale;
use Thinktomorrow\Trader\Sales\Domain\SaleId;
use Thinktomorrow\Trader\Sales\Domain\Types\PercentageOffSale;
use Thinktomorrow\Trader\Tests\Stubs\PurchasableStub;

class SaleTest extends TestCase
{
    /** @test */
    public function saleId_is_a_valid_identifier()
    {
        $saleId = SaleId::fromInteger(2);

        $this->assertEquals(2, $saleId->get());
    }

    /** @test */
    public function purchasable_can_have_sale_price()
    {
        $salePrice = Money::EUR(99);
        $purchasable = new PurchasableStub(1, [], Money::EUR(100), Percentage::fromPercent(6), $salePrice);

        $this->assertSame($salePrice, $purchasable->salePrice());
        $this->assertEquals($salePrice->multiply(0.06), $purchasable->tax());
    }

    /** @test */
    public function sale_price_is_used_for_itemprice_in_cart()
    {
        $salePrice = Money::EUR(99);
        $purchasable = new PurchasableStub(1, [], Money::EUR(100), Percentage::fromPercent(6), $salePrice);

        $item = $this->getItem(null, null, $purchasable);

        $this->assertEquals($salePrice, $item->total());
    }

    /** @test */
    public function it_applies_sale()
    {
        $stub = $this->makeEligibleForSaleStub(100);
        $sale = $this->makePercentageOffSale(20);

        $sale->apply($stub);

        $this->assertCount(1, $stub->sales());
        $this->assertEquals(Money::EUR(100), $stub->price());
        $this->assertEquals(Money::EUR(20), $stub->saleTotal());
        $this->assertEquals(Money::EUR(80), $stub->salePrice());
    }

    /** @test */
    public function it_applies_multiple_sales()
    {
        $stub = $this->makeEligibleForSaleStub(100);
        $sale = $this->makePercentageOffSale(20);
        $sale2 = $this->makePercentageOffSale(20);

        $sale->apply($stub);
        $sale2->apply($stub);

        $this->assertCount(2, $stub->sales());
        $this->assertEquals(Money::EUR(100), $stub->price());
        $this->assertEquals(Money::EUR(40), $stub->saleTotal());
        $this->assertEquals(Money::EUR(60), $stub->salePrice());
    }

    /** @test */
    public function when_applied_an_applied_sale_is_kept_with_the_sale_amounts()
    {
        $stub = $this->makeEligibleForSaleStub(100);
        $sale = $this->makePercentageOffSale(20);

        $sale->apply($stub);

        $appliedSale = $stub->sales()[0];
        $this->assertInstanceOf(AppliedSale::class, $appliedSale);

        $this->assertEquals(Money::EUR(20), $appliedSale->saleAmount());
        $this->assertEquals(Percentage::fromPercent(20), $appliedSale->salePercentage());
        $this->assertEquals(PercentageOffSale::class, $appliedSale->saleType());
        $this->assertEquals(SaleId::fromString("1"), $appliedSale->saleId());
    }


    /** @test */
    public function when_applied_an_applied_sale_is_kept_with_custom_data()
    {
        $stub = $this->makeEligibleForSaleStub(100);
        $sale = $this->makePercentageOffSale(20, ['foo' => 'bar']);

        $sale->apply($stub);

        $appliedSale = $stub->sales()[0];
        $this->assertEquals(['foo' => 'bar'], $appliedSale->data());
        $this->assertEquals('bar', $appliedSale->data('foo'));
    }
}
