<?php

namespace Thinktomorrow\Trader\Tests;

use Thinktomorrow\Trader\Discounts\Domain\AppliedDiscount;
use Thinktomorrow\Trader\Discounts\Domain\Discount;
use Thinktomorrow\Trader\Discounts\Domain\DiscountId;
use Thinktomorrow\Trader\Discounts\Domain\Types\TypeKey;
use Thinktomorrow\Trader\Orders\Domain\Order;

class DiscountTypeTest extends UnitTestCase
{
    /** @test */
    public function it_only_accepts_available_type_keys()
    {
        $this->expectException(\InvalidArgumentException::class);

        TypeKey::fromString('test');
    }

    /** @test */
    public function it_only_accepts_available_discount_classnames()
    {
        $this->expectException(\InvalidArgumentException::class);

        TypeKey::fromDiscount(new UnknownDiscount(DiscountId::fromInteger(1), [], []));
    }

    /** @test */
    public function it_accepts_available_discount_classnames()
    {
        $type = TypeKey::fromDiscount($this->makePercentageOffDiscount());

        $this->assertTrue(TypeKey::fromString('percentage_off')->equals($type));
    }

    /** @test */
    public function it_gives_classname_of_type()
    {
        $type = TypeKey::fromString('percentage_off');

        $this->assertEquals(get_class($this->makePercentageOffDiscount()), $type->class());
    }
}

class UnknownDiscount implements Discount
{
    public function __construct(DiscountId $id, array $conditions, array $adjusters)
    {
    }

    public function id(): DiscountId
    {
    }

    public function apply(Order $order): AppliedDiscount
    {
    }
}