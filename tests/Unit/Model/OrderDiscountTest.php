<?php
declare(strict_types=1);

namespace Tests\Unit\Model;

use Tests\Unit\TestCase;
use Thinktomorrow\Trader\Domain\Common\Email;
use Thinktomorrow\Trader\Domain\Model\Customer\CustomerId;
use Thinktomorrow\Trader\Domain\Model\Order\Address\BillingAddress;
use Thinktomorrow\Trader\Domain\Model\Order\Address\ShippingAddress;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\Discount;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountId;
use Thinktomorrow\Trader\Domain\Model\Order\Events\LineAdded;
use Thinktomorrow\Trader\Domain\Model\Order\Events\LineDeleted;
use Thinktomorrow\Trader\Domain\Model\Order\Events\LineUpdated;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderCreated;
use Thinktomorrow\Trader\Domain\Model\Order\Events\OrderUpdated;
use Thinktomorrow\Trader\Domain\Model\Order\Events\ShippingAdded;
use Thinktomorrow\Trader\Domain\Model\Order\Events\ShippingUpdated;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Line;
use Thinktomorrow\Trader\Domain\Model\Order\Line\LineId;
use Thinktomorrow\Trader\Domain\Model\Order\Line\LinePrice;
use Thinktomorrow\Trader\Domain\Model\Order\Line\Quantity;
use Thinktomorrow\Trader\Domain\Model\Order\Order;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\OrderState;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\Payment;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentCost;
use Thinktomorrow\Trader\Domain\Model\Order\Payment\PaymentId;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\Shipping;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingCost;
use Thinktomorrow\Trader\Domain\Model\Order\Shipping\ShippingId;
use Thinktomorrow\Trader\Domain\Model\PaymentMethod\PaymentMethodId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountableType;
use Thinktomorrow\Trader\Domain\Model\ShippingProfile\ShippingProfileId;

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
