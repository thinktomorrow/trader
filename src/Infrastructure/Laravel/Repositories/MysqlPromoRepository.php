<?php

declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;
use Thinktomorrow\Trader\Domain\Model\Promo\Conditions\Condition;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\Discount;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\DiscountFactory;
use Thinktomorrow\Trader\Domain\Model\Promo\Discounts\PromoDiscountId;
use Thinktomorrow\Trader\Domain\Model\Promo\Exceptions\CouldNotFindPromo;
use Thinktomorrow\Trader\Domain\Model\Promo\Promo;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoId;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoRepository;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoState;

final class MysqlPromoRepository implements PromoRepository
{
    private static string $promoTable = 'trader_promos';

    private static string $promoDiscountTable = 'trader_promo_discounts';

    private static string $promoConditionTable = 'trader_promo_discount_conditions';

    private DiscountFactory $discountFactory;

    public function __construct(DiscountFactory $discountFactory)
    {
        $this->discountFactory = $discountFactory;
    }

    public function getAvailableAutomaticPromos(): array
    {
        $results = $this->baseActiveQuery()
            ->whereNull('coupon_code')
            ->get();

        return $this->composePromosFromResults($results);
    }

    public function findAvailableByCouponCode(string $couponCode): ?Promo
    {
        $result = $this->baseActiveQuery()
            ->where('coupon_code', $couponCode)
            ->first();

        if (! $result) {
            return null;
        }

        return $this->composePromosFromResults(collect([$result]))[0];
    }

    private function composePromosFromResults(Collection $results): array
    {
        $discountStates = $this->getDiscountStates($results->pluck('promo_id')->toArray());

        return array_map(function ($promoResult) use ($discountStates) {
            $promoResult = (array) $promoResult;

            return Promo::fromMappedData(array_merge($promoResult, [
                'is_system_promo' => (bool) $promoResult['is_system_promo'],
            ]), [
                Discount::class => $this->makeDiscounts($discountStates, $promoResult, $this->discountFactory),
            ]);
        }, $results->toArray());
    }

    private function baseActiveQuery(): Builder
    {
        $date = Carbon::now();

        return DB::table(self::$promoTable)
            ->whereIn('state', PromoState::onlineStates())
            ->where(function ($query) use ($date) {
                $query->where('start_at', '<', $date)
                    ->orWhereNull('start_at');
            })
            ->where(function ($query) use ($date) {
                $query->where('end_at', '>', $date)
                    ->orWhereNull('end_at');
            });
    }

    public function save(Promo $promo): void
    {
        $state = $promo->getMappedData();

        if (! $this->exists($promo->promoId)) {
            DB::table(self::$promoTable)->insert($state);
        } else {
            DB::table(self::$promoTable)
                ->where('promo_id', $promo->promoId)
                ->update($state);
        }

        $this->upsertDiscounts($promo);
    }

    private function upsertDiscounts(Promo $promo): void
    {
        $discountIds = array_map(fn ($discount) => $discount->discountId->get(), $promo->getDiscounts());

        $existingDiscountIds = DB::table(self::$promoDiscountTable)
            ->where('promo_id', $promo->promoId)
            ->select('discount_id')
            ->get()
            ->pluck('discount_id')
            ->toArray();

        DB::table(self::$promoDiscountTable)
            ->where('promo_id', $promo->promoId)
            ->whereNotIn('discount_id', $discountIds)
            ->delete();

        DB::table(self::$promoConditionTable)
            ->whereIn('discount_id', $existingDiscountIds)
            ->delete();

        foreach ($promo->getDiscounts() as $discount) {
            DB::table(self::$promoDiscountTable)
                ->updateOrInsert([
                    'discount_id' => $discount->discountId->get(),
                ], $discount->getMappedData());

            DB::table(self::$promoConditionTable)->insert(
                array_map(fn ($conditionState) => array_merge($conditionState, [
                    'discount_id' => $discount->discountId->get(),
                ]), $discount->getChildEntities()[Condition::class])
            );

            // Conditions
        }
    }

    private function exists(PromoId $promoId): bool
    {
        return DB::table(self::$promoTable)->where('promo_id', $promoId->get())->exists();
    }

    public function find(PromoId $promoId): Promo
    {
        $promoState = DB::table(self::$promoTable)
            ->where('promo_id', $promoId->get())
            ->first();

        if (! $promoState) {
            throw new CouldNotFindPromo('No promo found by id ['.$promoId->get().']');
        }

        $promoState = (array) $promoState;

        $discountStates = $this->getDiscountStates($promoId->get());
        $discounts = $this->makeDiscounts($discountStates, $promoState, $this->discountFactory);

        return Promo::fromMappedData(array_merge($promoState, [
            'is_system_promo' => (bool) $promoState['is_system_promo'],
        ]), [
            Discount::class => $discounts,
        ]);
    }

    private function getDiscountStates(array|string $promoIds): Collection
    {
        return DB::table(self::$promoDiscountTable)
            ->leftJoin(self::$promoConditionTable, self::$promoDiscountTable.'.discount_id', '=', self::$promoConditionTable.'.discount_id')
            ->whereIn('promo_id', (array) $promoIds)
            ->select([
                self::$promoDiscountTable.'.*',
                DB::raw(self::$promoConditionTable.'.key AS condition_key'),
                DB::raw(self::$promoConditionTable.'.data AS condition_data'),
            ])
            ->get();
    }

    private function makeDiscounts(Collection $discountResults, array $promoState, DiscountFactory $factory): array
    {
        return $discountResults
            ->where('promo_id', $promoState['promo_id'])
            ->groupBy('discount_id')
            ->reject(fn (Collection $group) => $group->isEmpty())
            ->map(function (Collection $group) use ($promoState, $factory) {
                $conditionStates = $group
                    ->reject(fn ($item) => ! $item->condition_key)
                    ->map(fn ($item) => (array) $item)
                    ->map(fn ($conditionState) => array_merge($conditionState, [
                        'key' => $conditionState['condition_key'],
                        'data' => $conditionState['condition_data'],
                    ]))
                    ->toArray();

                return $factory->make(
                    $group->first()->key,
                    (array) $group->first(),
                    $promoState,
                    $conditionStates
                );
            })->values()->toArray();
    }

    public function delete(PromoId $promoId): void
    {
        $discountIds = DB::table(self::$promoDiscountTable)
            ->where('promo_id', $promoId->get())
            ->get()
            ->map(fn ($discount) => $discount->discount_id)->toArray();

        DB::table(self::$promoConditionTable)->whereIn('discount_id', $discountIds)->delete();
        DB::table(self::$promoDiscountTable)->whereIn('discount_id', $discountIds)->delete();
        DB::table(self::$promoTable)->where('promo_id', $promoId->get())->delete();
    }

    public function nextReference(): PromoId
    {
        return PromoId::fromString((string) Uuid::uuid4());
    }

    public function nextPromoDiscountReference(): PromoDiscountId
    {
        return PromoDiscountId::fromString((string) Uuid::uuid4());
    }
}
