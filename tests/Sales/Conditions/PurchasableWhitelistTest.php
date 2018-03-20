<?php

namespace Thinktomorrow\Trader\Tests\Sales\Conditions;

use Money\Money;
use Thinktomorrow\Trader\Sales\Domain\Conditions\PurchasableWhitelist;
use Thinktomorrow\Trader\Tests\TestCase;
use Thinktomorrow\Trader\Tests\Stubs\PurchasableStub;

class PurchasableWhitelistTest extends TestCase
{
    /** @test */
    public function purchasable_sale_passes_if_no_whitelist_is_enforced()
    {
        $condition = new PurchasableWhitelist();
        $stub = new PurchasableStub(2,[], Money::EUR(120));

        $this->assertTrue($condition->check($stub));
    }

    /** @test */
    public function passed_value_must_be_an_array_of_ids()
    {
        $this->expectException(\InvalidArgumentException::class);

        (new PurchasableWhitelist())->setParameters(['purchasable_whitelist' => 'foobar']);
    }

    /** @test */
    public function purchasable_sale_passes_if_given_purchasable_is_in_whitelist()
    {
        $stub = new PurchasableStub(2,[], Money::EUR(120));

        $condition = (new PurchasableWhitelist())->setParameters(['purchasable_whitelist' => [2]]);
        $this->assertTrue($condition->check($stub));
    }

    /** @test */
    public function purchasable_sale_does_not_pass_if_given_purchasable_is_not_in_whitelist()
    {
        $stub = new PurchasableStub(2,[], Money::EUR(120));

        $condition = (new PurchasableWhitelist())->setParameters(['purchasable_whitelist' => [5]]);
        $this->assertFalse($condition->check($stub));
    }
}
