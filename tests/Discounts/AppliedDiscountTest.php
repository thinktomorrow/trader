<?php

namespace Thinktomorrow\Trader\Tests;

use Money\Money;
use Thinktomorrow\Trader\Common\Domain\Price\Percentage;
use Thinktomorrow\Trader\Discounts\Domain\AppliedDiscount;
use Thinktomorrow\Trader\Discounts\Domain\DiscountId;
use Thinktomorrow\Trader\Discounts\Domain\Types\TypeKey;

class AppliedDiscountTest extends TestCase
{
    /** @test */
    public function it_can_create_applied_discount()
    {
        $discountId = DiscountId::fromInteger(1);
        $data = ['foobar'];
        $amount = Money::EUR(20);
        $percentage = Percentage::fromPercent(15);

        $appliedDiscount = new AppliedDiscount(
            $discountId,
            'foobar',
            $amount,
            $percentage,
            $data
        );

        $this->assertSame($discountId, $appliedDiscount->discountId());
        $this->assertSame('foobar', $appliedDiscount->discountType());
        $this->assertSame($amount, $appliedDiscount->discountAmount());
        $this->assertSame($percentage, $appliedDiscount->discountPercentage());
        $this->assertSame($data, $appliedDiscount->data());
    }
}
