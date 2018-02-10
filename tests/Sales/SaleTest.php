<?php

namespace Thinktomorrow\Trader\Tests;

use Money\Money;
use Thinktomorrow\Trader\Sales\Domain\SaleId;

class SaleTest extends TestCase
{
    /** @test */
    public function saleId_is_a_valid_identifier()
    {
        $saleId = SaleId::fromInteger(2);

        $this->assertEquals(2, $saleId->get());
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
}
