<?php
declare(strict_types=1);

namespace Tests\Unit\Model\Order;

use Tests\Unit\TestCase;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\Discount;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountId;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountableType;

class OrderDiscountTest extends TestCase
{
    /** @test */
    public function it_can_add_a_discount()
    {
        $order = $this->createDefaultOrder();

        $order->addDiscount($this->createOrderDiscount(['promo_discount_id' => 'qqq', 'discount_id' => 'defgh'], $order->getMappedData()));

        $this->assertCount(2, $order->getChildEntities()[Discount::class]);
    }

    /** @test */
    public function it_cannot_add_same_applied_discount_twice()
    {
        $this->expectException(\InvalidArgumentException::class);

        $order = $this->createDefaultOrder();

        $order->addDiscount($this->createOrderDiscount(['promo_discount_id' => 'qqq'], $order->getMappedData())); // discount_id is the same as the default which implies  a duplicate

        $this->assertCount(1, $order->getChildEntities()[Discount::class]);
    }

    /** @test */
    public function it_cannot_add_same_promo_discount_twice()
    {
        $this->expectException(\InvalidArgumentException::class);

        $order = $this->createDefaultOrder();

        $order->addDiscount($this->createOrderDiscount(['discount_id' => 'defgh'], $order->getMappedData())); // promo_discount_id is same as already set on createdOrder

        $this->assertCount(1, $order->getChildEntities()[Discount::class]);
    }

    /** @test */
    public function it_cannot_add_discount_with_discountable_type_mismatch()
    {
        $this->expectException(\InvalidArgumentException::class);

        $order = $this->createDefaultOrder();

        $order->addDiscount($this->createOrderDiscount(['promo_discount_id' => 'qqq', 'discount_id' => 'defgh', 'discountable_type' => DiscountableType::line->value], $order->getMappedData()));

        $this->assertCount(1, $order->getChildEntities()[Discount::class]);
    }

    /** @test */
    public function it_cannot_add_discount_with_discountable_id_mismatch()
    {
        $this->expectException(\InvalidArgumentException::class);

        $order = $this->createDefaultOrder();

        $order->addDiscount($this->createOrderDiscount(['promo_discount_id' => 'qqq', 'discount_id' => 'defgh', 'discountable_id' => 'foobar'], $order->getMappedData()));

        $this->assertCount(1, $order->getChildEntities()[Discount::class]);
    }

    /** @test */
    public function it_can_delete_a_discount()
    {
        $order = $this->createDefaultOrder();

        $this->assertCount(1, $order->getChildEntities()[Discount::class]);

        $order->deleteDiscount(
            DiscountId::fromString('ababab'),
        );

        $this->assertCount(0, $order->getChildEntities()[Discount::class]);
    }
}
