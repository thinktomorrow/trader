<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Carbon\Carbon;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Database\Query\Builder;
use Thinktomorrow\Trader\Domain\Model\Promo\Promo;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoId;
use Thinktomorrow\Trader\Domain\Model\Promo\Condition;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoState;
use Thinktomorrow\Trader\Domain\Model\Promo\PromoRepository;
use Thinktomorrow\Trader\Application\Promo\ApplicablePromo\Discount;
use Thinktomorrow\Trader\Domain\Model\Promo\Exceptions\CouldNotFindPromo;
use Thinktomorrow\Trader\Application\Promo\ApplicablePromo\DiscountFactory;
use Thinktomorrow\Trader\Application\Promo\ApplicablePromo\ApplicablePromo;
use Thinktomorrow\Trader\Application\Promo\ApplicablePromo\ConditionFactory;
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
        $results = $this->baseActiveQuery()
            ->get();

        $promoIds = $results->pluck('promo_id')->toArray();

        $discountResults = DB::table(static::$promoDiscountTable)
            ->leftJoin(static::$promoConditionTable, static::$promoDiscountTable.'.discount_id', '=', static::$promoConditionTable.'.discount_id')
            ->whereIn('promo_id', $promoIds)
            ->select([
                static::$promoDiscountTable.'.*',
                DB::raw(static::$promoConditionTable.'.key AS condition_key'),
                DB::raw(static::$promoConditionTable.'.data AS condition_data'),
            ])
            ->get();

        return array_map(function($promoResult) use($discountResults){

            $promoResult = (array) $promoResult;

            $discounts = $discountResults
                ->where('promo_id', $promoResult['promo_id'])
                ->groupBy('discount_id')
                ->reject(fn(Collection $group) => $group->isEmpty())
                ->map(function(Collection $group) use($promoResult) {

                    $conditionStates = $group
                        ->map(fn($item) => (array) $item)
                        ->map(fn($conditionState) => array_merge($conditionState, [
                            'key' => $conditionState['condition_key'],
                            'data' => $conditionState['condition_data'],
                        ]))
                        ->toArray();

                    return $this->discountFactory->make(
                        $group->first()->key,
                        (array) $group->first(),
                        $promoResult,
                        $conditionStates
                    );
                })->toArray();


            return ApplicablePromo::fromMappedData($promoResult, [
                Discount::class => $discounts,
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
            ->where(function($query) use($date){
                $query->where('start_at','<', $date)
                    ->orWhereNull('start_at');
            })
            ->where(function($query) use($date){
                $query->where('end_at','>', $date)
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
                array_map(fn($conditionState) => array_merge($conditionState, [
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

        $conditions = DB::table(static::$promoConditionTable)
            ->where('promo_id', $promoId->get())
            ->get()
            ->map(fn($record) => (array) $record)
            ->toArray();

        $discounts = DB::table(static::$promoDiscountTable)
            ->where('promo_id', $promoId->get())
            ->get()
            ->map(fn($record) => (array) $record)
            // WE need the classes because of validation constraints.hill
//            ->map(fn($discountState) => $this->discountFactory->make($discountState['key'], $discountState, $promoState, $conditions))
            ->map(fn($discountState) => \Thinktomorrow\Trader\Domain\Model\Promo\Discount::fromMappedData($discountState, $promoState, $conditions))
            ->toArray();
trap($discounts);
        return Promo::fromMappedData($promoState, [
            Discount::class => $discounts,
        ]);
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
