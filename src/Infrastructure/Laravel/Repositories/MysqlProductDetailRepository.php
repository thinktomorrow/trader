<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Illuminate\Support\Facades\DB;
use Thinktomorrow\Trader\Domain\Model\Product\ProductId;
use Thinktomorrow\Trader\Domain\Model\Product\ProductState;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Application\Product\ProductDetail\ProductDetail;
use Thinktomorrow\Trader\Application\Product\ProductOptions\ProductOption;
use Thinktomorrow\Trader\Application\Product\ProductOptions\ProductOptions;
use Thinktomorrow\Trader\Application\Product\ProductDetail\ProductDetailRepository;
use Thinktomorrow\Trader\Application\Product\ProductOptions\ProductOptionsRepository;

class MysqlProductDetailRepository implements ProductDetailRepository, ProductOptionsRepository
{
    private static string $productTable = 'trader_products';
    private static string $variantTable = 'trader_product_variants';

    private static string $optionTable = 'trader_product_options';
    private static string $optionValueTable = 'trader_product_option_values';
    private static string $variantOptionValueLookupTable = 'trader_variant_option_values';

    public function findProductDetail(VariantId $variantId): ProductDetail
    {
        // Basic builder query
        $state = DB::table(static::$variantTable)
            ->join(static::$productTable, static::$variantTable . '.product_id', '=', static::$productTable . '.product_id')
            ->whereIn(static::$productTable . '.state', ProductState::onlineStates())
            ->where(static::$variantTable . '.variant_id', $variantId->get())
            ->select([
                static::$variantTable . '.*',
                static::$productTable . '.data AS product_data',
                static::$productTable . '.order_column AS product_order_column',
            ])
        ->first();

        if(!$state) {
            throw new \RuntimeException('No online variant found by id [' . $variantId->get(). ']');
        }

        $state = (array) $state;

        return ProductDetail::fromMappedData(array_merge($state, [
            'includes_tax' => (bool) $state['includes_tax'],
        ]));
    }

    public function getProductOptions(ProductId $productId): ProductOptions
    {
        $optionValues = DB::table(static::$optionValueTable)
            ->select(static::$optionValueTable.'.*')
            ->join(static::$optionTable, static::$optionValueTable . '.option_id', '=', static::$optionTable.'.option_id')
            ->orderBy(static::$optionTable . '.order_column')
            ->orderBy(static::$optionValueTable . '.order_column')
            ->get()
            ->map(fn($item) => (array) $item)
            ->toArray();

        $productOptions = [];
        foreach($optionValues as $optionValue) {
            $productOptions[] = ProductOption::fromMappedData($optionValue);
        }

        return ProductOptions::fromType($productOptions);
    }
}
