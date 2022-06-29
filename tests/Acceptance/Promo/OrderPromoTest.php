<?php
declare(strict_types=1);

namespace Tests\Acceptance\Promo;

use Money\Money;
use Tests\Acceptance\Cart\CartContext;
use Thinktomorrow\Trader\Application\Cart\RefreshCart\RefreshCart;
use Thinktomorrow\Trader\Application\Promo\EnterCoupon;
use Thinktomorrow\Trader\Domain\Common\Cash\Cash;

class OrderPromoTest extends CartContext
{
    /** @test */
    public function it_can_apply_promo_by_coupon_code()
    {
        $this->givenThereIsAPromo(['coupon_code' => 'foobar']);
        $this->givenThereIsAProductWhichCostsEur('lightsaber', 5);
        $this->whenIAddTheVariantToTheCart('lightsaber-123', 5);

        $this->promoApplication->enterCoupon(new EnterCoupon($this->getOrder()->orderId->get(), 'foobar'));

        $order = $this->orderRepository->find($this->getOrder()->orderId);
        $this->assertCount(1, $order->getDiscounts());

        $this->assertEquals('foobar', $order->getEnteredCouponCode());

        $cart = $this->cartRepository->findCart($this->getOrder()->orderId);
        $this->assertCount(1, $cart->getDiscounts());

        $this->assertEquals(Money::EUR(2500), $order->getSubTotal()->getIncludingVat());
        $this->assertEquals(Money::EUR(40), $order->getDiscountTotal()->getIncludingVat());
        $this->assertEquals(Money::EUR(2460), $order->getTotal()->getIncludingVat());

        $this->assertEquals('€ 25', $cart->getSubtotalPrice());
        $this->assertEquals('€ 0,40', $cart->getDiscountPrice());
        $this->assertEquals('€ 24,60', $cart->getTotalPrice());
    }

    /** @test */
    public function it_cannot_apply_promo_by_coupon_code_if_code_is_wrong()
    {
        $this->givenThereIsAPromo(['coupon_code' => 'foobar']);
        $this->givenThereIsAProductWhichCostsEur('lightsaber', 5);
        $this->whenIAddTheVariantToTheCart('lightsaber-123', 5);

        $this->promoApplication->enterCoupon(new EnterCoupon($this->getOrder()->orderId->get(), 'fooxxx'));

        $order = $this->orderRepository->find($this->getOrder()->orderId);
        $this->assertCount(0, $order->getDiscounts());

        $this->assertEquals(Money::EUR(2500), $order->getSubTotal()->getIncludingVat());
        $this->assertEquals(Money::EUR(0), $order->getDiscountTotal()->getIncludingVat());
        $this->assertEquals(Money::EUR(2500), $order->getTotal()->getIncludingVat());
    }

    /** @test */
    public function it_cannot_apply_promo_by_coupon_code_if_conditions_fail()
    {
        $this->givenThereIsAPromo(['coupon_code' => 'foobar']);
        $this->givenThereIsAProductWhichCostsEur('lightsaber', 5);
        $this->whenIAddTheVariantToTheCart('lightsaber-123', 4);

        $this->promoApplication->enterCoupon(new EnterCoupon($this->getOrder()->orderId->get(), 'foobar'));

        $order = $this->orderRepository->find($this->getOrder()->orderId);
        $this->assertCount(0, $order->getDiscounts());

        $this->assertEquals(Money::EUR(2000), $order->getSubTotal()->getIncludingVat());
        $this->assertEquals(Money::EUR(0), $order->getDiscountTotal()->getIncludingVat());
        $this->assertEquals(Money::EUR(2000), $order->getTotal()->getIncludingVat());
    }

    /** @test */
    public function it_can_apply_automatic_applicable_promos()
    {
        $this->givenThereIsAPromo([]);
        $this->givenThereIsAProductWhichCostsEur('lightsaber', 5);
        $this->whenIAddTheVariantToTheCart('lightsaber-123', 5);

        // Refresh cart...
        $this->cartApplication->refresh(new RefreshCart($this->getOrder()->orderId->get()));

        $order = $this->orderRepository->find($this->getOrder()->orderId);
        $this->assertCount(1, $order->getDiscounts());

        $this->assertEquals(Money::EUR(2500), $order->getSubTotal()->getIncludingVat());
        $this->assertEquals(Money::EUR(40), $order->getDiscountTotal()->getIncludingVat());
        $this->assertEquals(Money::EUR(2460), $order->getTotal()->getIncludingVat());
    }

    /** @test */
    public function it_can_apply_multiple_combinable_automatic_applicable_promos()
    {
        $this->givenThereIsAPromo(['promo_id' => 'aaa', 'is_combinable' => true], [$this->createDiscount(['discount_id' => 'abc'])]);
        $this->givenThereIsAPromo(['promo_id' => 'bbb', 'is_combinable' => true], [$this->createDiscount(['discount_id' => 'abcd'])]);
        $this->givenThereIsAProductWhichCostsEur('lightsaber', 5);
        $this->whenIAddTheVariantToTheCart('lightsaber-123', 5);

        // Refresh cart...
        $this->cartApplication->refresh(new RefreshCart($this->getOrder()->orderId->get()));

        $order = $this->orderRepository->find($this->getOrder()->orderId);
        $this->assertCount(2, $order->getDiscounts());

        $this->assertEquals(Money::EUR(2500), $order->getSubTotal()->getIncludingVat());
        $this->assertEquals(Money::EUR(80), $order->getDiscountTotal()->getIncludingVat());
        $this->assertEquals(Money::EUR(2420), $order->getTotal()->getIncludingVat());
    }

    /** @test */
    public function it_applies_promo_with_highest_discount()
    {
        $this->givenThereIsAPromo(['promo_id' => 'aaa'], [$this->createDiscount(['data' => json_encode(['amount' => '100'])])]);
        $this->givenThereIsAPromo(['promo_id' => 'bbb'], [$this->createDiscount(['data' => json_encode(['amount' => '200'])])]);
        $this->givenThereIsAProductWhichCostsEur('lightsaber', 5);
        $this->whenIAddTheVariantToTheCart('lightsaber-123', 5);

        // Refresh cart...
        $this->cartApplication->refresh(new RefreshCart($this->getOrder()->orderId->get()));

        $order = $this->orderRepository->find($this->getOrder()->orderId);
        $this->assertCount(1, $order->getDiscounts());

        $this->assertEquals(Money::EUR(2500), $order->getSubTotal()->getIncludingVat());
        $this->assertEquals(Money::EUR(200), $order->getDiscountTotal()->getIncludingVat());
        $this->assertEquals(Money::EUR(2300), $order->getTotal()->getIncludingVat());
    }

    /** @test */
    public function it_can_apply_combinable_automatic_applicable_promos_with_coupon_promo()
    {
        $this->givenThereIsAPromo(['promo_id' => 'aaa', 'coupon_code' => 'foobar', 'is_combinable' => true], [$this->createDiscount(['discount_id' => 'abc'])]);
        $this->givenThereIsAPromo(['promo_id' => 'bbb', 'is_combinable' => true], [$this->createDiscount(['discount_id' => 'abcd'])]);
        $this->givenThereIsAProductWhichCostsEur('lightsaber', 5);
        $this->whenIAddTheVariantToTheCart('lightsaber-123', 5);

        $this->promoApplication->enterCoupon(new EnterCoupon($this->getOrder()->orderId->get(), 'foobar'));

        // Refresh cart
        $order = $this->orderRepository->find($this->getOrder()->orderId);
        $this->cartApplication->refresh(new RefreshCart($order->orderId->get()));

        $order = $this->orderRepository->find($order->orderId);
        $this->assertCount(2, $order->getDiscounts());

        $this->assertEquals(Money::EUR(2500), $order->getSubTotal()->getIncludingVat());
        $this->assertEquals(Money::EUR(80), $order->getDiscountTotal()->getIncludingVat());
        $this->assertEquals(Money::EUR(2420), $order->getTotal()->getIncludingVat());
    }

    /** @test */
    public function it_cannot_go_below_zero()
    {
        $this->givenThereIsAPromo(['promo_id' => 'aaa', 'is_combinable' => true], [$this->createDiscount(['data' => json_encode(['amount' => '100000'])])]);
        $this->givenThereIsAProductWhichCostsEur('lightsaber', 5);
        $this->whenIAddTheVariantToTheCart('lightsaber-123', 5);

        // Refresh cart...
        $this->cartApplication->refresh(new RefreshCart($this->getOrder()->orderId->get()));

        $order = $this->orderRepository->find($this->getOrder()->orderId);
        $this->assertCount(1, $order->getDiscounts());

        $this->assertEquals($order->getSubTotal()->getIncludingVat(), $order->getDiscountTotal()->getIncludingVat());
        $this->assertEquals(Cash::zero(), $order->getTotal()->getIncludingVat());
        $this->assertEquals(Cash::zero(), $order->getTotal()->getExcludingVat());
    }

    /** @test */
    public function it_can_apply_discount_on_entire_order()
    {
    }

    /** @test */
    public function it_can_apply_discount_on_line()
    {
    }

    /** @test */
    public function it_can_apply_discount_on_shipping()
    {
    }
}
