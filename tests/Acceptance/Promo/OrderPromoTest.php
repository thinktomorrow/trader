<?php

declare(strict_types=1);

namespace Tests\Acceptance\Promo;

use Money\Money;
use Tests\Acceptance\TestCase;
use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters\AdjustOrderVatSnapshot;
use Thinktomorrow\Trader\Application\Promo\ApplyPromoToOrder;
use Thinktomorrow\Trader\Application\Promo\Coupon\EnterCoupon;
use Thinktomorrow\Trader\Application\Promo\LinePromo\Discounts\SalePriceLineDiscount;
use Thinktomorrow\Trader\Application\Promo\LinePromo\LineDiscount;
use Thinktomorrow\Trader\Application\Promo\OrderPromo\Discounts\FixedAmountOrderDiscount;
use Thinktomorrow\Trader\Application\Promo\OrderPromo\OrderDiscount;
use Thinktomorrow\Trader\Application\Promo\PromoApplicationScope;
use Thinktomorrow\Trader\Domain\Common\Price\TaxMode;
use Thinktomorrow\Trader\Domain\Model\Order\Discount\DiscountId;
use Thinktomorrow\Trader\Domain\Model\Promo\Conditions\MinimumAmount;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;
use Thinktomorrow\Trader\Infrastructure\Test\TestTraderConfig;

class OrderPromoTest extends TestCase
{
    public function test_fixed_including_vat_discount_remains_authoritative(): void
    {
        $this->orderContext->createPromo('promo-aaa', ['coupon_code' => null], [
            $this->orderContext->createPromoDiscount('promo-aaa', 'promo-discount-aaa', 'fixed_amount', [
                'data' => json_encode([
                    'amount' => '12',
                    'tax_mode' => 'inclusive',
                ]),
            ]),
        ]);
        $this->catalogContext->createProduct();
        $order = $this->orderContext->createEmptyOrder();
        $this->orderContext->addLineToOrder($order, $this->orderContext->createLine());

        $order = $this->orderContext->refreshOrder($order->orderId->get());

        $this->assertCount(1, $order->getDiscounts());
        $this->assertEquals(Money::EUR(10), $order->getDiscountTotalExcl());
        $this->assertEquals(Money::EUR(12), $order->getDiscountTotalIncl());
        $this->assertSame(TaxMode::Inclusive, $order->getDiscounts()[0]->getDiscountPrice()->getTaxMode());
    }

    public function test_full_including_vat_discount_exactly_reverses_rounded_order_allocation(): void
    {
        $order = $this->orderContext->createEmptyOrder();
        $this->orderContext->addLineToOrder($order, $this->orderContext->createLine('order-aaa', 'line-a', [
            'unit_price_excl' => '2',
            'tax_rate' => '21',
            'includes_vat' => false,
        ]));
        $this->orderContext->addLineToOrder($order, $this->orderContext->createLine('order-aaa', 'line-b', [
            'unit_price_excl' => '2',
            'tax_rate' => '21',
            'includes_vat' => false,
        ]));
        $this->orderContext->addLineToOrder($order, $this->orderContext->createLine('order-aaa', 'line-c', [
            'unit_price_excl' => '1',
            'tax_rate' => '6',
            'includes_vat' => false,
        ]));
        $discount = FixedAmountOrderDiscount::fromMappedData([
            'discount_id' => 'discount',
            'data' => json_encode(['amount' => '5', 'tax_mode' => 'inclusive']),
        ], [
            'promo_id' => 'promo',
            'coupon_code' => null,
            'data' => json_encode([]),
        ], [], $this->catalogContext->apps()->vatAllocator());

        $discount->apply($order, $order, DiscountId::fromString('applied-discount'));
        (new TestContainer)->get(AdjustOrderVatSnapshot::class)->adjust($order);

        $this->assertEquals(Money::EUR(5), $order->getDiscountTotalExcl());
        $this->assertEquals(Money::EUR(5), $order->getDiscountTotalIncl());
        $this->assertEquals(Money::EUR(0), $order->getTotalExcl());
        $this->assertEquals(Money::EUR(0), $order->getTotalVat());
        $this->assertEquals(Money::EUR(0), $order->getTotalIncl());
    }

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

        //        $this->orderContext->refreshOrder($order->orderId->get());

        $cart = $this->orderContext->findCart($order->orderId);
        $this->assertCount(1, $cart->getDiscounts());

        $this->assertEquals(Money::EUR(80), $order->getSubtotalExcl());
        $this->assertEquals(Money::EUR(12), $order->getDiscountTotalExcl());
        $this->assertEquals(Money::EUR(68), $order->getTotalExcl());
    }

    public function test_it_can_combine_coupon_promo_with_saleprice_line_promo()
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

        $this->assertCount(1, $order->getLines()[0]->getDiscounts());
        $this->assertCount(1, $order->getDiscounts());

        $this->orderContext->refreshOrder($order->orderId->get());

        $cart = $this->orderContext->findCart($order->orderId);
        $this->assertCount(1, $cart->getDiscounts());

        $this->assertEquals(Money::EUR(80), $order->getSubtotalExcl());
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
        $this->assertEquals(Money::EUR(80), $order->getSubtotalExcl());
        $this->assertEquals(Money::EUR(80), $order->getDiscountTotalExcl());
        $this->assertEquals(Money::EUR(0), $order->getTotalExcl());
    }

    public function test_promo_scopes_apply_lines_before_services_and_order_without_duplicates(): void
    {
        $order = $this->orderContext->createDefaultOrder();
        $lineDiscount = $this->createMock(LineDiscount::class);
        $lineDiscount->expects($this->exactly(count($order->getLines())))
            ->method('setCalculationTaxMode')
            ->with(TaxMode::Inclusive);
        $lineDiscount->expects($this->exactly(count($order->getLines())))->method('isApplicable')->willReturn(true);
        $lineDiscount->expects($this->exactly(count($order->getLines())))->method('apply');

        $orderDiscount = $this->createMock(OrderDiscount::class);
        $orderDiscount->expects($this->exactly(count($order->getShippings()) + count($order->getPayments()) + 1))->method('isApplicable')->willReturnCallback(
            fn ($currentOrder, $discountable): bool => $discountable === $order->getPayments()[0],
        );
        $orderDiscount->expects($this->once())->method('apply');

        $subject = new ApplyPromoToOrder($this->orderContext->repos()->orderRepository(), new TestTraderConfig);
        $subject->apply($order, [$lineDiscount, $orderDiscount], 'PROMO', PromoApplicationScope::Lines);
        $subject->apply($order, [$lineDiscount, $orderDiscount], 'PROMO', PromoApplicationScope::ServicesAndOrder);

        $this->assertSame('PROMO', $order->getEnteredCouponCode());
    }

    public function test_sale_price_discount_uses_its_calculation_tax_mode(): void
    {
        $discount = SalePriceLineDiscount::fromMappedData(
            ['discount_id' => 'discount-aaa'],
            ['promo_id' => 'promo-aaa', 'coupon_code' => null, 'data' => '[]'],
            [],
            $this->catalogContext->apps()->vatAllocator(),
        );
        $order = $this->orderContext->createEmptyOrder();
        $line = $this->orderContext->createLine('order-aaa', 'line-aaa', [
            'unit_price_excl' => '826',
            'unit_price_incl' => '1000',
            'includes_vat' => true,
            'data' => json_encode([
                'unit_price_excl' => '826',
                'unit_price_incl' => '1000',
                'sale_price_excl' => '744',
                'sale_price_incl' => '900',
            ]),
        ]);

        $discount->setCalculationTaxMode(TaxMode::Inclusive);
        $includingVatDiscount = $discount->getDiscountPrice($order, $line);

        $this->assertSame(TaxMode::Inclusive, $includingVatDiscount->getTaxMode());
        $this->assertEquals(Money::EUR(100), $includingVatDiscount->getAuthoritativeAmount());

        $discount->setCalculationTaxMode(TaxMode::Exclusive);
        $excludingVatDiscount = $discount->getDiscountPrice($order, $line);

        $this->assertSame(TaxMode::Exclusive, $excludingVatDiscount->getTaxMode());
        $this->assertEquals(Money::EUR(82), $excludingVatDiscount->getAuthoritativeAmount());
    }

    public function test_item_discount_config_is_exposed_as_tax_mode(): void
    {
        $this->assertSame(TaxMode::Inclusive, (new TestTraderConfig)->getItemDiscountTaxMode());
        $this->assertSame(
            TaxMode::Exclusive,
            (new TestTraderConfig(['calculate_item_discounts_excluding_vat' => true]))->getItemDiscountTaxMode(),
        );
    }

    public function test_fixed_including_vat_discount_above_available_mixed_order_caps_at_exact_zero(): void
    {
        $order = $this->orderContext->createEmptyOrder();
        $this->orderContext->addLineToOrder($order, $this->orderContext->createLine('order-aaa', 'line-21', [
            'unit_price_excl' => '100',
            'unit_price_incl' => '121',
            'total_excl' => '100',
            'total_incl' => '121',
            'tax_rate' => '21',
            'includes_vat' => true,
        ]));
        $this->orderContext->addLineToOrder($order, $this->orderContext->createLine('order-aaa', 'line-6', [
            'unit_price_excl' => '100',
            'unit_price_incl' => '106',
            'total_excl' => '100',
            'total_incl' => '106',
            'tax_rate' => '6',
            'includes_vat' => true,
        ]));
        $discount = FixedAmountOrderDiscount::fromMappedData([
            'discount_id' => 'discount',
            'data' => json_encode(['amount' => '999', 'tax_mode' => TaxMode::Inclusive->value]),
        ], [
            'promo_id' => 'promo',
            'coupon_code' => null,
            'data' => json_encode([]),
        ], [], $this->catalogContext->apps()->vatAllocator());

        $discount->apply($order, $order, DiscountId::fromString('applied-discount'));
        (new TestContainer)->get(AdjustOrderVatSnapshot::class)->adjust($order);

        $this->assertEquals(Money::EUR(227), $order->getDiscountTotalIncl());
        $this->assertEquals(Money::EUR(0), $order->getTotalExcl());
        $this->assertEquals(Money::EUR(0), $order->getTotalVat());
        $this->assertEquals(Money::EUR(0), $order->getTotalIncl());
    }
}
