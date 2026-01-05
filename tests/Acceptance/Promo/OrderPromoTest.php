<?php
declare(strict_types=1);

namespace Tests\Acceptance\Promo;

use Money\Money;
use Tests\Acceptance\TestCase;
use Thinktomorrow\Trader\Application\Promo\Coupon\EnterCoupon;
use Thinktomorrow\Trader\Domain\Model\Promo\Conditions\MinimumAmount;

class OrderPromoTest extends TestCase
{
    public function test_it_can_apply_promo_by_coupon_code()
    {
        $this->orderContext->createPromo('promo-aaa', [
            'coupon_code' => 'foobar',
        ], [
            $this->orderContext->createPromoDiscount(),
        ]);

        // Required for refresh order to work properly (line will be deleted is no related variant is found)
        $this->catalogContext->createProduct();
        $order = $this->orderContext->createEmptyOrder();
        $line = $this->orderContext->createLine();
        $this->orderContext->addLineToOrder($order, $line);
        $order = $this->orderContext->refreshOrder($order->orderId->get());

        $this->orderContext->apps()->couponPromoApplication()->enterCoupon(new EnterCoupon($order->orderId->get(), 'foobar'));

        $this->orderContext->refreshOrder($order->orderId->get());

        $order = $this->orderContext->findOrder($order->orderId);
        $this->assertEquals('foobar', $order->getEnteredCouponCode());
        $this->assertCount(1, $order->getDiscounts());

        $cart = $this->orderContext->findCart($order->orderId);
        $this->assertCount(1, $cart->getDiscounts());

        $this->assertEquals(Money::EUR(80), $order->getSubTotalExcl());
        $this->assertEquals(Money::EUR(12), $order->getDiscountTotalExcl());
        $this->assertEquals(Money::EUR(68), $order->getTotalExcl());
    }

    public function test_it_cannot_apply_promo_by_coupon_code_if_code_is_wrong()
    {
        $this->orderContext->createPromo('promo-aaa', [
            'coupon_code' => 'foobar',
        ], [
            $this->orderContext->createPromoDiscount(),
        ]);

        // Required for refresh order to work properly (line will be deleted is no related variant is found)
        $this->catalogContext->createProduct();
        $order = $this->orderContext->createEmptyOrder();
        $line = $this->orderContext->createLine();
        $this->orderContext->addLineToOrder($order, $line);
        $order = $this->orderContext->refreshOrder($order->orderId->get());

        $this->orderContext->apps()->couponPromoApplication()->enterCoupon(new EnterCoupon($order->orderId->get(), 'wrong'));

        $this->orderContext->refreshOrder($order->orderId->get());

        $order = $this->orderContext->findOrder($order->orderId);

        $this->assertNull($order->getEnteredCouponCode());
        $this->assertCount(0, $order->getDiscounts());
        $this->assertEquals(Money::EUR(0), $order->getDiscountTotalExcl());
    }

    public function test_it_cannot_apply_promo_by_coupon_code_if_conditions_fail()
    {
        $promo = $this->orderContext->createPromo('promo-aaa', [
            'coupon_code' => 'foobar',
        ], [
            $this->orderContext->createPromoDiscount('promo-aaa', 'promo-discount-aaa', 'percentage_off', [], [
                MinimumAmount::fromMappedData(['data' => json_encode(['amount' => '9000'])], []),
            ]),
        ]);
        // Required for refresh order to work properly (line will be deleted is no related variant is found)
        $this->catalogContext->createProduct();
        $order = $this->orderContext->createEmptyOrder();
        $line = $this->orderContext->createLine();
        $this->orderContext->addLineToOrder($order, $line);
        $order = $this->orderContext->refreshOrder($order->orderId->get());

        $this->orderContext->apps()->couponPromoApplication()->enterCoupon(new EnterCoupon($order->orderId->get(), 'foobar'));

        $this->orderContext->refreshOrder($order->orderId->get());

        $order = $this->orderContext->findOrder($order->orderId);
        $this->assertCount(0, $order->getDiscounts());

        $this->assertEquals(Money::EUR(0), $order->getDiscountTotalExcl());
    }

    public function test_it_can_apply_automatic_applicable_promos()
    {
        $this->orderContext->createPromo('promo-aaa', [
            'coupon_code' => null,
        ], [
            $this->orderContext->createPromoDiscount(),
        ]);

        // Required for refresh order to work properly (line will be deleted is no related variant is found)
        $this->catalogContext->createProduct();
        $order = $this->orderContext->createEmptyOrder();
        $line = $this->orderContext->createLine();
        $this->orderContext->addLineToOrder($order, $line);

        $order = $this->orderContext->refreshOrder($order->orderId->get());
        $order = $this->orderContext->findOrder($order->orderId);

        $this->assertCount(1, $order->getDiscounts());
        $this->assertEquals(Money::EUR(12), $order->getDiscountTotalExcl());
    }

    public function test_it_can_apply_multiple_combinable_automatic_applicable_promos()
    {
        $this->orderContext->createPromo('promo-aaa', [
            'coupon_code' => null,
            'is_combinable' => true,
        ], [
            $this->orderContext->createPromoDiscount(),
        ]);

        $this->orderContext->createPromo('promo-bbb', [
            'coupon_code' => null,
            'is_combinable' => true,
        ], [
            $this->orderContext->createPromoDiscount('promo-bbb', 'promo-discount-bbb'),
        ]);

        // Required for refresh order to work properly (line will be deleted is no related variant is found)
        $this->catalogContext->createProduct();
        $order = $this->orderContext->createEmptyOrder();
        $line = $this->orderContext->createLine();
        $this->orderContext->addLineToOrder($order, $line);

        $order = $this->orderContext->refreshOrder($order->orderId->get());
        $order = $this->orderContext->findOrder($order->orderId);

        $this->assertCount(2, $order->getDiscounts());
        $this->assertEquals(Money::EUR(24), $order->getDiscountTotalExcl());
    }

    public function test_it_applies_promo_with_highest_discount()
    {
        $this->orderContext->createPromo('promo-aaa', [
            'coupon_code' => null,
            'is_combinable' => false,
        ], [
            $this->orderContext->createPromoDiscount('promo-aaa', 'promo-discount-aaa', 'percentage_off', ['data' => json_encode(['percentage' => '10'])]),
        ]);

        $this->orderContext->createPromo('promo-bbb', [
            'coupon_code' => null,
            'is_combinable' => false,
        ], [
            $this->orderContext->createPromoDiscount('promo-bbb', 'promo-discount-bbb', 'percentage_off', ['data' => json_encode(['percentage' => '15'])]),
        ]);

        // Required for refresh order to work properly (line will be deleted is no related variant is found)
        $this->catalogContext->createProduct();
        $order = $this->orderContext->createEmptyOrder();
        $line = $this->orderContext->createLine();
        $this->orderContext->addLineToOrder($order, $line);

        $order = $this->orderContext->refreshOrder($order->orderId->get());
        $order = $this->orderContext->findOrder($order->orderId);

        $this->assertCount(1, $order->getDiscounts());
        $this->assertEquals(Money::EUR(12), $order->getDiscountTotalExcl());
    }

    public function test_it_can_apply_combinable_automatic_applicable_promos_with_coupon_promo()
    {
        $this->orderContext->createPromo('promo-aaa', [
            'coupon_code' => 'foobar',
            'is_combinable' => true,
        ], [
            $this->orderContext->createPromoDiscount(),
        ]);

        $this->orderContext->createPromo('promo-bbb', [
            'coupon_code' => null,
            'is_combinable' => true,
        ], [
            $this->orderContext->createPromoDiscount('promo-bbb', 'promo-discount-bbb'),
        ]);

        // Required for refresh order to work properly (line will be deleted is no related variant is found)
        $this->catalogContext->createProduct();
        $order = $this->orderContext->createEmptyOrder();
        $line = $this->orderContext->createLine();
        $this->orderContext->addLineToOrder($order, $line);

        $order = $this->orderContext->refreshOrder($order->orderId->get());
        $order = $this->orderContext->findOrder($order->orderId);

        $this->orderContext->apps()->couponPromoApplication()->enterCoupon(new EnterCoupon($order->orderId->get(), 'foobar'));

        $this->orderContext->refreshOrder($order->orderId->get());
        $order = $this->orderContext->findOrder($order->orderId);

        $this->assertCount(2, $order->getDiscounts());
        $this->assertEquals(Money::EUR(24), $order->getDiscountTotalExcl());
    }

    public function test_it_cannot_go_below_zero()
    {
        $this->orderContext->createPromo('promo-aaa', [
            'coupon_code' => null,
        ], [
            $this->orderContext->createPromoDiscount('promo-aaa', 'promo-discount-aaa', 'percentage_off', ['data' => json_encode(['percentage' => '110'])]),
        ]);

        // Required for refresh order to work properly (line will be deleted is no related variant is found)
        $this->catalogContext->createProduct();
        $order = $this->orderContext->createEmptyOrder();
        $line = $this->orderContext->createLine();
        $this->orderContext->addLineToOrder($order, $line);

        $order = $this->orderContext->refreshOrder($order->orderId->get());
        $order = $this->orderContext->findOrder($order->orderId);

        $this->assertCount(1, $order->getDiscounts());
        $this->assertEquals(Money::EUR(80), $order->getSubTotalExcl());
        $this->assertEquals(Money::EUR(80), $order->getDiscountTotalExcl());
        $this->assertEquals(Money::EUR(0), $order->getTotalExcl());
    }
}
