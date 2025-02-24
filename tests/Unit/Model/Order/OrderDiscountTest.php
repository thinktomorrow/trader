<?php
declare(strict_types=1);

namespace Tests\Unit\Model\Order;

use Money\Money;
use Tests\Unit\TestCase;
use Thinktomorrow\Trader\Domain\Common\Cash\Percentage;
use Thinktomorrow\Trader\Domain\Common\Vat\VatTotals;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\Discount;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountableId;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountableType;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountId;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountTotal;
use Thinktomorrow\Trader\Domain\Model\Order\OrderId;
use Thinktomorrow\Trader\Domain\Model\Order\OrderTotal;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoId;

class OrderDiscountTest extends TestCase
{
    public function test_it_can_create_discount()
    {
        $discount = Discount::create(
            $orderId = OrderId::fromString('aaa'),
            $discountId = DiscountId::fromString('bbb'),
            $discountableType = DiscountableType::line,
            $discountableId = DiscountableId::fromString('ccc'),
            $promoId = PromoId::fromString('ddd'),
            $promoDiscountId = \Thinktomorrow\Trader\Domain\Model\Promo\DiscountId::fromString('eee'),
            $discountTotal = DiscountTotal::fromDefault(Money::EUR(50)),
            ['foo' => 'bar']
        );

        $this->assertEquals($discountTotal, $discount->getTotal());
        $this->assertEquals(Percentage::fromString('50.00'), $discount->getPercentage(OrderTotal::make(Money::EUR(100), VatTotals::zero(), false)));
        $this->assertEquals([
            'order_id' => $orderId->get(),
            'discount_id' => $discountId->get(),
            'discountable_type' => $discountableType->value,
            'discountable_id' => $discountableId->get(),
            'promo_id' => $promoId->get(),
            'promo_discount_id' => $promoDiscountId->get(),
            'total' => $discountTotal->getIncludingVat()->getAmount(),
            'includes_vat' => true,
            'tax_rate' => '21',
            'data' => json_encode(['foo' => 'bar', 'promo_id' => $promoId->get(), 'promo_discount_id' => $promoDiscountId->get()]),
        ], $discount->getMappedData());
    }

    public function test_it_can_add_a_discount()
    {
        $order = $this->createDefaultOrder();

        $order->addDiscount($this->createOrderDiscount(['promo_discount_id' => 'qqq', 'discount_id' => 'defgh'], $order->getMappedData()));

        $this->assertCount(2, $order->getChildEntities()[Discount::class]);
    }

    public function test_it_can_add_a_discount_to_payment()
    {
        $order = $this->createDefaultOrder();
        $order->getPayments()[0]->addDiscount($this->createOrderPaymentDiscount(['promo_discount_id' => 'qqq', 'discount_id' => 'defgh'], $order->getMappedData()));

        $this->assertCount(1, $order->getPayments()[0]->getDiscounts());
    }

    public function test_it_cannot_add_same_applied_discount_twice()
    {
        $this->expectException(\InvalidArgumentException::class);

        $order = $this->createDefaultOrder();

        $order->addDiscount($this->createOrderDiscount(['promo_discount_id' => 'qqq'], $order->getMappedData())); // discount_id is the same as the default which implies  a duplicate

        $this->assertCount(1, $order->getChildEntities()[Discount::class]);
    }

    public function test_it_cannot_add_same_promo_discount_twice()
    {
        $this->expectException(\InvalidArgumentException::class);

        $order = $this->createDefaultOrder();

        $order->addDiscount($this->createOrderDiscount(['discount_id' => 'defgh'], $order->getMappedData())); // promo_discount_id is same as already set on createdOrder

        $this->assertCount(1, $order->getChildEntities()[Discount::class]);
    }

    public function test_it_cannot_add_discount_with_discountable_type_mismatch()
    {
        $this->expectException(\InvalidArgumentException::class);

        $order = $this->createDefaultOrder();

        $order->addDiscount($this->createOrderDiscount(['promo_discount_id' => 'qqq', 'discount_id' => 'defgh', 'discountable_type' => DiscountableType::line->value], $order->getMappedData()));

        $this->assertCount(1, $order->getChildEntities()[Discount::class]);
    }

    public function test_it_cannot_add_discount_with_discountable_id_mismatch()
    {
        $this->expectException(\InvalidArgumentException::class);

        $order = $this->createDefaultOrder();

        $order->addDiscount($this->createOrderDiscount(['promo_discount_id' => 'qqq', 'discount_id' => 'defgh', 'discountable_id' => 'foobar'], $order->getMappedData()));

        $this->assertCount(1, $order->getChildEntities()[Discount::class]);
    }

    public function test_it_can_delete_a_discount()
    {
        $order = $this->createDefaultOrder();

        $this->assertCount(1, $order->getChildEntities()[Discount::class]);

        $order->deleteDiscount(
            DiscountId::fromString('order-discount-abc'),
        );

        $this->assertCount(0, $order->getChildEntities()[Discount::class]);
    }
}
