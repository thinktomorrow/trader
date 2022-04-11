<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\DB;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\Variant;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\Product\VariantRepository;
use Thinktomorrow\Trader\Domain\Model\Product\Option\OptionValue;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantState;
use Thinktomorrow\Trader\Application\Product\ProductOptions\VariantForProductOptionCollection;
use Thinktomorrow\Trader\Application\Cart\VariantForCart\VariantForCart;
use Thinktomorrow\Trader\Domain\Model\Product\Exceptions\CouldNotFindVariant;
use Thinktomorrow\Trader\Application\Cart\VariantForCart\VariantForCartRepository;
use Thinktomorrow\Trader\Application\Product\ProductOptions\VariantForProductOption;
use Thinktomorrow\Trader\Application\Product\ProductOptions\VariantForProductOptionRepository;

class MysqlVariantRepository implements VariantRepository, VariantForCartRepository, VariantForProductOptionRepository
{
    private static string $variantTable = 'trader_product_variants';
    private static string $optionTable = 'trader_product_options';
    private static string $optionValueTable = 'trader_product_option_values';
    private static string $variantOptionValueLookupTable = 'trader_variant_option_values';

    public function save(Variant $variant): void
    {
        $state = $variant->getMappedData();

        if (!$this->exists($variant->variantId)) {
            DB::table(static::$variantTable)->insert($state);
        } else {
            DB::table(static::$variantTable)->where('variant_id', $variant->variantId)->update($state);
        }

        $this->upsertOptionValues($variant);
    }

    private function exists(VariantId $variantId): bool
    {
        return DB::table(static::$variantTable)->where('variant_id', $variantId->get())->exists();
    }

    private function upsertOptionValues(Variant $variant): void
    {
        $optionValueIds = array_map(fn($optionValueState) => $optionValueState['option_id'], $variant->getChildEntities()[OptionValue::class]);

        DB::table(static::$optionValueTable)
            ->where('variant_id', $variant->variantId)
            ->whereNotIn('option_value_id', $optionValueIds)
            ->delete();

        foreach ($variant->getChildEntities()[OptionValue::class] as $optionValueState) {

            DB::table(static::$optionValueTable)
                ->updateOrInsert([
                    'option_id' => $optionValueState['option_id'],
                    'option_value_id'  => $optionValueState['option_value_id'],
                ], $optionValueState);
        }
    }

    public function find(VariantId $variantId): Variant
    {
        $state = DB::table(static::$variantTable)
            ->where(static::$variantTable . '.variant_id', $variantId->get())
            ->first();

        if (!$state) {
            throw new CouldNotFindVariant('No variant found by id [' . $variantId->get() . ']');
        }

        $optionValueStates = DB::table(static::$optionValueTable)
            ->where(static::$optionValueTable . '.variant_id', $variantId->get())
            ->get()
            ->map(fn($item) => (array) $item)
            ->toArray();

        $state = (array) $state;
        $state['includes_vat'] = (bool) $state['includes_vat'];

        return Variant::fromMappedData($state, [
            'product_id' => $state['product_id'],
        ], [
            OptionValue::class => $optionValueStates,
        ]);
    }

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
                'includes_tax'
            ])
            ->first();

        if(!$state) {
            throw new \RuntimeException('No online variant found by id [' . $variantId->get(). ']');
        }

        $state = (array) $state;

        return VariantForCart::fromMappedData(array_merge($state, [
            'includes_tax' => (bool) $state['includes_tax'],
        ]));
    }

    public function getVariantsForProductOption(ProductId $productId): VariantForProductOptionCollection
    {
        $rows = DB::table(static::$variantTable)
            ->join(static::$variantOptionValueLookupTable, static::$variantTable.'.variant_id', '=', static::$variantOptionValueLookupTable.'.variant_id')
            ->join(static::$optionValueTable, static::$variantOptionValueLookupTable.'.option_value_id','=',static::$optionValueTable.'.option_value_id')
            ->where(static::$variantTable . '.product_id', $productId->get())
            ->whereIn(static::$variantTable . '.state', VariantState::availableStates())
            ->select([
                static::$variantOptionValueLookupTable.'.variant_id',
                static::$optionValueTable.'.option_value_id',
                static::$optionValueTable.'.option_id',
                static::$optionValueTable.'.data',
            ])
            ->orderBy(static::$variantTable.'.order_column','ASC')
            ->get()
            ->map(fn($item) => (array) $item)
            ->groupBy('variant_id');

        return VariantForProductOptionCollection::fromType($rows->map(function($optionValueStates) {
            return VariantForProductOption::fromMappedData([
                'variant_id' => $optionValueStates->first()['variant_id']
            ], $optionValueStates->all());
        })->values()->all());
    }
}
