<?php

namespace Thinktomorrow\Trader\Tests;

use Money\Money;
use Thinktomorrow\Trader\Discounts\Domain\Bases\Base;
use Thinktomorrow\Trader\Discounts\Domain\Bases\DiscountBase;
use Thinktomorrow\Trader\Discounts\Domain\Discount;
use Thinktomorrow\Trader\Discounts\Domain\DiscountId;
use Thinktomorrow\Trader\Discounts\Domain\EligibleForDiscount;
use Thinktomorrow\Trader\Discounts\Domain\Types\TypeKey;
use Thinktomorrow\Trader\Orders\Domain\Order;

class DiscountTypeTest extends TestCase
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

    public function getBaseType(): string
    {
        return 'basket';
    }

    public function getBase(Order $order): EligibleForDiscount
    {
        return $order;
    }

    public function getType(): string
    {
        return 'unknown';
    }

    public function applicable(Order $order, EligibleForDiscount $eligibleForDiscount): bool
    {
        // TODO: Implement applicable() method.
    }

    public function apply(Order $order, EligibleForDiscount $eligibleForDiscount)
    {
        // TODO: Implement apply() method.
    }

    public function discountAmount(Order $order, EligibleForDiscount $eligibleForDiscount): Money
    {
        // TODO: Implement discountAmount() method.
    }

    public function discountBasePrice(Order $order, EligibleForDiscount $eligibleForDiscount): Money
    {
        // TODO: Implement discountBasePrice() method.
    }

    public function usesCondition(string $condition_key): bool
    {
        return false;
    }

    public function data($key = null)
    {
        return 'value';
    }
}
