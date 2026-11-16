<?php

declare(strict_types=1);

namespace Tests\Acceptance\Promo;

use Money\Money;
use Tests\Acceptance\TestCase;
use Thinktomorrow\Trader\Application\Cart\RefreshCart\Adjusters\AdjustOrderPricingSnapshot;
use Thinktomorrow\Trader\Application\Promo\ApplicableDiscounts\FixedAmountOrderSubtotalDiscount;
use Thinktomorrow\Trader\Application\Promo\ApplicableDiscounts\PercentageOffOrderSubtotalDiscount;
use Thinktomorrow\Trader\Application\Promo\ApplicableDiscounts\SalePriceProductDiscount;
use Thinktomorrow\Trader\Application\Promo\Coupon\EnterCoupon;
use Thinktomorrow\Trader\Domain\Common\Price\TaxMode;
use Thinktomorrow\Trader\Domain\Model\Order\AppliedDiscount\AppliedDiscountId;
use Thinktomorrow\Trader\Domain\Model\Promo\Conditions\MinimumAmount;
use Thinktomorrow\Trader\Infrastructure\Test\TestContainer;
use Thinktomorrow\Trader\Infrastructure\Test\TestTraderConfig;

class PromoDiscountTest extends TestCase
{
    public function test_product_discounts_within_one_promo_apply_together_without_inter_promo_stacking_permission(): void
    {
        $this->orderContext->createPromo('bundle', [], [
            $this->orderContext->createPromoDiscount('bundle', 'first', 'sale_price'),
            $this->orderContext->createPromoDiscount('bundle', 'second', 'sale_price'),
        ]);
        $this->catalogContext->createProduct();
        $order = $this->orderContext->createEmptyOrder();
        $this->orderContext->addLineToOrder($order, $this->orderContext->createLine());

        $order = $this->orderContext->refreshOrder($order->orderId->get());

        $this->assertSame(['bundle', 'bundle'], array_map(fn ($discount) => $discount->promoId->get(), $order->getLines()[0]->getDiscounts()));
    }

    public function test_product_discounts_on_the_same_line_only_stack_when_both_promos_allow_it(): void
    {
        foreach (['one', 'two'] as $id) {
            $this->orderContext->createPromo($id, [
                'combines_with' => '["line"]',
                'multiple_product_discounts_per_line' => true,
            ], [
                $this->orderContext->createPromoDiscount($id, $id.'-discount', 'sale_price'),
            ]);
        }
        $this->catalogContext->createProduct();
        $order = $this->orderContext->createEmptyOrder();
        $this->orderContext->addLineToOrder($order, $this->orderContext->createLine());

        $order = $this->orderContext->refreshOrder($order->orderId->get());

        $this->assertCount(2, $order->getLines()[0]->getDiscounts());
        $this->assertSame(['one', 'two'], array_map(fn ($discount) => $discount->promoId->get(), $order->getLines()[0]->getDiscounts()));
    }

    public function test_product_discounts_do_not_stack_when_only_one_promo_allows_multiple_per_line(): void
    {
        foreach (['one' => true, 'two' => false] as $id => $stackable) {
            $this->orderContext->createPromo($id, [
                'combines_with' => '["line"]',
                'multiple_product_discounts_per_line' => $stackable,
            ], [
                $this->orderContext->createPromoDiscount($id, $id.'-discount', 'sale_price'),
            ]);
        }
        $this->catalogContext->createProduct();
        $order = $this->orderContext->createEmptyOrder();
        $this->orderContext->addLineToOrder($order, $this->orderContext->createLine());

        $order = $this->orderContext->refreshOrder($order->orderId->get());

        $this->assertCount(1, $order->getLines()[0]->getDiscounts());
    }

    public function test_fifty_non_stackable_product_promos_on_one_line_select_one_discount(): void
    {
        // The acceptance context already registers one system sale-price promo.
        foreach (range(1, 49) as $number) {
            $id = 'product-'.str_pad((string) $number, 2, '0', STR_PAD_LEFT);

            $this->orderContext->createPromo($id, ['combines_with' => '["line"]'], [
                $this->orderContext->createPromoDiscount($id, $id.'-discount', 'sale_price'),
            ]);
        }

        $this->catalogContext->createProduct();
        $order = $this->orderContext->createEmptyOrder();
        $this->orderContext->addLineToOrder($order, $this->orderContext->createLine());

        $order = $this->orderContext->refreshOrder($order->orderId->get());

        $this->assertCount(1, $order->getLines()[0]->getDiscounts());
        $this->assertEquals(Money::EUR(80), $order->getSubtotalExcl());
    }

    public function test_fifty_product_discounts_in_one_promo_apply_together_without_searching_choices(): void
    {
        $discounts = [];

        foreach (range(1, 50) as $number) {
            $discounts[] = $this->orderContext->createPromoDiscount('bundle', 'discount-'.$number, 'sale_price');
        }

        $this->orderContext->createPromo('bundle', [], $discounts);
        $this->catalogContext->createProduct();
        $order = $this->orderContext->createEmptyOrder();
        $this->orderContext->addLineToOrder($order, $this->orderContext->createLine());

        $order = $this->orderContext->refreshOrder($order->orderId->get());

        $this->assertCount(50, $order->getLines()[0]->getDiscounts());
        $this->assertEquals(Money::EUR(0), $order->getSubtotalExcl());
    }

    public function test_multiple_order_percentages_use_the_subtotal_after_product_discounts_without_compounding(): void
    {
        foreach (['ten' => '10', 'twenty' => '20'] as $id => $percentage) {
            $this->orderContext->createPromo($id, ['combines_with' => '["line","order","shipping","payment"]'], [
                $this->orderContext->createPromoDiscount($id, $id.'-discount', 'percentage_off', [
                    'data' => json_encode(['percentage' => $percentage]),
                ]),
            ]);
        }

        $this->catalogContext->createProduct();
        $order = $this->orderContext->createEmptyOrder();
        $this->orderContext->addLineToOrder($order, $this->orderContext->createLine());

        $order = $this->orderContext->refreshOrder($order->orderId->get());

        $this->assertEquals(Money::EUR(80), $order->getSubtotalExcl());
        $this->assertEquals(Money::EUR(24), $order->getDiscountTotalExcl());
        $this->assertCount(2, $order->getDiscounts());
    }

    public function test_many_combinable_order_discounts_are_applied_together(): void
    {
        foreach (range(1, 20) as $number) {
            $id = 'percent-'.$number;
            $this->orderContext->createPromo($id, ['combines_with' => '["line","order","shipping","payment"]'], [
                $this->orderContext->createPromoDiscount($id, $id.'-discount', 'percentage_off', [
                    'data' => json_encode(['percentage' => '1']),
                ]),
            ]);
        }
        $this->catalogContext->createProduct();
        $order = $this->orderContext->createEmptyOrder();
        $this->orderContext->addLineToOrder($order, $this->orderContext->createLine());

        $order = $this->orderContext->refreshOrder($order->orderId->get());

        $this->assertCount(20, $order->getDiscounts());
        $this->assertEquals(Money::EUR(20), $order->getDiscountTotalExcl());
    }

    public function test_a_better_automatic_promo_wins_over_an_entered_coupon(): void
    {
        $this->orderContext->createPromo('automatic', ['combines_with' => '[]'], [
            $this->orderContext->createPromoDiscount('automatic', 'automatic-discount', 'percentage_off', [
                'data' => json_encode(['percentage' => '30']),
            ]),
        ]);
        $this->orderContext->createPromo('coupon', ['coupon_code' => 'SAVE15', 'combines_with' => '[]'], [
            $this->orderContext->createPromoDiscount('coupon', 'coupon-discount', 'percentage_off', [
                'data' => json_encode(['percentage' => '15']),
            ]),
        ]);
        $this->catalogContext->createProduct();
        $order = $this->orderContext->createEmptyOrder();
        $this->orderContext->addLineToOrder($order, $this->orderContext->createLine());
        $order = $this->orderContext->refreshOrder($order->orderId->get());

        $this->orderContext->apps()->couponPromoApplication()->enterCoupon(new EnterCoupon($order->orderId->get(), 'SAVE15'));
        $order = $this->orderContext->findOrder($order->orderId);

        $this->assertSame('SAVE15', $order->getEnteredCouponCode());
        $this->assertSame('automatic', $order->getDiscounts()[0]->promoId->get());
    }

    public function test_an_exclusive_order_discount_can_beat_a_product_discount_on_the_cart_total(): void
    {
        $this->orderContext->createPromo('order-promo', [], [
            $this->orderContext->createPromoDiscount('order-promo', 'order-discount', 'percentage_off', [
                'data' => json_encode(['percentage' => '30']),
            ]),
        ]);
        $this->catalogContext->createProduct();
        $order = $this->orderContext->createEmptyOrder();
        $this->orderContext->addLineToOrder($order, $this->orderContext->createLine());

        $order = $this->orderContext->refreshOrder($order->orderId->get());

        $this->assertCount(0, $order->getLines()[0]->getDiscounts());
        $this->assertSame('order-promo', $order->getDiscounts()[0]->promoId->get());
        $this->assertEquals(Money::EUR(30), $order->getDiscountTotalExcl());
    }

    public function test_a_multi_class_promo_wins_as_one_product_and_order_offer(): void
    {
        $this->orderContext->createPromo('bundle', [], [
            $this->orderContext->createPromoDiscount('bundle', 'bundle-product', 'sale_price'),
            $this->orderContext->createPromoDiscount('bundle', 'bundle-order', 'percentage_off', [
                'data' => json_encode(['percentage' => '30']),
            ]),
        ]);
        $this->orderContext->createPromo('order-only', [], [
            $this->orderContext->createPromoDiscount('order-only', 'order-only-discount', 'percentage_off', [
                'data' => json_encode(['percentage' => '35']),
            ]),
        ]);
        $this->catalogContext->createProduct();
        $order = $this->orderContext->createEmptyOrder();
        $this->orderContext->addLineToOrder($order, $this->orderContext->createLine());

        $order = $this->orderContext->refreshOrder($order->orderId->get());

        $this->assertSame('bundle', $order->getLines()[0]->getDiscounts()[0]->promoId->get());
        $this->assertSame('bundle', $order->getDiscounts()[0]->promoId->get());
        $this->assertEquals(Money::EUR(56), $order->getTotalExcl());
    }

    public function test_order_discounts_require_mutual_permission_to_combine(): void
    {
        $this->orderContext->createPromo('allowing', ['combines_with' => '["order"]'], [
            $this->orderContext->createPromoDiscount('allowing', 'allowing-discount', 'percentage_off', [
                'data' => json_encode(['percentage' => '15']),
            ]),
        ]);
        $this->orderContext->createPromo('exclusive', [], [
            $this->orderContext->createPromoDiscount('exclusive', 'exclusive-discount', 'percentage_off', [
                'data' => json_encode(['percentage' => '30']),
            ]),
        ]);
        $this->catalogContext->createProduct();
        $order = $this->orderContext->createEmptyOrder();
        $this->orderContext->addLineToOrder($order, $this->orderContext->createLine());

        $order = $this->orderContext->refreshOrder($order->orderId->get());

        $this->assertCount(1, $order->getDiscounts());
        $this->assertSame('exclusive', $order->getDiscounts()[0]->promoId->get());
    }

    public function test_fixed_including_vat_discount_remains_authoritative(): void
    {
        $this->orderContext->createPromo('promo-aaa', ['coupon_code' => null, 'combines_with' => '["line"]'], [
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
        $discount = FixedAmountOrderSubtotalDiscount::fromMappedData([
            'discount_id' => 'discount',
            'data' => json_encode(['amount' => '5', 'tax_mode' => 'inclusive']),
        ], [
            'promo_id' => 'promo',
            'coupon_code' => null,
            'data' => json_encode([]),
        ], [], $this->catalogContext->apps()->vatAllocator());

        $discount->apply($order, $order, AppliedDiscountId::fromString('applied-discount'));
        (new TestContainer)->get(AdjustOrderPricingSnapshot::class)->adjust($order);

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
            'combines_with' => '["line"]',
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
            'combines_with' => '["line"]',
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
            'combines_with' => '["line"]',
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
            'combines_with' => '["line","order","shipping","payment"]',
        ], [
            $this->orderContext->createPromoDiscount(),
        ]);

        $this->orderContext->createPromo('promo-bbb', [
            'coupon_code' => null,
            'combines_with' => '["line","order","shipping","payment"]',
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
            'combines_with' => '["line"]',
        ], [
            $this->orderContext->createPromoDiscount('promo-aaa', 'promo-discount-aaa', 'percentage_off', ['data' => json_encode(['percentage' => '10'])]),
        ]);

        $this->orderContext->createPromo('promo-bbb', [
            'coupon_code' => null,
            'combines_with' => '["line"]',
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
            'combines_with' => '["line","order","shipping","payment"]',
        ], [
            $this->orderContext->createPromoDiscount(),
        ]);

        $this->orderContext->createPromo('promo-bbb', [
            'coupon_code' => null,
            'combines_with' => '["line","order","shipping","payment"]',
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
            'combines_with' => '["line"]',
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

    public function test_sale_price_discount_uses_its_calculation_tax_mode(): void
    {
        $discount = SalePriceProductDiscount::fromMappedData(
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

    public function test_could_apply_only_rules_out_discounts_that_cannot_affect_the_order(): void
    {
        $promoData = ['promo_id' => 'promo-aaa', 'coupon_code' => null, 'data' => '[]'];
        $vatAllocator = $this->catalogContext->apps()->vatAllocator();
        $salePrice = SalePriceProductDiscount::fromMappedData(
            ['discount_id' => 'sale-price'], $promoData, [], $vatAllocator,
        );
        $orderDiscount = PercentageOffOrderSubtotalDiscount::fromMappedData(
            ['discount_id' => 'order-discount', 'data' => '{"percentage":"10"}'], $promoData, [], $vatAllocator,
        );
        $order = $this->orderContext->createEmptyOrder();

        $this->assertFalse($salePrice->couldApply($order));
        $this->assertTrue($orderDiscount->couldApply($order));

        $this->orderContext->addLineToOrder($order, $this->orderContext->createLine('order-aaa', 'line-aaa', [
            'data' => '{}',
        ]));

        $this->assertFalse($salePrice->couldApply($order));
        $this->assertTrue($orderDiscount->couldApply($order));
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
        $discount = FixedAmountOrderSubtotalDiscount::fromMappedData([
            'discount_id' => 'discount',
            'data' => json_encode(['amount' => '999', 'tax_mode' => TaxMode::Inclusive->value]),
        ], [
            'promo_id' => 'promo',
            'coupon_code' => null,
            'data' => json_encode([]),
        ], [], $this->catalogContext->apps()->vatAllocator());

        $discount->apply($order, $order, AppliedDiscountId::fromString('applied-discount'));
        (new TestContainer)->get(AdjustOrderPricingSnapshot::class)->adjust($order);

        $this->assertEquals(Money::EUR(227), $order->getDiscountTotalIncl());
        $this->assertEquals(Money::EUR(0), $order->getTotalExcl());
        $this->assertEquals(Money::EUR(0), $order->getTotalVat());
        $this->assertEquals(Money::EUR(0), $order->getTotalIncl());
    }
}
