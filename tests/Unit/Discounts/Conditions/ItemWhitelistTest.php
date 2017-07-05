<?php

namespace Thinktomorrow\Trader\Tests\Unit;

use Money\Money;
use Thinktomorrow\Trader\Discounts\Domain\Conditions\ItemWhitelist;
use Thinktomorrow\Trader\Order\Domain\Item;
use Thinktomorrow\Trader\Tests\Unit\Stubs\ConcretePurchasable;

class ItemWhitelistTest extends UnitTestCase
{
    /** @test */
    function it_checks_ok_if_no_whitelist_is_enforced()
    {
        $condition = new ItemWhitelist();
        $item = Item::fromPurchasable(new ConcretePurchasable(20,[],Money::EUR(110)),2);

        $this->assertTrue($condition->check($this->makeOrder(), $item));
    }

    /** @test */
    function passed_value_must_be_an_array_of_ids()
    {
        $this->setExpectedException(\InvalidArgumentException::class);

        (new ItemWhitelist())->setParameters(['purchasable_ids' => 'foobar']);
    }

    /** @test */
    function it_checks_if_given_item_is_in_whitelist()
    {
        $order = $this->makeOrder();
        $item = Item::fromPurchasable(new ConcretePurchasable(20,[],Money::EUR(110)),2);

        $condition = (new ItemWhitelist())->setParameters(['purchasable_ids' => [20]]);
        $this->assertTrue($condition->check($order, $item));

    }
}