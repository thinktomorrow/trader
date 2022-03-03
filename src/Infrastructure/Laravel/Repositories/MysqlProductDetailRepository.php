<?php
declare(strict_types=1);

namespace Thinktomorrow\Trader\Infrastructure\Laravel\Repositories;

use Illuminate\Support\Facades\DB;
use Thinktomorrow\Trader\TraderConfig;
use Thinktomorrow\Trader\Domain\Model\Product\ProductState;
use Thinktomorrow\Trader\Domain\Model\Product\Variant\VariantId;
use Thinktomorrow\Trader\Application\Product\ProductDetail\ProductDetail;
use Thinktomorrow\Trader\Application\Product\ProductDetail\ProductDetailRepository;

class MysqlProductDetailRepository implements ProductDetailRepository
{
    private static string $productTable = 'trader_products';
    private static string $variantTable = 'trader_product_variants';

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
}
