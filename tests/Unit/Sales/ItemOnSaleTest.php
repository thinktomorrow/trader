<?php

use Money\Money;
use Thinktomorrow\Trader\Orders\Domain\Item;
use Thinktomorrow\Trader\Tests\Unit\Stubs\PurchasableStub;
use Thinktomorrow\Trader\Tests\Unit\UnitTestCase;

class ItemOnSaleTest extends UnitTestCase
{
    /** @test */
    public function purchasable_can_have_sale_price()
    {
        $salePrice = Money::EUR(99);
        $purchasable = new PurchasableStub(1, [], \Money\Money::EUR(100), \Thinktomorrow\Trader\Common\Domain\Price\Percentage::fromPercent(6), $salePrice);

        $this->assertSame($salePrice, $purchasable->salePrice());
        $this->assertEquals($salePrice->multiply(0.06), $purchasable->tax());
    }

    /** @test */
    public function sale_price_is_used_for_itemprice_in_cart()
    {
        $salePrice = Money::EUR(99);
        $purchasable = new PurchasableStub(1, [], \Money\Money::EUR(100), \Thinktomorrow\Trader\Common\Domain\Price\Percentage::fromPercent(6), $salePrice);

        $item = Item::fromPurchasable($purchasable);

        $this->assertEquals($salePrice, $item->total());
    }
}
