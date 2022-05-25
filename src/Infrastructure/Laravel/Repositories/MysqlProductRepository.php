<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Thinktomorrow\Trader\Domain\Model\Product\Product;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Application\Common\TraderHelpers;
use Thinktomorrow\Trader\Domain\Model\Product\Option\Option;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\Variant;
use Thinktomorrow\Trader\Domain\Model\Product\ProductRepository;
use Thinktomorrow\Trader\Domain\Model\Product\VariantRepository;
use Thinktomorrow\Trader\Domain\Model\Product\Exceptions\CouldNotFindProduct;

class MysqlProductRepository implements ProductRepository
{
    private VariantRepository $variantRepository;

    private static string $productTable = 'trader_products';
    private static string $variantTable = 'trader_product_variants';
    private static string $productTaxonLookupTable = 'trader_taxa_products';

    private static string $optionTable = 'trader_product_options';
    private static string $optionValueTable = 'trader_product_option_values';
    private static string $variantOptionValueLookupTable = 'trader_variant_option_values';

    public function __construct(VariantRepository $variantRepository)
    {
        $this->variantRepository = $variantRepository;
    }

    public function save(Product $product): void
    {
        // Save product WITH
        // VARIANTS,
        // options
        // personalisations

        $state = $product->getMappedData();
        $taxon_ids = TraderHelpers::array_remove($state, 'taxon_ids');

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

        foreach ($product->getChildEntities()[Option::class] as $i => $optionState) {

            $option_values = TraderHelpers::array_remove($optionState, 'values');

            DB::table(static::$optionTable)
                ->updateOrInsert([
                    'product_id' => $product->productId->get(),
                    'option_id'  => $optionState['option_id'],
                ], array_merge($optionState,['order_column' => $i]));

            // TODO: do this in one query...
//            trap($option_values);
            foreach($option_values as $j => $option_value) {
                DB::table(static::$optionValueTable)
                    ->updateOrInsert([
                        'option_id'  => $option_value['option_id'],
                        'option_value_id'  => $option_value['option_value_id'],
                    ], array_merge($option_value, ['order_column' => $j]));
            }
        }
    }

    private function upsertVariants(Product $product): void
    {
        $variant_ids = array_map(fn($variant) => $variant->variantId->get(), $product->getVariants());

        DB::table(static::$variantTable)
            ->where('product_id', $product->productId)
            ->whereNotIn('variant_id', $variant_ids)
            ->delete();

        foreach($product->getVariants() as $variant) {
            $this->variantRepository->save($variant);
        }
    }

    private function syncTaxonIds(ProductId $productId, array $taxon_ids): void
    {
        $changedTaxonIds = collect($taxon_ids);

        // Get all existing taxon ids
        $existingTaxonIds = DB::table(static::$productTaxonLookupTable)
            ->where('product_id', $productId)
            ->select('taxon_id')
            ->get()
            ->pluck('taxon_id');

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
            ->groupBy(static::$productTable . '.product_id')
            ->first();

        // Handle a bug in laravel where raw group concat statement would return a record with falsy null values
        if($productState && null === $productState->product_id) {
            $productState = null;
        }

        if (!$productState) {
            throw new CouldNotFindProduct('No product found by id [' . $productId->get() . ']');
        }

        $productState = array_merge((array)$productState, ['taxon_ids' => ($productState->taxon_ids ? explode(',', $productState->taxon_ids) : [])]);

        $variantStates = $this->variantRepository->getStatesByProduct($productId);

        $optionStates = DB::table(static::$optionTable)
            ->join(static::$optionValueTable, static::$optionTable.'.option_id' ,'=',static::$optionValueTable.'.option_id')
            ->where(static::$optionTable . '.product_id', $productId->get())
            ->orderBy(static::$optionTable . '.order_column')
            ->orderBy('option_value_order_column')
            ->select([
                static::$optionTable . '.*',
                static::$optionValueTable . '.option_value_id',
                static::$optionValueTable . '.data AS option_value_data',
                static::$optionValueTable . '.order_column AS option_value_order_column',
            ])
            ->get()
            ->groupBy('option_id')
            ->map(function(Collection $item) {

                $first = $item->first();

                return [
                    'option_id' => $first->option_id,
                    'product_id' => $first->product_id,
                    'data' => $first->data,
                    'values' => array_map(fn($value) => [
                        'option_id' => $value->option_id,
                        'option_value_id' => $value->option_value_id,
                        'data' => $value->option_value_data,
                        'order_column' => $value->option_value_order_column,
                    ], $item->all())
                ];
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
}
