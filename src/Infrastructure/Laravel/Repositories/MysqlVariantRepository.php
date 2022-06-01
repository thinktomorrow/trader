<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\DB;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Application\Common\TraderHelpers;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\Variant;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\Product\VariantRepository;
use Thinktomorrow\Trader\Application\Cart\VariantForCart\VariantForCart;
use Thinktomorrow\Trader\Application\Cart\VariantForCart\VariantForCartRepository;

class MysqlVariantRepository implements VariantRepository, VariantForCartRepository
{
    private static string $variantTable = 'trader_product_variants';
    private static string $optionTable = 'trader_product_options';
    private static string $optionValueTable = 'trader_product_option_values';
    private static string $variantOptionValueLookupTable = 'trader_variant_option_values';

    public function save(Variant $variant): void
    {
        $state = $variant->getMappedData();

        $option_value_ids = TraderHelpers::array_remove($state, 'option_value_ids');

        if (!$this->exists($variant->variantId)) {
            DB::table(static::$variantTable)->insert($state);
        } else {
            DB::table(static::$variantTable)->where('variant_id', $variant->variantId)->update($state);
        }

        $this->syncVariantOptionValueIds($variant->variantId, $option_value_ids);
    }

    private function exists(VariantId $variantId): bool
    {
        return DB::table(static::$variantTable)->where('variant_id', $variantId->get())->exists();
    }

    private function syncVariantOptionValueIds(VariantId $variantId, array $option_value_ids): void
    {
        $changedOptionValueIds = collect($option_value_ids);

        // Get all existing option_value ids
        $existingOptionValueIds = DB::table(static::$variantOptionValueLookupTable)
            ->where('variant_id', $variantId)
            ->select('option_value_id')
            ->get()
            ->pluck('option_value_id');

        // Remove the ones that are not in the new list
        $detachOptionValueIds = $existingOptionValueIds->diff($changedOptionValueIds);
        if($detachOptionValueIds->count() > 0) {
            DB::table(static::$variantOptionValueLookupTable)
                ->where('variant_id', $variantId)
                ->whereIn('option_value_id', $detachOptionValueIds->all())
                ->delete();
        }

        // Insert the new option_value ids
        $attachOptionValueIds = $changedOptionValueIds->diff($existingOptionValueIds);

        $insertData = $attachOptionValueIds->map(function($option_value_id) use($variantId) {
            return ['variant_id' => $variantId->get(), 'option_value_id' => $option_value_id];
        })->all();

        DB::table(static::$variantOptionValueLookupTable)->insert($insertData);
    }

    public function getStatesByProduct(ProductId $productId): array
    {
        $variantStates = DB::table(static::$variantTable)
            ->select([static::$variantTable . '.*', DB::raw('GROUP_CONCAT(`option_value_id`) AS option_value_ids')])
            ->where(static::$variantTable . '.product_id', $productId->get())
            ->leftJoin(static::$variantOptionValueLookupTable, static::$variantTable . '.variant_id','=',static::$variantOptionValueLookupTable.'.variant_id')
            ->groupBy(static::$variantTable . '.variant_id')
            ->orderBy(static::$variantTable.'.order_column')
            ->get()
            ->map(fn($item) => (array) $item)
            ->map(fn($item) => array_merge($item, [
                'includes_vat' => (bool) $item['includes_vat'],
                'option_value_ids' => $item['option_value_ids'] ? explode(',', $item['option_value_ids']) : []
            ]))
            ->toArray();

        // Handle a bug in laravel where raw group concat statement would return a record with falsy null values
        if(count($variantStates) == 1 && null === $variantStates[0]['variant_id']) {
            $variantStates = [];
        }

        return $variantStates;
    }

//    public function find(VariantId $variantId): Variant
//    {
//        $state = DB::table(static::$variantTable)
//            ->where(static::$variantTable . '.variant_id', $variantId->get())
//            ->first();
//
//        if (!$state) {
//            throw new CouldNotFindVariant('No variant found by id [' . $variantId->get() . ']');
//        }
//
//        $optionValueStates = DB::table(static::$optionValueTable)
//            ->where(static::$optionValueTable . '.variant_id', $variantId->get())
//            ->get()
//            ->map(fn($item) => (array) $item)
//            ->toArray();
//
//        $state = (array) $state;
//        $state['includes_vat'] = (bool) $state['includes_vat'];
//
//        return Variant::fromMappedData($state, [
//            'product_id' => $state['product_id'],
//        ], [
//            OptionValue::class => $optionValueStates,
//        ]);
//    }

    public function delete(VariantId $variantId): void
    {
        DB::table(static::$optionValueTable)->where('variant_id', $variantId->get())->delete();
        DB::table(static::$variantTable)->where('variant_id', $variantId->get())->delete();
    }

    public function nextReference(): VariantId
    {
        return VariantId::fromString((string) Uuid::uuid4());
    }

    public function findVariantForCart(VariantId $variantId): VariantForCart
    {
        // Basic builder query
        $state = DB::table(static::$variantTable)
            ->where(static::$variantTable . '.variant_id', $variantId->get())
            ->select([
                'variant_id',
                'sale_price',
                'tax_rate',
                'includes_vat'
            ])
            ->first();

        if(!$state) {
            throw new \RuntimeException('No online variant found by id [' . $variantId->get(). ']');
        }

        $state = (array) $state;

        return VariantForCart::fromMappedData(array_merge($state, [
            'includes_vat' => (bool) $state['includes_vat'],
        ]));
    }
}
