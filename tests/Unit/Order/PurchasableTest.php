<?php

namespace Thinktomorrow\Trader\Tests\Unit;

use Thinktomorrow\Trader\Orders\Domain\ItemId;
use Thinktomorrow\Trader\Orders\Domain\PurchasableId;
use Thinktomorrow\Trader\Tests\Unit\Stubs\PurchasableStub;

class PurchasableTest extends UnitTestCase
{
    /** @test */
    public function it_can_get_purchasableId()
    {
        $purchasable = new PurchasableStub(1);

        $this->assertEquals(PurchasableId::fromInteger(1), $purchasable->purchasableId());
    }

    /** @test */
    public function it_can_get_extra_data()
    {
        $purchasable = new PurchasableStub(1, ['foo' => 'bar']);

        $this->assertEquals(['foo' => 'bar'], $purchasable->itemData());
    }
}
