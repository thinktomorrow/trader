<?php

namespace Thinktomorrow\Trader\Tests\Discounts\Types;

use Money\Money;
use Thinktomorrow\Trader\Common\Adjusters\Amount;
use Thinktomorrow\Trader\Common\Adjusters\Percentage;
use Thinktomorrow\Trader\Common\Price\Percentage as PercentageValue;
use Thinktomorrow\Trader\Discounts\Domain\DiscountId;
use Thinktomorrow\Trader\Discounts\Domain\EligibleForDiscount;
use Thinktomorrow\Trader\Discounts\Domain\Exceptions\CannotApplyDiscount;
use Thinktomorrow\Trader\Discounts\Domain\Types\FixedAmountOffDiscount;
use Thinktomorrow\Trader\Orders\Domain\Order;
use Thinktomorrow\Trader\Tests\TestCase;

class FixedAmountOffTest extends TestCase
{
    /** @test */
    public function fixed_amount_discount_cannot_be_negative()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->makeFixedAmountOffDiscount(-10);
    }

    /** @test */
    public function fixed_amount_is_subtracted_from_original_price()
    {
        list($order, $item) = $this->prepOrderWithItem(100);
        $discount = $this->makeFixedAmountOffDiscount(15);

        $discount->apply($order, $order);

        $this->assertEquals(Money::EUR(100), $order->subtotal());
        $this->assertEquals(Money::EUR(100), $order->discountBasePrice());
        $this->assertEquals(Money::EUR(15), $order->discountTotal());
        $this->assertEquals(Money::EUR(85), $order->total());
        $this->assertCount(1, $order->discounts());
    }

    /** @test */
    public function for_item_discount_fixed_amount_is_subtracted_from_sale_price()
    {
        list($order, $item) = $this->prepOrderWithItem(100, 90);
        $discount = $this->makeFixedAmountOffDiscount(9);

        $discount->apply($order, $item);

        $this->assertCount(1, $item->discounts());
        $this->assertEquals(Money::EUR(90), $item->discountBasePrice());
        $this->assertEquals(Money::EUR(9), $item->discountTotal());

        $this->assertCount(0, $order->discounts());
        $this->assertEquals(Money::EUR(81), $order->subtotal());
        $this->assertEquals(Money::EUR(0), $order->discountTotal());
        $this->assertEquals(Money::EUR(81), $order->total());
    }

    /** @test */
    public function discount_is_trimmed_if_higher_than_original_price()
    {
//        $this->expectException(CannotApplyDiscount::class);

        list($order, $item) = $this->prepOrderWithItem(100);
        $discount = $this->makeFixedAmountOffDiscount(120);

        $discount->apply($order, $item);

        $this->assertEquals(Money::EUR(100), $item->discounts()[0]->discountAmount());
        $this->assertEquals(Money::EUR(100), $item->discountTotal());
        $this->assertEquals(Money::EUR(0), $order->total());
    }

    /** @test */
    public function discount_cannot_be_higher_than_original_price()
    {
        $this->expectException(CannotApplyDiscount::class);

        $order = $this->makeOrder(100);
        $discount = new FixedAmountOffDiscountWithoutFlexibleAmount(
            DiscountId::fromInteger(rand(1, 99)),
            [],
            (new Amount())->setParameters(Money::EUR(120)),
            []
        );

        $discount->apply($order, $order);
    }

    /** @test */
    public function fixed_amount_discount_can_go_to_zero()
    {
        list($order, $item) = $this->prepOrderWithItem(100);
        $discount = $this->makeFixedAmountOffDiscount(0);

        $discount->apply($order, $item);

        $this->assertEquals(Money::EUR(100), $order->subtotal());
        $this->assertEquals(Money::EUR(100), $order->discountBasePrice());
        $this->assertEquals(Money::EUR(0), $order->discountTotal());
        $this->assertEquals(Money::EUR(100), $order->total());
    }

    /** @test */
    public function it_requires_an_amount_adjuster()
    {
        $this->expectException(\InvalidArgumentException::class);

        new FixedAmountOffDiscount(
            DiscountId::fromInteger(1),
            [],
            (new Percentage())->setParameters(PercentageValue::fromPercent(5)),
            []
        );
    }
}

class FixedAmountOffDiscountWithoutFlexibleAmount extends FixedAmountOffDiscount
{
    public function discountAmount(Order $order, EligibleForDiscount $eligibleForDiscount): Money
    {
        return $this->adjuster->getParameter('amount');
    }
}
