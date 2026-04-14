<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Application\Promo\OrderPromo\OrderPromo;
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
}
