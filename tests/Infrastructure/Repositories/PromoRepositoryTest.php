<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Application\Promo\OrderPromo\Discounts\FixedAmountOrderDiscount;
use Thinktomorrow\Trader\Application\Promo\OrderPromo\Discounts\PercentageOffOrderDiscount;
use Thinktomorrow\Trader\Application\Promo\OrderPromo\OrderConditionFactory;
use Thinktomorrow\Trader\Application\Promo\OrderPromo\OrderDiscountFactory;
use Thinktomorrow\Trader\Application\Promo\OrderPromo\OrderPromo;
use Thinktomorrow\Trader\Application\Promo\OrderPromo\OrderPromoRepository;
use Thinktomorrow\Trader\Domain\Model\Promo\ConditionFactory;
use Thinktomorrow\Trader\Domain\Model\Promo\Conditions\MinimumLinesQuantity;
use Thinktomorrow\Trader\Domain\Model\Promo\DiscountFactory;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\FixedAmountDiscount;
use Thinktomorrow\Trader\Domain\Model\Promo\Exceptions\CouldNotFindPromo;
use Thinktomorrow\Trader\Domain\Model\Promo\Promo;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoId;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoState;
use Thinktomorrow\Trader\Infrastructure\Laravel\Repositories\MysqlPromoRepository;
use Thinktomorrow\Trader\Infrastructure\Test\Repositories\InMemoryPromoRepository;

final class PromoRepositoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     * @dataProvider promos
     */
    public function it_can_save_and_find_a_promo(Promo $promo)
    {
        foreach ($this->repositories() as $repository) {
            $repository->save($promo);
            $promo->releaseEvents();

            $this->assertEquals($promo, $repository->find($promo->promoId));
        }
    }

    /**
     * @test
     * @dataProvider promos
     */
    public function it_can_delete_a_promo(Promo $promo)
    {
        $promosNotFound = 0;

        foreach ($this->repositories() as $repository) {
            $repository->save($promo);
            $repository->delete($promo->promoId);

            try {
                $repository->find($promo->promoId);
            } catch (CouldNotFindPromo $e) {
                $promosNotFound++;
            }
        }

        $this->assertEquals(count(iterator_to_array($this->repositories())), $promosNotFound);
    }

    public function test_it_can_generate_a_next_reference()
    {
        foreach ($this->repositories() as $repository) {
            $this->assertInstanceOf(PromoId::class, $repository->nextReference());
        }
    }

    public function test_it_can_get_applicable_promos()
    {
        /** @var OrderPromoRepository $repository */
        foreach ($this->repositories() as $repository) {
            $promoOffline = $this->createPromo(['promo_id' => 'xxx', 'state' => PromoState::online->value]);
            $repository->save($promoOffline);

            $promoPeriod = $this->createPromo([
                'promo_id' => 'aaa',
                'start_at' => now()->subDay()->format('Y-m-d H:i:s'),
                'end_at' => now()->addDay()->format('Y-m-d H:i:s'),
            ]);
            $repository->save($promoPeriod);

            $promoScheduled = $this->createPromo(['promo_id' => 'bbb', 'start_at' => now()->subDay()->format('Y-m-d H:i:s')]);
            $repository->save($promoScheduled);

            $promoFinished = $this->createPromo(['promo_id' => 'ccc', 'end_at' => now()->addDay()->format('Y-m-d H:i:s')]);
            $repository->save($promoFinished);

            $this->assertCount(4, $repository->getAvailableOrderPromos());
        }
    }

    public function test_it_shall_not_get_non_applicable_promos()
    {
        /** @var OrderPromoRepository $repository */
        foreach ($this->repositories() as $repository) {
            // Promo with coupon is never automatically applicable.
            $promoWithCoupon = $this->createPromo(['promo_id' => 'abc', 'coupon_code' => 'foobar']);
            $repository->save($promoWithCoupon);

            $promoOffline = $this->createPromo(['promo_id' => 'aaa', 'state' => PromoState::offline->value]);
            $repository->save($promoOffline);

            $promoScheduled = $this->createPromo(['promo_id' => 'bbb', 'start_at' => now()->addDay()->format('Y-m-d H:i:s')]);
            $repository->save($promoScheduled);

            $promoFinished = $this->createPromo(['promo_id' => 'ccc', 'end_at' => now()->subDay()->format('Y-m-d H:i:s')]);
            $repository->save($promoFinished);

            $this->assertCount(0, $repository->getAvailableOrderPromos());
        }
    }

    public function test_it_can_get_applicable_promo_by_coupon_code()
    {
        /** @var OrderPromoRepository $repository */
        foreach ($this->repositories() as $repository) {
            $promoWithCoupon = $this->createPromo(['promo_id' => 'abc', 'coupon_code' => 'foobar']);
            $repository->save($promoWithCoupon);

            $this->assertInstanceOf(OrderPromo::class, $repository->findOrderPromoByCouponCode('foobar'));
        }
    }

    private static function repositories(): \Generator
    {
        $factories = [
            new DiscountFactory([
                FixedAmountDiscount::class,
                PercentageOffOrderDiscount::class,
            ], new ConditionFactory([
                MinimumLinesQuantity::class,
            ])),
            new OrderDiscountFactory([
                FixedAmountOrderDiscount::class,
                PercentageOffOrderDiscount::class,
            ], new OrderConditionFactory([
                \Thinktomorrow\Trader\Application\Promo\OrderPromo\Conditions\MinimumLinesQuantityOrderCondition::class,
            ])),

        ];

        yield new InMemoryPromoRepository(...$factories);
        yield new MysqlPromoRepository(...$factories);
    }

    public static function promos(): \Generator
    {
        yield [static::createPromo([], [
            static::createDiscount(['discount_id' => 'abc'], [static::createCondition()]),
            static::createDiscount(['discount_id' => 'def']),
        ])];
        yield [static::createPromo()];
        yield [static::createPromo(['coupon_code' => 'foobar'], [static::createDiscount()])];
        yield [static::createPromo(['start_at' => '2022-02-02 10:10:10']), [static::createDiscount([], [static::createCondition()])]];
        yield [static::createPromo(['end_at' => '2022-02-02 10:10:10'], [static::createDiscount([], [static::createCondition()])])];
    }
}
