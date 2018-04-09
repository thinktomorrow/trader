<?php

namespace Thinktomorrow\Trader\Tests;

use Money\Money;
use Thinktomorrow\Trader\Common\Adjusters\Percentage;
use Thinktomorrow\Trader\Common\Price\Percentage as PercentageValue;
use Thinktomorrow\Trader\Sales\Domain\Exceptions\CannotApplySale;
use Thinktomorrow\Trader\Sales\Domain\SaleId;
use Thinktomorrow\Trader\Sales\Domain\Types\FixedCustomAmountSale;

class FixedCustomAmountSaleTest extends TestCase
{
    /** @test */
    public function fixed_custom_amount_sale_cannot_be_negative()
    {
        $stub = $this->makeEligibleForSaleStub(100);
        $stub->original_saleprice = Money::EUR(-10);

        $sale = $this->makeFixedCustomAmountSale();
        $this->assertFalse($sale->applicable($stub));
    }

    /** @test */
    public function fixed_custom_amount_is_set_as_new_saleprice()
    {
        $stub = $this->makeEligibleForSaleStub(120);
        $stub->original_saleprice = Money::EUR(35);

        $sale = $this->makeFixedCustomAmountSale();

        $sale->apply($stub);

        $this->assertEquals(Money::EUR(120), $stub->price());
        $this->assertEquals(Money::EUR(85), $stub->saleTotal());
        $this->assertEquals(Money::EUR(35), $stub->salePrice());
    }

    /** @test */
    public function sale_cannot_be_higher_than_original_price()
    {
        $this->expectException(CannotApplySale::class);

        $stub = $this->makeEligibleForSaleStub(100);
        $stub->original_saleprice = Money::EUR(120);
        $sale = $this->makeFixedCustomAmountSale();

        $sale->apply($stub);
    }

    /** @test */
    public function fixed_custom_amount_sale_can_go_to_zero()
    {
        $stub = $this->makeEligibleForSaleStub(100);
        $stub->original_saleprice = Money::EUR(0);
        $sale = $this->makeFixedCustomAmountSale();

        $sale->apply($stub);

        $this->assertEquals(Money::EUR(100), $stub->price());
        $this->assertEquals(Money::EUR(100), $stub->saleTotal());
        $this->assertEquals(Money::EUR(0), $stub->salePrice());
    }

    /** @test */
    public function fixed_custom_amount_sale_is_not_applied_if_original_saleprice_is_zero()
    {
        $this->expectException(CannotApplySale::class);

        $stub = $this->makeEligibleForSaleStub(100);
        $stub->original_saleprice = null;
        $sale = $this->makeFixedCustomAmountSale();

        $sale->apply($stub);

        $this->assertEquals(Money::EUR(100), $stub->price());
        $this->assertEquals(Money::EUR(0), $stub->saleTotal());
        $this->assertEquals(Money::EUR(100), $stub->salePrice());
    }

    /** @test */
    public function it_requires_an_amount_adjuster()
    {
        $this->expectException(\InvalidArgumentException::class);

        new FixedCustomAmountSale(
            SaleId::fromInteger(1),
            [],
            (new Percentage())->setParameters(PercentageValue::fromPercent(5)),
            []
        );
    }
}
