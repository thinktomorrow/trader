<?php

namespace Thinktomorrow\Trader\Tests;

use Money\Money;
use Thinktomorrow\Trader\Common\Adjusters\Percentage;
use Thinktomorrow\Trader\Common\Price\Percentage as PercentageValue;
use Thinktomorrow\Trader\Sales\Domain\Exceptions\CannotApplySale;
use Thinktomorrow\Trader\Sales\Domain\SaleId;
use Thinktomorrow\Trader\Sales\Domain\Types\FixedAmountOffSale;

class FixedAmountOffSaleTest extends TestCase
{
    /** @test */
    public function fixed_amount_off_sale_cannot_be_negative()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->makeFixedAmountOffSale(-10);
    }

    /** @test */
    public function fixed_amount_off_is_subtracted_from_original_price()
    {
        $stub = $this->makeEligibleForSaleStub(100);
        $sale = $this->makeFixedAmountOffSale(80);

        $sale->apply($stub);

        $this->assertEquals(Money::EUR(100), $stub->price());
        $this->assertEquals(Money::EUR(80), $stub->saleTotal());
        $this->assertEquals(Money::EUR(20), $stub->salePrice());
    }

    /** @test */
    public function sale_cannot_be_higher_than_original_price()
    {
        $this->expectException(CannotApplySale::class);

        $stub = $this->makeEligibleForSaleStub(100);
        $sale = $this->makeFixedAmountOffSale(80);
        $sale2 = $this->makeFixedAmountOffSale(80);

        $sale->apply($stub);
        $sale2->apply($stub);
    }

    /** @test */
    public function sale_can_go_exactly_to_purchasable_original_price()
    {
        $stub = $this->makeEligibleForSaleStub(100);
        $sale = $this->makeFixedAmountOffSale(50);
        $sale2 = $this->makeFixedAmountOffSale(50);

        $sale->apply($stub);
        $sale2->apply($stub);

        $this->assertEquals(Money::EUR(100), $stub->price());
        $this->assertEquals(Money::EUR(100), $stub->saleTotal());
        $this->assertEquals(Money::EUR(0), $stub->salePrice());
    }

    /** @test */
    public function it_requires_an_amount_adjuster()
    {
        $this->expectException(\InvalidArgumentException::class);

        new FixedAmountOffSale(
            SaleId::fromInteger(1),
            [],
            (new Percentage())->setParameters(PercentageValue::fromPercent(5)),
            []
        );
    }
}
