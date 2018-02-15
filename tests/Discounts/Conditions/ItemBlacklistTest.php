<?php

namespace Thinktomorrow\Trader\Tests;

use Money\Money;
use Thinktomorrow\Trader\Discounts\Domain\Conditions\ItemBlacklist;
use Thinktomorrow\Trader\Orders\Domain\Item;
use Thinktomorrow\Trader\Tests\Stubs\PurchasableStub;

class ItemBlacklistTest extends TestCase
{
    /** @test */
    public function it_passes_if_no_blacklist_is_enforced()
    {
        $condition = new ItemBlacklist();
        $item = $this->getItem(null, null, new PurchasableStub(20, [], Money::EUR(110)), 2);

        $this->assertTrue($condition->check($this->makeOrder(), $item));
    }

    /** @test */
    public function passed_value_must_be_an_array_of_ids()
    {
        $this->expectException(\InvalidArgumentException::class);

        (new ItemBlacklist())->setParameters(['item_blacklist' => 'foobar']);
    }

    /** @test */
    public function it_passes_if_given_item_is_not_in_blacklist()
    {
        $order = $this->makeOrder();
        $item = $this->getItem(null, null, new PurchasableStub(20, [], Money::EUR(110)), 2);

        $condition = (new ItemBlacklist())->setParameters(['item_blacklist' => [5]]);
        $this->assertTrue($condition->check($order, $item));
    }

    /** @test */
    public function it_does_not_pass_if_given_item_is_in_blacklist()
    {
        $order = $this->makeOrder();
        $item = $this->getItem(null, null, new PurchasableStub(20, [], Money::EUR(110)), 2);

        $condition = (new ItemBlacklist())->setParameters(['item_blacklist' => [20]]);
        $this->assertFalse($condition->check($order, $item));
    }


}
