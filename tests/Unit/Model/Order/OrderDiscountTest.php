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
        $order = $this->orderContext->createDefaultOrder();
        $discount = $this->orderContext->createDiscount('order-aaa', 'discount-bbb', ['promo_discount_id' => 'promo-discount-bbb']);

        $order->addDiscount($discount);

        $this->assertCount(2, $order->getChildEntities()[Discount::class]);
    }

    public function test_it_can_add_a_discount_to_payment()
    {
        $order = $this->orderContext->createDefaultOrder();
        $discount = $this->orderContext->createDiscount('order-aaa', 'discount-bbb', [
            'discountable_type' => DiscountableType::payment->value,
            'discountable_id' => 'order-aaa:payment-aaa',
        ]);

        $order->getPayments()[0]->addDiscount($discount);

        $this->assertCount(1, $order->getPayments()[0]->getDiscounts());
    }

    public function test_it_cannot_add_same_applied_discount_twice()
    {
        $this->expectException(\InvalidArgumentException::class);

        $order = $this->orderContext->createDefaultOrder();
        $discount = $this->orderContext->createDiscount();

        $order->addDiscount($discount);

        $this->assertCount(1, $order->getChildEntities()[Discount::class]);
    }

    public function test_it_cannot_add_same_promo_discount_twice()
    {
        $this->expectException(\InvalidArgumentException::class);

        $order = $this->orderContext->createDefaultOrder();
        $discount = $this->orderContext->createDiscount('order-aaa', 'discount-bbb');

        $order->addDiscount($discount);

        $this->assertCount(1, $order->getChildEntities()[Discount::class]);
    }

    public function test_it_cannot_add_discount_with_discountable_type_mismatch()
    {
        $this->expectException(\InvalidArgumentException::class);

        $order = $this->orderContext->createDefaultOrder();
        $discount = $this->orderContext->createDiscount('order-aaa', 'discount-bbb', [
            'discountable_type' => DiscountableType::line->value,
        ]);

        $order->addDiscount($discount);

        $this->assertCount(1, $order->getChildEntities()[Discount::class]);
    }

    public function test_it_cannot_add_discount_with_discountable_id_mismatch()
    {
        $this->expectException(\InvalidArgumentException::class);

        $order = $this->orderContext->createDefaultOrder();
        $discount = $this->orderContext->createDiscount('order-aaa', 'discount-bbb', [
            'discountable_type' => DiscountableType::line->value,
            'discountable_id' => 'some-other-id',
        ]);

        $order->addDiscount($discount);

        $this->assertCount(1, $order->getChildEntities()[Discount::class]);
    }

    public function test_it_can_delete_a_discount()
    {
        $order = $this->orderContext->createDefaultOrder();

        $this->assertCount(1, $order->getChildEntities()[Discount::class]);

        $order->deleteDiscount(
            DiscountId::fromString('order-aaa:discount-aaa'),
        );

        $this->assertCount(0, $order->getChildEntities()[Discount::class]);
    }
}
