<?php

namespace Thinktomorrow\Trader\Tests;

use Money\Money;
use Thinktomorrow\Trader\Sales\Domain\Exceptions\CannotApplySale;

class PercentageOffSaleTest extends TestCase
{
    /** @test */
    public function percentage_sale_cannot_be_higher_than_100()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->makePercentageOffSale(120);
    }

    /** @test */
    public function percentage_sale_cannot_be_lower_than_0()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->makePercentageOffSale(-10);
    }

    /** @test */
    public function sale_cannot_go_below_purchasable_original_price()
    {
        $this->expectException(CannotApplySale::class);

        $stub = $this->makeEligibleForSaleStub(100);
        $sale = $this->makePercentageOffSale(80);
        $sale2 = $this->makePercentageOffSale(80);

        $sale->apply($stub);
        $sale2->apply($stub);

        $this->assertEquals(Money::EUR(100), $stub->price());
        $this->assertEquals(Money::EUR(100), $stub->saleTotal());
        $this->assertEquals(Money::EUR(0), $stub->salePrice());
    }

    /** @test */
    public function sale_cannot_go_exactly_to_purchasable_original_price()
    {
        $stub = $this->makeEligibleForSaleStub(100);
        $sale = $this->makePercentageOffSale(50);
        $sale2 = $this->makePercentageOffSale(50);

        $sale->apply($stub);
        $sale2->apply($stub);

        $this->assertEquals(Money::EUR(100), $stub->price());
        $this->assertEquals(Money::EUR(100), $stub->saleTotal());
        $this->assertEquals(Money::EUR(0), $stub->salePrice());
    }
}
