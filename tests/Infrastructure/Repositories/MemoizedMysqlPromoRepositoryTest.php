<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Repositories;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Orchestra\Testbench\TestCase;
use Thinktomorrow\Trader\Domain\Model\Promo\CombinationPolicy;
use Thinktomorrow\Trader\Domain\Model\Promo\Promo;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoId;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoRepository;
use Thinktomorrow\Trader\Infrastructure\Laravel\TraderServiceProvider;

final class MemoizedMysqlPromoRepositoryTest extends TestCase
{
    use DatabaseMigrations;

    protected function getPackageProviders($app): array
    {
        return [TraderServiceProvider::class];
    }

    public function test_it_invalidates_after_a_committed_update_and_delete(): void
    {
        config()->set('cache.default', 'array');
        $repository = app(PromoRepository::class);
        $promo = Promo::create(PromoId::fromString('preview-test'), null, null, null, new CombinationPolicy([]));
        $repository->save($promo);

        $this->assertCount(1, $repository->getAvailablePromos());

        DB::transaction(function () use ($repository, $promo): void {
            $promo->addData(['show_on_cart' => true]);
            $repository->save($promo);

            // Reads in a transaction see the write, rather than the cached pre-transaction state.
            $this->assertTrue($repository->getAvailablePromos()[0]->getData('show_on_cart'));
        });

        $this->assertTrue($repository->getAvailablePromos()[0]->getData('show_on_cart'));

        $repository->delete($promo->promoId);

        $this->assertSame([], $repository->getAvailablePromos());
    }
}
