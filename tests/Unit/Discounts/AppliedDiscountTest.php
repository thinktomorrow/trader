<?php

namespace Thinktomorrow\Trader\Tests\Unit;

use Thinktomorrow\Trader\Discounts\Domain\AppliedDiscount;
use Thinktomorrow\Trader\Discounts\Domain\DiscountDescription;
use Thinktomorrow\Trader\Discounts\Domain\DiscountId;
use Thinktomorrow\Trader\Discounts\Domain\DiscountType;
use Thinktomorrow\Trader\Order\Domain\ItemCollection;

class AppliedDiscountTest extends UnitTestCase
{
    /** @test */
    function it_can_create_applied_discount()
    {
        $discountId = DiscountId::fromInteger(1);
        $discountType = DiscountType::fromString('foobar');
        $description = new DiscountDescription('foo',[]);
        $affectedItems = new ItemCollection();

        $appliedDiscount = new AppliedDiscount(
            $discountId,
            $discountType,
            $description,
            $affectedItems
        );

        $this->assertSame($discountId, $appliedDiscount->discountId());
        $this->assertSame($discountType, $appliedDiscount->discountType());
        $this->assertSame($description, $appliedDiscount->description());
        $this->assertSame($affectedItems, $appliedDiscount->affectedItems());
    }
}