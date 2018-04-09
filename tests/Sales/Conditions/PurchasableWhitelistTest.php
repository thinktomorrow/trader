<?php

namespace Thinktomorrow\Trader\Tests\Sales\Conditions;

use Money\Money;
use Thinktomorrow\Trader\Sales\Domain\Conditions\PurchasableWhitelist;
use Thinktomorrow\Trader\Tests\Stubs\PurchasableStub;
use Thinktomorrow\Trader\Tests\TestCase;

class PurchasableWhitelistTest extends TestCase
{
    /** @test */
    public function purchasable_sale_passes_if_no_whitelist_is_enforced()
    {
        $condition = new PurchasableWhitelist();
        $stub = new PurchasableStub(2, [], Money::EUR(120));

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
        $stub = new PurchasableStub(2, [], Money::EUR(120));

        $condition = (new PurchasableWhitelist())->setParameters(['purchasable_whitelist' => [2]]);
        $this->assertTrue($condition->check($stub));
    }

    /** @test */
    public function purchasable_sale_does_not_pass_if_given_purchasable_is_not_in_whitelist()
    {
        $stub = new PurchasableStub(2, [], Money::EUR(120));

        $condition = (new PurchasableWhitelist())->setParameters(['purchasable_whitelist' => [5]]);
        $this->assertFalse($condition->check($stub));
    }

    /** @test */
    public function it_can_set_parameters_from_raw_values()
    {
        // Current time falls out of given period
        $condition1 = (new PurchasableWhitelist())->setParameters([
            'purchasable_whitelist' => [5, 10],
        ]);

        $condition2 = (new PurchasableWhitelist())->setParameterValues([
            'purchasable_whitelist' => [5, 10],
        ]);

        $condition3 = (new PurchasableWhitelist())->setParameterValues([5, 10]);

        $this->assertEquals($condition1, $condition2);
        $this->assertEquals($condition1, $condition3);
    }
}
