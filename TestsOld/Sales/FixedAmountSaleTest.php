<?php

namespace Thinktomorrow\Trader\TestsOld;

use Money\Money;
use Thinktomorrow\Trader\Common\Domain\Adjusters\Percentage;
use Thinktomorrow\Trader\Common\Price\Percentage as PercentageValue;
use Thinktomorrow\Trader\Sales\Domain\Exceptions\CannotApplySale;
use Thinktomorrow\Trader\Sales\Domain\SaleId;
use Thinktomorrow\Trader\Sales\Domain\Types\FixedAmountSale;

class FixedAmountSaleTest extends TestCase
{
    /** @test */
    public function fixed_amount_sale_cannot_be_negative()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->makeFixedAmountSale(-10);
    }

    /** @test */
    public function fixed_amount_is_subtracted_from_original_price()
    {
        $stub = $this->makeEligibleForSaleStub(100);
        $sale = $this->makeFixedAmountSale(60);

        $sale->apply($stub);

        $this->assertEquals(Money::EUR(100), $stub->price());
        $this->assertEquals(Money::EUR(40), $stub->saleTotal());
        $this->assertEquals(Money::EUR(60), $stub->salePrice());
    }

    /** @test */
    public function sale_cannot_be_higher_than_original_price()
    {
        $this->expectException(CannotApplySale::class);

        $stub = $this->makeEligibleForSaleStub(100);
        $sale = $this->makeFixedAmountSale(120);

        $sale->apply($stub);
    }

    /** @test */
    public function fixed_amount_sale_can_go_to_zero()
    {
        $stub = $this->makeEligibleForSaleStub(100);
        $sale = $this->makeFixedAmountSale(0);

        $sale->apply($stub);

        $this->assertEquals(Money::EUR(100), $stub->price());
        $this->assertEquals(Money::EUR(100), $stub->saleTotal());
        $this->assertEquals(Money::EUR(0), $stub->salePrice());
    }

    /** @test */
    public function it_requires_an_amount_adjuster()
    {
        $this->expectException(\InvalidArgumentException::class);

        new FixedAmountSale(
            SaleId::fromInteger(1),
            [],
            (new Percentage())->setParameters(PercentageValue::fromPercent(5)),
            []
        );
    }
}
