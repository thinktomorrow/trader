<?php

namespace Thinktomorrow\Trader\Tests\Unit;

use Money\Money;
use Thinktomorrow\Trader\Common\Domain\Price\Percentage;
use Thinktomorrow\Trader\Sales\Domain\Exceptions\CannotApplySale;
use Thinktomorrow\Trader\Sales\Domain\SaleId;
use Thinktomorrow\Trader\Sales\Domain\Types\PercentageOffSale;
use Thinktomorrow\Trader\Tests\Unit\Stubs\EligibleForSaleStub;

class SaleTest extends UnitTestCase
{
    /** @test */
    function saleId_is_a_valid_identifier()
    {
        $saleId = SaleId::fromInteger(2);

        $this->assertEquals(2,$saleId->get());
    }

    /** @test */
    function it_applies_sale()
    {
        $stub = $this->makeStub(100);
        $sale = $this->makePercentageOffSale(20);

        $sale->apply($stub);

        $this->assertCount(1,$stub->sales());
        $this->assertEquals(Money::EUR(100),$stub->price());
        $this->assertEquals(Money::EUR(20),$stub->saleTotal());
        $this->assertEquals(Money::EUR(80),$stub->salePrice());
    }

    /** @test */
    function it_applies_multiple_sales()
    {
        $stub = $this->makeStub(100);
        $sale = $this->makePercentageOffSale(20);
        $sale2 = $this->makePercentageOffSale(20);

        $sale->apply($stub);
        $sale2->apply($stub);

        $this->assertCount(2,$stub->sales());
        $this->assertEquals(Money::EUR(100),$stub->price());
        $this->assertEquals(Money::EUR(40),$stub->saleTotal());
        $this->assertEquals(Money::EUR(60),$stub->salePrice());
    }

    /** @test */
    function percentage_sale_cannot_be_higher_than_100()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->makePercentageOffSale(120);
    }

    /** @test */
    function percentage_sale_cannot_be_lower_than_0()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->makePercentageOffSale(-10);
    }

    /** @test */
    function sale_cannot_go_below_purchasable_original_price()
    {
        $this->expectException(CannotApplySale::class);

        $stub = $this->makeStub(100);
        $sale = $this->makePercentageOffSale(80);
        $sale2 = $this->makePercentageOffSale(80);

        $sale->apply($stub);
        $sale2->apply($stub);

        $this->assertEquals(Money::EUR(100),$stub->price());
        $this->assertEquals(Money::EUR(100),$stub->saleTotal());
        $this->assertEquals(Money::EUR(0),$stub->salePrice());
    }

    /** @test */
    function sale_cannot_go_exactly_to_purchasable_original_price()
    {
        $stub = $this->makeStub(100);
        $sale = $this->makePercentageOffSale(50);
        $sale2 = $this->makePercentageOffSale(50);

        $sale->apply($stub);
        $sale2->apply($stub);

        $this->assertEquals(Money::EUR(100),$stub->price());
        $this->assertEquals(Money::EUR(100),$stub->saleTotal());
        $this->assertEquals(Money::EUR(0),$stub->salePrice());
    }

    private function makeStub($amount)
    {
        $price = Money::EUR($amount);

        return new EligibleForSaleStub(1,[],$price);
    }

    private function makePercentageOffSale($percent)
    {
        return new PercentageOffSale(SaleId::fromInteger(1),[],['percentage' => Percentage::fromPercent($percent)]);
    }
}