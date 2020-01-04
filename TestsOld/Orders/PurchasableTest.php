<?php

namespace Thinktomorrow\Trader\TestsOld;

use Thinktomorrow\Trader\Orders\Domain\PurchasableId;
use Thinktomorrow\Trader\TestsOld\Stubs\PurchasableStub;

class PurchasableTest extends TestCase
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
