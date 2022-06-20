<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Carbon\Carbon;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Query\Builder;
use Thinktomorrow\Trader\Domain\Model\Promo\Promo;
use Thinktomorrow\Trader\Domain\Common\Map\Factory;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoId;
use Thinktomorrow\Trader\Domain\Model\Promo\Discount;
use Thinktomorrow\Trader\Domain\Model\Promo\Condition;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoState;
use Thinktomorrow\Trader\Domain\Model\Promo\DiscountFactory;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoRepository;
use Thinktomorrow\Trader\Domain\Model\Promo\Exceptions\CouldNotFindPromo;
use Thinktomorrow\Trader\Application\Promo\ApplicablePromo\ApplicablePromo;
use Thinktomorrow\Trader\Application\Promo\ApplicablePromo\ApplicableDiscount;
use Thinktomorrow\Trader\Application\Promo\ApplicablePromo\ApplicablePromoRepository;

final class MysqlPromoRepository implements PromoRepository, ApplicablePromoRepository
{
    private static string $promoTable = 'trader_promos';
    private static string $promoDiscountTable = 'trader_promo_discounts';
    private static string $promoConditionTable = 'trader_promo_conditions';

    private DiscountFactory $discountFactory;

    public function __construct(DiscountFactory $discountFactory)
    {
        $this->discountFactory = $discountFactory;
    }

    public function getActivePromos(): array
    {
        $results = $this->baseActiveQuery()->get();

        $discountStates = $this->getDiscountStates($results->pluck('promo_id')->toArray());

        return array_map(function ($promoResult) use ($discountStates) {
            $promoResult = (array) $promoResult;

            return ApplicablePromo::fromMappedData($promoResult, [
                ApplicableDiscount::class => $this->makeDiscounts($discountStates, $promoResult, $this->discountFactory),
            ]);
        }, $results->toArray());
    }

    public function findActivePromoByCouponCode(string $couponCode): ?ApplicablePromo
    {
        $result = $this->baseActiveQuery()
            ->where('coupon_code', $couponCode)
            ->first();

        return null;

        // add conditions and discounts
    }

    private function baseActiveQuery(): Builder
    {
        $date = Carbon::now();

        return DB::table(static::$promoTable)
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
            DB::table(static::$promoTable)->insert($state);
        } else {
            DB::table(static::$promoTable)
                ->where('promo_id', $promo->promoId)
                ->update($state);
        }

        $this->upsertDiscounts($promo);
    }

    private function upsertDiscounts(Promo $promo): void
    {
        $existingDiscountIds = DB::table(static::$promoDiscountTable)
            ->where('promo_id', $promo->promoId)
            ->select('discount_id')
            ->get()
            ->pluck('discount_id')
            ->toArray();

        DB::table(static::$promoDiscountTable)
            ->where('promo_id', $promo->promoId)
            ->delete();

        DB::table(static::$promoConditionTable)
            ->whereIn('discount_id', $existingDiscountIds)
            ->delete();

        foreach ($promo->getDiscounts() as $discount) {
            $insertedId = DB::table(static::$promoDiscountTable)
                ->insertGetId($discount->getMappedData());

            DB::table(static::$promoConditionTable)->insert(
                array_map(fn ($conditionState) => array_merge($conditionState, [
                    'discount_id' => $insertedId,
                ]), $discount->getChildEntities()[Condition::class])
            );

            // Conditions
        }
    }

    private function exists(PromoId $promoId): bool
    {
        return DB::table(static::$promoTable)->where('promo_id', $promoId->get())->exists();
    }

    public function find(PromoId $promoId): Promo
    {
        $promoState = DB::table(static::$promoTable)
            ->where('promo_id', $promoId->get())
            ->first();

        if (! $promoState) {
            throw new CouldNotFindPromo('No promo found by id [' . $promoId->get() . ']');
        }

        $promoState = (array) $promoState;

        $discountStates = $this->getDiscountStates($promoId->get());
        $discounts = $this->makeDiscounts($discountStates, $promoState, $this->discountFactory);

        return Promo::fromMappedData($promoState, [
            Discount::class => $discounts,
        ]);
    }

    private function getDiscountStates(array|string $promoIds): Collection
    {
        return DB::table(static::$promoDiscountTable)
            ->leftJoin(static::$promoConditionTable, static::$promoDiscountTable.'.discount_id', '=', static::$promoConditionTable.'.discount_id')
            ->whereIn('promo_id', (array) $promoIds)
            ->select([
                static::$promoDiscountTable.'.*',
                DB::raw(static::$promoConditionTable.'.key AS condition_key'),
                DB::raw(static::$promoConditionTable.'.data AS condition_data'),
            ])
            ->get();
    }

    private function makeDiscounts(Collection $discountResults, array $promoState, Factory $factory): array
    {
        return $discountResults
            ->where('promo_id', $promoState['promo_id'])
            ->groupBy('discount_id')
            ->reject(fn (Collection $group) => $group->isEmpty())
            ->map(function (Collection $group) use ($promoState, $factory) {

                $conditionStates = $group
                    ->reject(fn($item) => !$item->condition_key)
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
        DB::table(static::$promoDiscountTable)->where('promo_id', $promoId->get())->delete();
        DB::table(static::$promoConditionTable)->where('promo_id', $promoId->get())->delete();
        DB::table(static::$promoTable)->where('promo_id', $promoId->get())->delete();
    }

    public function nextReference(): PromoId
    {
        return PromoId::fromString((string) Uuid::uuid4());
    }
}
