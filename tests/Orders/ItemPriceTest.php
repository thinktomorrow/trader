<?php

use Money\Money;
use Thinktomorrow\Trader\Common\Price\Percentage;
use Thinktomorrow\Trader\Tests\Stubs\PurchasableStub;
use Thinktomorrow\Trader\Tests\TestCase;

class ItemPriceTest extends TestCase
{
    /** @test */
    public function it_can_get_base_price()
    {
        $item = $this->getItem(null, null, new PurchasableStub(99, [], Money::EUR(125), Percentage::fromPercent(6)));

        $this->assertEquals(Money::EUR(125), $item->price());
    }

    /** @test */
    public function it_can_get_salePrice()
    {
        $item = $this->getItem(null, null, new PurchasableStub(99, [], Money::EUR(125), Percentage::fromPercent(6),
            Money::EUR(110)
        ));

        $this->assertEquals(Money::EUR(110), $item->salePrice());
    }

    /** @test */
    public function total_price_is_sum_of_quantity()
    {
        $item = $this->getItem(null, null, new PurchasableStub(99, [], Money::EUR(125), Percentage::fromPercent(6)));
        $item->add(1);

        $this->assertEquals(Money::EUR(250), $item->total());
    }

    /** @test */
    public function if_quantity_is_zero_so_is_total_price()
    {
        $item = $this->getItem(null, null, new PurchasableStub(99, [], Money::EUR(125), Percentage::fromPercent(6)));
        $item->remove(1);

        $this->assertEquals(Money::EUR(125), $item->price());
        $this->assertEquals(Money::EUR(0), $item->total());
    }

    /** @test */
    public function total_price_is_quantification_of_salePrice()
    {
        $item = $this->getItem(null, null, new PurchasableStub(99, [], Money::EUR(125), Percentage::fromPercent(6),
            Money::EUR(110)
        ));
        $item->add(1);

        $this->assertEquals(Money::EUR(220), $item->total());
    }

    /** @test */
    public function tax_is_inclusive_and_based_on_total_price()
    {
        // Without salePrice
        $item = $this->getItem(null, Percentage::fromPercent(6), new PurchasableStub(99, [], Money::EUR(125), Percentage::fromPercent(6)));

        $this->assertEquals(Money::EUR(125), $item->total());
        $this->assertEquals(Money::EUR(125)->multiply(0.06), $item->taxTotal());

        // With SalePrice
        $item = $this->getItem(null, Percentage::fromPercent(6), new PurchasableStub(99, [], Money::EUR(125), Percentage::fromPercent(6), Money::EUR(99)));

        $this->assertEquals(Money::EUR(99), $item->total());
        $this->assertEquals(Money::EUR(99)->multiply(0.06), $item->taxTotal());
    }
}
