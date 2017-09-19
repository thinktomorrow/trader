<?php

namespace Thinktomorrow\Trader\Tests;

use Money\Money;
use Thinktomorrow\Trader\Discounts\Domain\Conditions\ItemWhitelist;
use Thinktomorrow\Trader\Orders\Domain\Item;
use Thinktomorrow\Trader\Tests\Stubs\PurchasableStub;

class ItemWhitelistTest extends UnitTestCase
{
    /** @test */
    public function it_checks_ok_if_no_whitelist_is_enforced()
    {
        $condition = new ItemWhitelist();
        $item = Item::fromPurchasable(new PurchasableStub(20, [], Money::EUR(110)), 2);

        $this->assertTrue($condition->check($this->makeOrder(), $item));
    }

    /** @test */
    public function passed_value_must_be_an_array_of_ids()
    {
        $this->expectException(\InvalidArgumentException::class);

        (new ItemWhitelist())->setParameters(['purchasable_ids' => 'foobar']);
    }

    /** @test */
    public function it_checks_if_given_item_is_in_whitelist()
    {
        $order = $this->makeOrder();
        $item = Item::fromPurchasable(new PurchasableStub(20, [], Money::EUR(110)), 2);

        $condition = (new ItemWhitelist())->setParameters(['purchasable_ids' => [20]]);
        $this->assertTrue($condition->check($order, $item));
    }
}
