<?php

use Thinktomorrow\Trader\Tests\Unit\UnitTestCase;

use Money\Money;
use Thinktomorrow\Trader\Order\Domain\Item;
use Thinktomorrow\Trader\Price\Percentage;
use Thinktomorrow\Trader\Tests\Unit\Stubs\ConcretePurchasable;

class ItemPriceTest extends UnitTestCase
{

    /** @test */
    function it_can_get_base_price()
    {
        $item = Item::fromPurchasable(new ConcretePurchasable(99,[],Money::EUR(125),Percentage::fromPercent(6)));

        $this->assertEquals(Money::EUR(125),$item->price());
    }

    /** @test */
    function it_can_get_salePrice()
    {
        $item = Item::fromPurchasable(new ConcretePurchasable(99,[],Money::EUR(125),Percentage::fromPercent(6),
            Money::EUR(110)
        ));

        $this->assertEquals(Money::EUR(110),$item->salePrice());
    }

    /** @test */
    function total_price_is_sum_of_quantity()
    {
        $item = Item::fromPurchasable(new ConcretePurchasable(99,[],Money::EUR(125),Percentage::fromPercent(6)));
        $item->add(1);

        $this->assertEquals(Money::EUR(250),$item->total());
    }

    /** @test */
    function if_quantity_is_zero_so_is_total_price()
    {
        $item = Item::fromPurchasable(new ConcretePurchasable(99,[],Money::EUR(125),Percentage::fromPercent(6)));
        $item->remove(1);

        $this->assertEquals(Money::EUR(125),$item->price());
        $this->assertEquals(Money::EUR(0),$item->total());
    }

    /** @test */
    function total_price_is_quantification_of_salePrice()
    {
        $item = Item::fromPurchasable(new ConcretePurchasable(99,[],Money::EUR(125),Percentage::fromPercent(6),
            Money::EUR(110)
        ));
        $item->add(1);

        $this->assertEquals(Money::EUR(220),$item->total());
    }

    /** @test */
    function tax_is_inclusive_and_based_on_total_price()
    {
        // Without salePrice
        $item = Item::fromPurchasable(new ConcretePurchasable(99,[],Money::EUR(125),Percentage::fromPercent(6)));

        $this->assertEquals(Money::EUR(125),$item->total());
        $this->assertEquals(Money::EUR(125)->multiply(0.06),$item->tax());

        // With SalePrice
        $item = Item::fromPurchasable(new ConcretePurchasable(99,[],Money::EUR(125),Percentage::fromPercent(6),Money::EUR(99)));

        $this->assertEquals(Money::EUR(99),$item->total());
        $this->assertEquals(Money::EUR(99)->multiply(0.06),$item->tax());
    }
}