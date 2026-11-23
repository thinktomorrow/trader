<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Illuminate\Support\Facades\DB;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\PromoDiscountId;
use Thinktomorrow\Trader\Domain\Model\Promo\Promo;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoId;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoRepository;

final class MemoizedMysqlPromoRepository implements PromoRepository
{
    public function __construct(private MysqlPromoRepository $repository, private PromoCache $promoCache) {}

    public function getAvailablePromos(): array
    {
        if (DB::connection()->transactionLevel() > 0) {
            return $this->repository->getAvailablePromos();
        }

        return $this->promoCache->rememberAvailable(fn (): array => [
            $this->repository->getAvailablePromos(),
            $this->repository->nextAvailabilityTransitionAt(),
        ]);
    }

    public function getAvailableAutomaticPromos(): array
    {
        return array_values(array_filter($this->getAvailablePromos(), fn (Promo $promo): bool => ! $promo->hasCouponCode()));
    }

    public function findAvailableByCouponCode(string $couponCode): ?Promo
    {
        foreach ($this->getAvailablePromos() as $promo) {
            if ($promo->getCouponCode() === $couponCode && $promo->hasCouponCode()) {
                return $promo;
            }
        }

        return null;
    }

    public function find(PromoId $promoId): Promo
    {
        return $this->repository->find($promoId);
    }

    public function save(Promo $promo): void
    {
        $this->repository->save($promo);
        $this->invalidateAfterCommit();
    }

    public function delete(PromoId $promoId): void
    {
        $this->repository->delete($promoId);
        $this->invalidateAfterCommit();
    }

    public function nextReference(): PromoId
    {
        return $this->repository->nextReference();
    }

    public function nextPromoDiscountReference(): PromoDiscountId
    {
        return $this->repository->nextPromoDiscountReference();
    }

    private function invalidateAfterCommit(): void
    {
        if (DB::connection()->transactionLevel() > 0) {
            DB::afterCommit(fn () => $this->promoCache->clear());

            return;
        }

        $this->promoCache->clear();
    }
}
