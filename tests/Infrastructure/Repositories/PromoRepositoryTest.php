<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Application\Cart\CartApplication;
use Thinktomorrow\Trader\Application\Cart\Line\AddLine;
use Thinktomorrow\Trader\Application\Cart\RefreshCart\RefreshCart;
use Thinktomorrow\Trader\Application\Promo\Coupon\CouponPromoApplication;
use Thinktomorrow\Trader\Application\Promo\Coupon\EnterCoupon;
use Thinktomorrow\Trader\Application\Promo\OrderPromo\OrderPromo;
use Thinktomorrow\Trader\Domain\Common\Price\TaxMode;
use Thinktomorrow\Trader\Domain\Model\Order\OrderRepository;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\FixedAmountDiscount;
use Thinktomorrow\Trader\Domain\Model\Promo\Exceptions\CouldNotFindPromo;
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

            $this->assertCount(1, $repository->getAvailableOrderPromos());
            $this->assertEquals($promo->promoId, $repository->getAvailableOrderPromos()[0]->promoId);
        }
    }

    public function test_it_can_get_applicable_promo_by_coupon_code()
    {
        foreach (OrderContext::drivers() as $orderContext) {
            $repository = $orderContext->repos()->promoRepository();

            $promo = $orderContext->createPromo('promo-aaa', [
                'coupon_code' => 'foobar',
            ]);

            $this->assertInstanceOf(OrderPromo::class, $repository->findOrderPromoByCouponCode('foobar'));
        }
    }

    public function test_system_marketing_and_coupon_promos_are_loaded_separately(): void
    {
        foreach (OrderContext::drivers() as $orderContext) {
            $repository = $orderContext->repos()->promoRepository();
            $orderContext->createPromo('system-promo', ['is_system_promo' => true]);
            $orderContext->createPromo('marketing-promo');
            $orderContext->createPromo('coupon-promo', ['coupon_code' => 'SAVE']);

            $marketing = $repository->getAvailableOrderPromos();
            $system = $repository->getAvailableSystemPromos();

            $this->assertCount(1, $marketing);
            $this->assertSame('marketing-promo', $marketing[0]->promoId->get());
            $this->assertCount(1, $system);
            $this->assertSame('system-promo', $system[0]->promoId->get());
            $this->assertSame('coupon-promo', $repository->findOrderPromoByCouponCode('SAVE')->promoId->get());
        }
    }

    public function test_mysql_cart_refresh_combines_system_marketing_and_coupon_discounts(): void
    {
        foreach (['system', 'marketing', 'coupon'] as $type) {
            $this->orderContext->createPromo($type, [
                'is_system_promo' => $type === 'system',
                'is_combinable' => true,
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
