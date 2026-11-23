<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Application\Cart\CartApplication;
use Thinktomorrow\Trader\Application\Cart\Line\AddLine;
use Thinktomorrow\Trader\Application\Cart\RefreshCart\RefreshCart;
use Thinktomorrow\Trader\Application\Promo\Coupon\CouponPromoApplication;
use Thinktomorrow\Trader\Application\Promo\Coupon\EnterCoupon;
use Thinktomorrow\Trader\Domain\Common\Price\TaxMode;
use Thinktomorrow\Trader\Domain\Model\Order\OrderRepository;
use Thinktomorrow\Trader\Domain\Model\Promo\CombinationPolicy;
use Thinktomorrow\Trader\Domain\Model\Promo\Conditions\MinimumLinesQuantity;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\DiscountableType;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\FixedAmountDiscount;
use Thinktomorrow\Trader\Domain\Model\Promo\Exceptions\CouldNotFindPromo;
use Thinktomorrow\Trader\Domain\Model\Promo\Promo;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoId;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoState;
use Thinktomorrow\Trader\Testing\Order\OrderContext;

final class PromoRepositoryTest extends TestCase
{
    public function test_it_can_save_and_find_a_promo()
    {
        foreach (OrderContext::drivers() as $orderContext) {
            $repository = $orderContext->repos()->promoRepository();

            $promo = $orderContext->dontPersist()->createPromo();

            $repository->save($promo);

            $this->assertEquals($promo, $repository->find($promo->promoId));
        }
    }

    public function test_it_preserves_fixed_discount_tax_mode(): void
    {
        foreach (OrderContext::drivers() as $orderContext) {
            $repository = $orderContext->repos()->promoRepository();
            $promo = $orderContext->dontPersist()->createPromo('inclusive-promo', [], [
                $orderContext->createPromoDiscount('inclusive-promo', 'inclusive-discount', 'fixed_amount', [
                    'data' => json_encode([
                        'amount' => '700',
                        'tax_mode' => TaxMode::Inclusive->value,
                    ]),
                ]),
            ]);

            $repository->save($promo);
            $savedDiscount = $repository->find($promo->promoId)->getDiscounts()[0];

            $this->assertInstanceOf(FixedAmountDiscount::class, $savedDiscount);
            $this->assertSame(TaxMode::Inclusive, $savedDiscount->getTaxMode());
        }
    }

    public function test_it_persists_a_targeted_discount_update_and_delete_with_conditions(): void
    {
        foreach (OrderContext::drivers() as $orderContext) {
            $repository = $orderContext->repos()->promoRepository();
            $promo = $orderContext->dontPersist()->createPromo('targeted-promo', [], [
                $orderContext->createPromoDiscount('targeted-promo', 'first', 'fixed_amount', [
                    'data' => json_encode(['amount' => '50']),
                ], [
                    MinimumLinesQuantity::fromMappedData(['data' => json_encode(['minimum_quantity' => '2'])], []),
                ]),
                $orderContext->createPromoDiscount('targeted-promo', 'second', 'percentage_off', [
                    'data' => json_encode(['percentage' => '10']),
                ]),
            ]);
            $repository->save($promo);

            $promo = $repository->find($promo->promoId);
            $promo->updatePromoDiscount($orderContext->createPromoDiscount('targeted-promo', 'first', 'fixed_amount', [
                'data' => json_encode(['amount' => '75']),
            ], [
                MinimumLinesQuantity::fromMappedData(['data' => json_encode(['minimum_quantity' => '3'])], []),
            ]));
            $repository->save($promo);

            $saved = $repository->find($promo->promoId);
            $this->assertCount(2, $saved->getDiscounts());
            $this->assertSame('75', $saved->getDiscounts()[0]->getData('amount'));
            $this->assertEquals(3, $saved->getDiscounts()[0]->getConditions()[0]->getData('minimum_quantity'));
            $this->assertSame('10', $saved->getDiscounts()[1]->getData('percentage'));

            $saved->deletePromoDiscount($saved->getDiscounts()[0]->getDiscountId());
            $repository->save($saved);

            $remaining = $repository->find($promo->promoId)->getDiscounts();
            $this->assertCount(1, $remaining);
            $this->assertSame('second', $remaining[0]->getDiscountId()->get());
        }
    }

    public function test_it_preserves_combination_policy_and_discountable_type_across_repositories(): void
    {
        foreach (OrderContext::drivers() as $orderContext) {
            $repository = $orderContext->repos()->promoRepository();
            $promo = $orderContext->dontPersist()->createPromo('classified-promo', [], [
                $orderContext->createPromoDiscount('classified-promo', 'classified-discount', 'fixed_amount', [
                    'discountable_type' => DiscountableType::order->value,
                    'data' => json_encode(['amount' => '100']),
                ]),
            ]);
            $promo->updateCombinationPolicy(new CombinationPolicy([DiscountableType::order, DiscountableType::shipping]));

            $repository->save($promo);
            $saved = $repository->find($promo->promoId);
            $applicable = $repository->getAvailableAutomaticPromos()[0];

            $this->assertSame(['order', 'shipping'], $saved->getCombinationPolicy()->toArray());
            $this->assertSame(DiscountableType::order, $saved->getDiscounts()[0]->getDiscountableType());
            $this->assertSame([DiscountableType::order], $applicable->getDiscountableTypes());
        }
    }

    public function test_it_can_delete_a_promo()
    {
        $promosNotFound = 0;

        foreach (OrderContext::drivers() as $orderContext) {
            $repository = $orderContext->repos()->promoRepository();

            $promo = $orderContext->createPromo();

            $repository->delete($promo->promoId);

            try {
                $repository->find($promo->promoId);
            } catch (CouldNotFindPromo $e) {
                $promosNotFound++;
            }
        }

        $this->assertCount($promosNotFound, OrderContext::drivers());
    }

    public function test_it_can_generate_a_next_reference()
    {
        foreach (OrderContext::drivers() as $orderContext) {
            $repository = $orderContext->repos()->promoRepository();

            $this->assertInstanceOf(PromoId::class, $repository->nextReference());
        }
    }

    public function test_promo_is_only_available_when_online_and_within_period()
    {
        foreach (OrderContext::drivers() as $orderContext) {
            $repository = $orderContext->repos()->promoRepository();

            $promo = $orderContext->createPromo('promo-aaa', [
                'state' => PromoState::online->value,
                'start_at' => now()->subDay()->format('Y-m-d H:i:s'),
                'end_at' => now()->addDay()->format('Y-m-d H:i:s'),
            ]);

            // Promo offline
            $orderContext->createPromo('promo-bbb', [
                'state' => PromoState::offline->value,
                'start_at' => now()->subDay()->format('Y-m-d H:i:s'),
                'end_at' => now()->addDay()->format('Y-m-d H:i:s'),
            ]);

            // Promo out of period
            $orderContext->createPromo('promo-ccc', [
                'state' => PromoState::online->value,
                'start_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'end_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
            ]);

            $this->assertCount(1, $repository->getAvailableAutomaticPromos());
            $this->assertEquals($promo->promoId, $repository->getAvailableAutomaticPromos()[0]->promoId);
        }
    }

    public function test_it_can_get_applicable_promo_by_coupon_code()
    {
        foreach (OrderContext::drivers() as $orderContext) {
            $repository = $orderContext->repos()->promoRepository();

            $promo = $orderContext->createPromo('promo-aaa', [
                'coupon_code' => 'foobar',
            ]);

            $this->assertInstanceOf(Promo::class, $repository->findAvailableByCouponCode('foobar'));
        }
    }

    public function test_automatic_promos_include_system_and_admin_promos_but_not_coupon_promos(): void
    {
        foreach (OrderContext::drivers() as $orderContext) {
            $repository = $orderContext->repos()->promoRepository();
            $orderContext->createPromo('system-promo', ['is_system_promo' => true]);
            $orderContext->createPromo('marketing-promo');
            $orderContext->createPromo('coupon-promo', ['coupon_code' => 'SAVE']);
            $orderContext->createPromo('system-coupon-promo', ['is_system_promo' => true, 'coupon_code' => 'SYSTEM']);

            $automatic = $repository->getAvailableAutomaticPromos();

            $this->assertCount(2, $automatic);
            $ids = array_map(fn (Promo $promo) => $promo->promoId->get(), $automatic);
            sort($ids);
            $this->assertSame(['marketing-promo', 'system-promo'], $ids);
            $this->assertSame('coupon-promo', $repository->findAvailableByCouponCode('SAVE')->promoId->get());
            $this->assertSame('system-coupon-promo', $repository->findAvailableByCouponCode('SYSTEM')->promoId->get());
        }
    }

    public function test_available_promos_include_active_coupons_as_well_as_automatic_promos(): void
    {
        foreach (OrderContext::drivers() as $orderContext) {
            $repository = $orderContext->repos()->promoRepository();
            $orderContext->createPromo('automatic');
            $orderContext->createPromo('coupon', ['coupon_code' => 'SAVE']);
            $orderContext->createPromo('offline', ['state' => PromoState::offline->value]);

            $ids = array_map(fn (Promo $promo): string => $promo->promoId->get(), $repository->getAvailablePromos());
            sort($ids);

            $this->assertSame(['automatic', 'coupon'], $ids);
        }
    }

    public function test_mysql_cart_refresh_combines_system_marketing_and_coupon_discounts(): void
    {
        foreach (['system', 'marketing', 'coupon'] as $type) {
            $this->orderContext->createPromo($type, [
                'is_system_promo' => $type === 'system',
                'combines_with' => json_encode(DiscountableType::values()),
                'coupon_code' => $type === 'coupon' ? 'SAVE' : null,
            ], [
                $this->orderContext->createPromoDiscount($type, $type.'-discount', 'fixed_amount', [
                    'data' => json_encode(['amount' => '100', 'tax_mode' => 'exclusive']),
                ]),
            ]);
        }
        $product = $this->catalogContext->createProduct(variantId: null);
        $variant = $this->catalogContext->createVariant($product, values: ['unit_price' => 10000, 'sale_price' => 10000]);
        $application = app(CartApplication::class);
        $orderId = $application->createNewOrder();
        $application->addLine(new AddLine($orderId->get(), $variant->variantId->get(), 1, [], []));
        app(CouponPromoApplication::class)->enterCoupon(new EnterCoupon($orderId->get(), 'SAVE'));

        $application->refresh(new RefreshCart($orderId->get()));

        $order = app(OrderRepository::class)->find($orderId);
        $promoIds = array_map(fn ($discount) => $discount->promoId->get(), $order->getDiscounts());
        sort($promoIds);
        $this->assertSame(['coupon', 'marketing', 'system'], $promoIds);
        $this->assertSame('300', $order->getDiscountTotalExcl()->getAmount());
        $this->assertSame('11640', $order->getTotalIncl()->getAmount());
        $this->assertSame('SAVE', $order->getEnteredCouponCode());
    }
}
