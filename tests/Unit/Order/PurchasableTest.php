<?php

namespace Thinktomorrow\Trader\Tests\Unit;

use Thinktomorrow\Trader\Tests\Unit\Stubs\ConcretePurchasable;

class PurchasableTest extends UnitTestCase
{
    /** @test */
    function it_can_get_itemid()
    {
        $purchasable = new ConcretePurchasable(1);

        $this->assertEquals(1,$purchasable->itemId());
    }

    /** @test */
    function it_can_get_extra_data()
    {
        $purchasable = new ConcretePurchasable(1,['foo' => 'bar']);

        $this->assertEquals(['foo' => 'bar'],$purchasable->itemData());
    }
}