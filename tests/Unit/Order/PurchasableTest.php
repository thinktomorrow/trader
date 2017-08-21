<?php

namespace Thinktomorrow\Trader\Tests\Unit;

use Thinktomorrow\Trader\Order\Domain\ItemId;
use Thinktomorrow\Trader\Tests\Unit\Stubs\PurchasableStub;

class PurchasableTest extends UnitTestCase
{
    /** @test */
    public function it_can_get_itemid()
    {
        $purchasable = new PurchasableStub(1);

        $this->assertEquals(ItemId::fromInteger(1), $purchasable->itemId());
    }

    /** @test */
    public function it_can_get_extra_data()
    {
        $purchasable = new PurchasableStub(1, ['foo' => 'bar']);

        $this->assertEquals(['foo' => 'bar'], $purchasable->itemData());
    }
}
