<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\DB;
use Thinktomorrow\Trader\Domain\Model\Product\Product;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\Option\Option;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\Variant;
use Thinktomorrow\Trader\Domain\Model\Product\Option\OptionId;
use Thinktomorrow\Trader\Domain\Model\Product\ProductRepository;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Domain\Model\Product\Exceptions\CouldNotFindProduct;

class MysqlProductRepository implements ProductRepository
{
    private static string $productTable = 'trader_products';
    private static string $variantTable = 'trader_product_variants';
    private static string $productTaxonLookupTable = 'trader_taxa_products';

    private static string $optionTable = 'trader_product_options';
    private static string $optionValueTable = 'trader_product_option_values';
    private static string $variantOptionValueLookupTable = 'trader_variant_option_values';

    public function save(Product $product): void
    {
        // Save product WITH
        // VARIANTS,
        // options
        // personalisations

        $state = $product->getMappedData();
        $taxon_ids = $this->arrayRemove($state, 'taxon_ids');

        if (!$this->exists($product->productId)) {
            DB::table(static::$productTable)->insert($state);
        } else {
            DB::table(static::$productTable)->where('product_id', $product->productId)->update($state);
        }

        // TODO: upsertOptions and option values

        $this->upsertOptions($product);
        $this->syncTaxonIds($product->productId, $taxon_ids);
        $this->upsertVariants($product);
    }

    private function upsertOptions(Product $product): void
    {
        $option_ids = array_map(fn($optionState) => $optionState['option_id'], $product->getChildEntities()[Option::class]);

        DB::table(static::$optionTable)
            ->where('product_id', $product->productId)
            ->whereNotIn('option_id', $option_ids)
            ->delete();

        foreach ($product->getChildEntities()[Option::class] as $optionState) {

            $option_values = $this->arrayRemove($optionState, 'values');

            DB::table(static::$optionTable)
                ->updateOrInsert([
                    'product_id' => $product->productId->get(),
                    'option_id'  => $optionState['option_id'],
                ], $optionState);

            // TODO: do this in one query...
            foreach($option_values as $option_value) {
                DB::table(static::$optionValueTable)
                    ->updateOrInsert([
                        'option_id'  => $option_value['option_id'],
                        'option_value_id'  => $option_value['option_value_id'],
                    ], $option_value);
            }
        }
    }

    private function upsertVariants(Product $product): void
    {
        $variantIds = array_map(fn($variantState) => $variantState['variant_id'], $product->getChildEntities()[Variant::class]);

        DB::table(static::$variantTable)
            ->where('product_id', $product->productId)
            ->whereNotIn('variant_id', $variantIds)
            ->delete();

        foreach ($product->getChildEntities()[Variant::class] as $variantState) {

            // TODO: own repo because option children ...
            $option_value_ids = $this->arrayRemove($variantState, 'option_value_ids');

            DB::table(static::$variantTable)
                ->updateOrInsert([
                    'product_id' => $product->productId->get(),
                    'variant_id'  => $variantState['variant_id'],
                ], $variantState);

            $this->syncVariantOptionValueIds(
                VariantId::fromString($variantState['variant_id']),
                $option_value_ids
            );
        }
    }

    private function syncVariantOptionValueIds(VariantId $variantId, array $option_value_ids): void
    {
        $changedOptionValueIds = collect($option_value_ids);

        // Get all existing option_value ids
        $existingOptionValueIds = DB::table(static::$variantOptionValueLookupTable)
            ->where('variant_id', $variantId)
            ->select('option_value_id')
            ->get();

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

    private function syncTaxonIds(ProductId $productId, array $taxon_ids): void
    {
        $changedTaxonIds = collect($taxon_ids);

        // Get all existing taxon ids
        $existingTaxonIds = DB::table(static::$productTaxonLookupTable)
            ->where('product_id', $productId)
            ->select('taxon_id')
            ->get();

        // Remove the ones that are not in the new list
        $detachTaxonIds = $existingTaxonIds->diff($changedTaxonIds);
        if($detachTaxonIds->count() > 0) {
            DB::table(static::$productTaxonLookupTable)
                ->where('product_id', $productId)
                ->whereIn('taxon_id', $detachTaxonIds->all())
                ->delete();
        }

        // Insert the new taxon ids
        $attachTaxonIds = $changedTaxonIds->diff($existingTaxonIds);

        $insertData = $attachTaxonIds->map(function($taxon_id) use($productId) {
            return ['product_id' => $productId->get(), 'taxon_id' => $taxon_id];
        })->all();

        DB::table(static::$productTaxonLookupTable)->insert($insertData);
    }

    private function exists(ProductId $productId): bool
    {
        return DB::table(static::$productTable)->where('product_id', $productId->get())->exists();
    }

    public function find(ProductId $productId): Product
    {
        $productState = DB::table(static::$productTable)
            ->select([static::$productTable . '.*', DB::raw('GROUP_CONCAT(`taxon_id`) AS taxon_ids')])
            ->where(static::$productTable . '.product_id', $productId->get())
            ->leftJoin(static::$productTaxonLookupTable, static::$productTable . '.product_id','=',static::$productTaxonLookupTable.'.product_id')
            ->first();

        // Handle a bug in laravel where raw group concat statement would return a record with falsy null values
        if(null === $productState->product_id) {
            $productState = null;
        }

        if (!$productState) {
            throw new CouldNotFindProduct('No product found by id [' . $productId->get() . ']');
        }

        $productState = array_merge((array)$productState, ['taxon_ids' => $productState->taxon_ids ?: []]);

        $variantStates = DB::table(static::$variantTable)
            ->select([static::$variantTable . '.*', DB::raw('GROUP_CONCAT(`option_value_id`) AS option_value_ids')])
            ->where(static::$variantTable . '.product_id', $productId->get())
            ->leftJoin(static::$variantOptionValueLookupTable, static::$variantTable . '.variant_id','=',static::$variantOptionValueLookupTable.'.variant_id')
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

        $optionStates = DB::table(static::$optionTable)
            ->where(static::$optionTable . '.product_id', $productId->get())
            ->orderBy(static::$optionTable . '.order_column')
            ->get()
            ->map(fn($item) => (array) $item)
            ->map(function($item) {

                // TODO: avoid nested query calls...
                $item['values'] = DB::table(static::$optionValueTable)
                    ->where(static::$optionValueTable . '.option_id', $item['option_id'])
                    ->orderBy(static::$optionValueTable . '.order_column')
                    ->get()
                    ->map(fn($item) => (array) $item)
                    ->toArray();

                return $item;
            })
            ->toArray();

        return Product::fromMappedData($productState, [
            Variant::class => $variantStates,
            Option::class => $optionStates,
        ]);
    }

    public function delete(ProductId $productId): void
    {
        DB::table(static::$variantTable)->where('product_id', $productId->get())->delete();
        DB::table(static::$productTable)->where('product_id', $productId->get())->delete();
    }

    public function nextReference(): ProductId
    {
        return ProductId::fromString((string) Uuid::uuid4());
    }

    private function arrayRemove(array &$array, $key)
    {
        $value = $array[$key] ?? null;
        unset($array[$key]);

        return $value;
    }
}
