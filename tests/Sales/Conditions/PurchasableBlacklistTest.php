<?php

namespace Thinktomorrow\Trader\Tests\Sales\Conditions;

use Money\Money;
use Thinktomorrow\Trader\Sales\Domain\Conditions\PurchasableBlacklist;
use Thinktomorrow\Trader\Tests\TestCase;
use Thinktomorrow\Trader\Tests\Stubs\PurchasableStub;

class PurchasableBlacklistTest extends TestCase
{
    /** @test */
    public function purchasable_sale_passes_if_no_blacklist_is_enforced()
    {
        $condition = new PurchasableBlacklist();
        $stub = new PurchasableStub(2,[], Money::EUR(120));

        $this->assertTrue($condition->check($stub));
    }

    /** @test */
    public function passed_value_must_be_an_array_of_ids()
    {
        $this->expectException(\InvalidArgumentException::class);

        (new PurchasableBlacklist())->setParameters(['purchasable_blacklist' => 'foobar']);
    }

    /** @test */
    public function purchasable_sale_passes_if_given_purchasable_is_not_in_blacklist()
    {
        $stub = new PurchasableStub(2,[], Money::EUR(120));

        $condition = (new PurchasableBlacklist())->setParameters(['purchasable_blacklist' => [5]]);
        $this->assertTrue($condition->check($stub));
    }

    /** @test */
    public function purchasable_sale_does_not_pass_if_given_purchasable_is_in_blacklist()
    {
        $stub = new PurchasableStub(2,[], Money::EUR(120));

        $condition = (new PurchasableBlacklist())->setParameters(['purchasable_blacklist' => [2]]);
        $this->assertFalse($condition->check($stub));
    }
}
