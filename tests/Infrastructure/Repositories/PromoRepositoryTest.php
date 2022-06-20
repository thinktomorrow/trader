<?php
declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Infrastructure\TestCase;
use Thinktomorrow\Trader\Application\Promo\ApplicablePromo\ApplicablePromoRepository;
use Thinktomorrow\Trader\Application\Promo\ApplicablePromo\ConditionFactory;
use Thinktomorrow\Trader\Application\Promo\ApplicablePromo\DiscountFactory;
use Thinktomorrow\Trader\Application\Promo\ApplicablePromo\Discounts\PercentageOffDiscount;
use Thinktomorrow\Trader\Domain\Model\Promo\Discount;
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
            $this->assertCount(1, $repository->find($promo->promoId)->getChildEntities()[Discount::class]);
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

    /** @test */
    public function it_can_generate_a_next_reference()
    {
        foreach ($this->repositories() as $repository) {
            $this->assertInstanceOf(PromoId::class, $repository->nextReference());
        }
    }

    /** @test */
    public function it_shall_not_get_inactive_promos()
    {
        /** @var ApplicablePromoRepository $repository */
        foreach ($this->repositories() as $repository) {
            $promoOffline = $this->createPromo(['promo_id' => 'aaa', 'state' => PromoState::offline->value]);
            $repository->save($promoOffline);

            $promoScheduled = $this->createPromo(['promo_id' => 'bbb', 'start_at' => now()->addDay()->format('Y-m-d H:i:s')]);
            $repository->save($promoScheduled);

            $promoFinished = $this->createPromo(['promo_id' => 'ccc', 'end_at' => now()->subDay()->format('Y-m-d H:i:s')]);
            $repository->save($promoFinished);

            $this->assertCount(0, $repository->getActivePromos());
        }
    }

    /** @test */
    public function it_can_get_active_promos()
    {
        /** @var ApplicablePromoRepository $repository */
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

            $this->assertCount(4, $repository->getActivePromos());
        }
    }

    private function repositories(): \Generator
    {
//        yield new InMemoryPromoRepository();
        yield new MysqlPromoRepository(new DiscountFactory([
            PercentageOffDiscount::class,
            // TODO: how to set this because application layer should not be present in domain layer...
            // Else list of discounts/conditions with values in domain and actual class in application? -> Application then uses this class as data.
        ], new ConditionFactory([

        ])));
    }

    public function promos(): \Generator
    {
        yield [$this->createPromo([], [Discount::class => [
                array_merge(
                    $this->createDiscount()->getMappedData(),
                    $this->createDiscount()->getChildEntities()
                ),
            ],
        ])];
        yield [$this->createPromo()];
        yield [$this->createPromo(['coupon_code' => 'foobar'])];
        yield [$this->createPromo(['start_at' => '2022-02-02 10:10:10'])];
        yield [$this->createPromo(['end_at' => '2022-02-02 10:10:10'])];
    }
}
