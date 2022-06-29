<?php
declare(strict_types=1);

namespace Tests\Unit\Model;

use Tests\Unit\TestCase;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\Discount;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountableType;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountId;

class OrderDiscountTest extends TestCase
{
    /** @test */
    public function it_can_add_a_discount()
    {
        $order = $this->createdOrder();

        $order->addDiscount($this->createOrderDiscount($order->orderId, ['promo_discount_id' => 'qqq', 'discount_id' => 'defgh']));

        $this->assertCount(2, $order->getChildEntities()[Discount::class]);
    }

    /** @test */
    public function it_cannot_add_same_applied_discount_twice()
    {
        $this->expectException(\InvalidArgumentException::class);

        $order = $this->createdOrder();

        $order->addDiscount($this->createOrderDiscount($order->orderId, ['promo_discount_id' => 'qqq'])); // discount_id is the same as the default which implies  a duplicate

        $this->assertCount(1, $order->getChildEntities()[Discount::class]);
    }

    /** @test */
    public function it_cannot_add_same_promo_discount_twice()
    {
        $this->expectException(\InvalidArgumentException::class);

        $order = $this->createdOrder();

        $order->addDiscount($this->createOrderDiscount($order->orderId, ['discount_id' => 'defgh'])); // promo_discount_id is same as already set on createdOrder

        $this->assertCount(1, $order->getChildEntities()[Discount::class]);
    }

    /** @test */
    public function it_cannot_add_discount_with_discountable_type_mismatch()
    {
        $this->expectException(\InvalidArgumentException::class);

        $order = $this->createdOrder();

        $order->addDiscount($this->createOrderDiscount($order->orderId, ['promo_discount_id' => 'qqq', 'discount_id' => 'defgh', 'discountable_type' => DiscountableType::line->value]));

        $this->assertCount(1, $order->getChildEntities()[Discount::class]);
    }

    /** @test */
    public function it_cannot_add_discount_with_discountable_id_mismatch()
    {
        $this->expectException(\InvalidArgumentException::class);

        $order = $this->createdOrder();

        $order->addDiscount($this->createOrderDiscount($order->orderId, ['promo_discount_id' => 'qqq', 'discount_id' => 'defgh', 'discountable_id' => 'foobar']));

        $this->assertCount(1, $order->getChildEntities()[Discount::class]);
    }

    /** @test */
    public function it_can_delete_a_discount()
    {
        $order = $this->createdOrder();

        $this->assertCount(1, $order->getChildEntities()[Discount::class]);

        $order->deleteDiscount(
            DiscountId::fromString('ababab'),
        );

        $this->assertCount(0, $order->getChildEntities()[Discount::class]);
    }
}
