<?php

namespace Thinktomorrow\Trader\Tests\Unit;

use Money\Money;
use Thinktomorrow\Trader\Sales\Domain\SaleId;
use Thinktomorrow\Trader\Tests\Unit\Stubs\ConcretePurchasable;

class SaleTest extends UnitTestCase
{
    /** @test */
    function saleId_is_a_valid_identifier()
    {
        $saleId = SaleId::fromInteger(2);

        $this->assertEquals(2,$saleId->get());
    }

    /** @test */
    function it_applies_sale_to_a_purchasable()
    {
        $purchasable = $this->makePurchasable(100);
        $sale = $this->makePercentageOffSale(20);

//        $sale->apply($purchasable);
//
//        $this->assertCount(1,$purchasable->sales());
//        $this->assertEquals(Money::EUR(80),$purchasable->total());
    }

    /** @test */
    function sale_cannot_go_below_purchasable_original_price()
    {
        $purchasable = $this->makePurchasable(50);
        $sale = $this->makePercentageOffSale(120);

//        $sale->apply($purchasable);
//
//        $this->assertEquals(Money::EUR(0), $purchasable->total());
    }

    private function makePurchasable($amount)
    {
        $price = Money::EUR($amount);

        return new ConcretePurchasable(1,[],$price);
    }

    private function makePercentageOffSale($percent)
    {
        // TODO
    }
}