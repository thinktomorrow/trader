<?php

namespace Thinktomorrow\Trader\Tests\Unit;

use Money\Money;
use Thinktomorrow\Trader\Common\Domain\Description;
use Thinktomorrow\Trader\Discounts\Domain\AppliedDiscount;
use Thinktomorrow\Trader\Discounts\Domain\DiscountId;
use Thinktomorrow\Trader\Discounts\Domain\Types\TypeKey;

class AppliedDiscountTest extends UnitTestCase
{
    /** @test */
    function it_can_create_applied_discount()
    {
        $discountId = DiscountId::fromInteger(1);
        $discountType = TypeKey::fromString('percentage_off');
        $description = new Description('foo',[]);
        $amount = Money::EUR(20);

        $appliedDiscount = new AppliedDiscount(
            $discountId,
            $discountType,
            $description,
            $amount
        );

        $this->assertSame($discountId, $appliedDiscount->discountId());
        $this->assertSame($discountType, $appliedDiscount->type());
        $this->assertSame($description, $appliedDiscount->description());
        $this->assertSame($amount, $appliedDiscount->amount());
    }
}